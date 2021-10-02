<?php
namespace Util;

use Model\Dao\Members;
use Model\Dao\Updaterequests;
use Util\EnvironmentUtil;
use Util\SlackUtil;

class AdminUtil{
	const COMPLETE_TYPE_ACCEPT = 0;
	const COMPLETE_TYPE_SELF_DELETED = 1;

	static function sendRequest(string $type,array $data, string $identifier=null): int{
		global $app;
		$updaterequests=new Updaterequests($app->getContainer()->get("db"));

		$request = [
			"type"=>$type,
			"identifier"=>$identifier
		];

		// 重複するリクエストがある場合には削除する
		// value以外の内容が同一なら削除対象
		$updaterequests->delete($request);

		// requesterとvalueを追加してDBに登録
		$request["requester"] = $_SESSION["ID"];
		$request["value"] = serialize($data);
		$no = $updaterequests->insert($request);

		// Slack通知
		$title = self::makeRequestTitle($request);
		SlackUtil::notify("リクエストの承認をお願いします。\n<".UrlUtil::toAbsUrl("admin/request/$no/")."|$title>\n");

		return $no;
	}

	static function completeRequest($id, $action=self::COMPLETE_TYPE_ACCEPT){
		global $app;
		$updaterequests=new Updaterequests($app->getContainer()->get("db"));
		$request = $updaterequests->select(["id"=>$id]);

		// Slack通知
		$title = self::makeRequestTitle($request);
		if ($action === self::COMPLETE_TYPE_ACCEPT){
			$members=new Members($app->getContainer()->get("db"));

			$name = $members->select(["id"=>$_SESSION["ID"]])["name"];
			SlackUtil::notify("リクエストが承認されました。\n対象リクエスト：No.$id $title\n承認者：$name");
		} else {
			SlackUtil::notify("リクエストが削除されました。\n対象リクエスト：No.$id $title\n");
		}
		$updaterequests->delete(["id"=>$id]);
	}

	static function makeRequestTitle(array $request):string{
		global $app;
		$members=new members($app->getContainer()->get("db"));
		$requester=$members->select(array("id"=>$request["requester"]))["name"];

		if($request["type"]==="publicInformation"){
			return $requester."の公開プロフィール変更";
		}
		if($request["type"]==="new"){
			return $requester."から新規ソフトウェア公開要求(".$request["identifier"].")";
		}
		if($request["type"]==="update"){
			return $requester."からバージョンアップ配信要求(".$request["identifier"].")";
		}
		if($request["type"]==="edit"){
			return $requester."から".$request["identifier"]."の公開情報変更要求";
		}
		if($request["type"]==="informations"){
			return $requester."からのお知らせ配信要求";
		}
		if($request["type"]==="delete_software_version"){
			return $requester."から" . $request["identifier"] . "の削除要求";
		}

	}
}
