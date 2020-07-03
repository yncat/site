<?php

namespace Util;

class ValidationUtil{

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

	//$sがツイート可能な文字数か否かを確認
	//$sにはURLは含まず、代わりに$urlに個数のみを指定しておく
	static function checkTweetLength($s,$url=0){
		return mb_strwidth+$url*11.5<=280;
	}
}
