<?php

namespace Util;

class EncryptUtil{

	static function encrypt($str){
		$iv=openssl_random_pseudo_bytes(16);
		$enclypted=openssl_encrypt($str,"AES-256-CBC",getenv("DB_PASS"),OPENSSL_RAW_DATA,$iv);
		$base64_iv=EncryptUtil::base64_urlSafe_encode($iv);
		$base64_enclypted=EncryptUtil::base64_urlSafe_encode($enclypted);
		$key=$base64_iv."-".$base64_enclypted;
		return $key;
	}

	static function decrypt($key){
		$key=explode("-",$key);
		if (count($key)==0){
			return false;
		}
		$iv=EncryptUtil::base64_urlSafe_decode($key[0]);
		$encrypted=EncryptUtil::base64_urlSafe_decode($key[1]);
		$decrypted=openssl_decrypt($encrypted,"AES-256-CBC",getenv("DB_PASS"),OPENSSL_RAW_DATA,$iv);
		return $decrypted;
	}

	// 秘密鍵で暗号化する
	// 出力はバイナリなので注意
	static function encrypt_private_key($data): string{
		$key = file_get_contents(getenv("PRIVATE_KEY_PATH"));
		openssl_private_encrypt($data, $crypted, $key);

		return $crypted;
	}

	static function base64_urlsafe_encode($val) {
		$val = base64_encode($val);
		return str_replace(array('+', '/', '='), array('_', '~', '.'), $val);
	}

	static function base64_urlsafe_decode($val) {
		$val = str_replace(array('_','~', '.'), array('+', '/', '='), $val);
		return base64_decode($val);
	}
}
