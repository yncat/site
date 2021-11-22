<?php
namespace Util;

class ValidationUtil{


	// URLの正規表現パターン
	const URL_PATTERN = "@^https?://([a-zA-Z0-9]+[\\.\\-])+[a-zA-Z0-9]+/.*$@";
	const EMAIL_PATTERN = "#^([a-zA-Z0-9][._\\-?+]?)+[a-zA-Z0-9]@([a-zA-Z0-9]+\\.)+[a-zA-Z0-9]+$#";

	//必要な情報が正しい形式で含まれていることを確認する
	static function checkParam(array $data,array $ptn){
		foreach($ptn as $key=>$p){
			if(!isset($data[$key])){
				return false;
			}
			if (\preg_match($p,$data[$key])!=1){
				return false;
			}
		}
		return true;
	}

	public static function validate(string $ptn, string $hystack):bool{
		if (\preg_match($ptn,$hystack)!=1){
			return false;
		}
		return true;
	}
}
