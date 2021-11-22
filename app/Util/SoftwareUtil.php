<?php

namespace Util;

use Model\Dao\Softwares;
use Model\Dao\AuthorizationLog;
use Model\Dao\Serialnumbers;
use Model\Dao\TrialAuthorizationLog;


class SoftwareUtil{

	// rate limitの対象=3時間以内に10回
	// 製品・体験版それぞれで数えるが、ここの値は共通で使われる
	private const RATE_TIME = 60*60*3;
	private const RATE_LIMIT = 10;

	// 認証回数の上限=年間3回まで
	private const AUTH_RATE_TIME = 60*60*24*365;
	private const AUTH_RATE_LIMIT = 3;

	// 体験版起動回数の上限=半年で3回まで
	private const TRIAL_RATE_TIME = 60*60*24*180;
	private const TRIAL_RATE_LIMIT = 3;

	// ライセンスファイルに埋め込まれるシリアル番号の末尾桁数
	private const LAST_KEY_LENGTH = 4;

	// 認証結果
	public const AUTH_RESULT_SUCCESS = 200;
	public const AUTH_RESULT_RATE_LIMIT = 429;
	public const AUTH_RESULT_SERIAL_NOT_FOUND = 404;
	public const AUTH_RESULT_SOFTWARE_NOT_FOUND = 404;
	public const AUTH_RESULT_VALIDATION_FAILED = 409;
	public const AUTH_SERIAL_LOCKED = 423;
	public const AUTH_TRIAL_LIMIT = 423;

	public static function makeTextVersion(array &$info){
		//必須情報の確認
		if (!isset($info["major"]) || !isset($info["minor"]) || !isset($info["patch"])){
			throw new \Exception("MakeTextVersion() cannot found required param");
		}

		$info["versionText"]=$info["major"].".".$info["minor"].".".$info["patch"];
	}

	//aがbより新しければtrueを返す
	//a,bともに書式チェック済みであることを前提とする
	public static function conpareVersion(string $a,string $b):bool{
		$x=explode(".",$a);
		$y=explode(".",$b);
		for($i=0;$i<3;$i++){
			if ($x[$i]>$y[$i]){
				return true;
			} else if ($x[$i]==$y[$i]){
				continue;
			} else {
				return false;
			}
		}
		return false;
	}

	public static function makeSoftwareUrl($keyword){
		return "/software/".$keyword;
	}

	// 製品を認証する
	public static function authorizeProduct(string $name, string $version, string $key, string $code):int{
		$authLog = new AuthorizationLog();

		// write request log
		$logId = $authLog->insert([
			"host" => getenv("REMOTE_ADDR"),
			"serialnumber" => $key,
			"code" => $code,
		]);

		// check rate limit
		if($authLog->checkRateCount(getenv("REMOTE_ADDR"), self::RATE_TIME) >= self::RATE_LIMIT){
			return self::AUTH_RESULT_RATE_LIMIT;
		}

		// get silial Data
		$silialnumbers = new Serialnumbers();
		$info = $silialnumbers->getNumberInfo($key);
		if(!$info){
			return self::AUTH_RESULT_SERIAL_NOT_FOUND;
		}
		$info = $info[0];

		// validation
		if ($info["software_name"] !== $name || $info["major"].".".$info["minor"].".".$info["patch"] !== $version){
			return self::AUTH_RESULT_VALIDATION_FAILED;
		}

		// 認証済みデバイスチェック
		if(!$authLog->select([
			"serialnumber" => $key,
			"code" => $code,
			"status" => AuthorizationLog::STATUS_AUTH_SUCCESS,
		])){
			// 新規認証

			// 直近の認証回数チェック
			if($authLog->checkAuthCount($key, self::AUTH_RATE_TIME) >= self::AUTH_RATE_LIMIT){
				return self::AUTH_SERIAL_LOCKED;
			}

			// ログの記録
			$authLog->update([
				"id" => $logId,
				"status" => AuthorizationLog::STATUS_AUTH_SUCCESS,
			]);
		} else {
			// 再認証
			// ログの記録
			$authLog->update([
				"id" => $logId,
				"status" => AuthorizationLog::STATUS_AUTH_RETRY,
			]);
		}
		return self::AUTH_RESULT_SUCCESS;
	}


	// 体験版を認証する
	public static function authorizeTrial(string $name, string $version, string $code):int{
		$authLog = new TrialAuthorizationLog();

		// write request log
		$data = [
			"host" => getenv("REMOTE_ADDR"),
			"software_name" => $name,
			"code" => $code,
		];
		$software_info = (new Softwares)->getLatest($name);
		if ($software_info){
			$software_info = $software_info[0];
			$data["software_id"] = $software_info["id"];
			$logId = $authLog->insert($data);
		} else {
			$logId = $authLog->insert($data);
			return self::AUTH_RESULT_SOFTWARE_NOT_FOUND;
		}

		// check rate limit
		if($authLog->checkRateCount(getenv("REMOTE_ADDR"), self::RATE_TIME) >= self::RATE_LIMIT){
			return self::AUTH_RESULT_RATE_LIMIT;
		}

		// check software version
		if ($software_info["major"].".".$software_info["minor"].".".$software_info["patch"] !== $version){
			return self::AUTH_RESULT_VALIDATION_FAILED;
		}

		// 体験版利用回数チェック
		if($authLog->checkAuthCount($software_info["id"], $code, self::TRIAL_RATE_TIME) >= self::TRIAL_RATE_LIMIT){
			return self::AUTH_TRIAL_LIMIT;
		}

		// ログの記録
		$authLog->update([
			"id" => $logId,
			"status" => AuthorizationLog::STATUS_AUTH_SUCCESS,
		]);

		return self::AUTH_RESULT_SUCCESS;
	}

	// シリアルキー、アンロックコードに対応するライセンスファイルを生成し、暗号化してBASE64したものを返す
	// 必ず$keyの存在確認その他のバリデーションをしてから呼び出すこと
	// 事前にauthorizeProduct()で認証手続きを済ませておく必要がある
	public static function makeLicenseFile(string $key, string $code):string{
		$info = (new Serialnumbers())->getNumberInfo($key)[0];
		$auth_info = (new AuthorizationLog)->select([
			"serialnumber" => $key,
			"code" => $code,
			"status" => AuthorizationLog::STATUS_AUTH_SUCCESS,
		]);

		$data = [
			"unlock_code" => $code,
			"serial_last" => substr($key, -1*self::LAST_KEY_LENGTH),
			"user_name" => $info["name"],
			"user_email" => $info["email"],
			"software_name" => $info["software_name"],
			"software_version" => $info["major"] . "." . $info["minor"],
			"authorized_at" => $auth_info["created_at"],
		];
		$data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		return base64_encode(EncryptUtil::encrypt_private_key($data));
	}

	// ソフト名、アンロックコード、nonceに対応する体験版起動許可認証データを生成し、暗号化してBASE64したものを返す
	// 起動してよいか、パラメータは不正でないか等、バリデーションをしてから呼び出すこと
	public static function makeTrialApproveData(string $software_name, string $code, string $nonce):string{

		$data = [
			"unlock_code" => $code,
			"software_name" => $software_name,
			"nonce" => $nonce,
		];
		$data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		return base64_encode(EncryptUtil::encrypt_private_key($data));
	}
}
