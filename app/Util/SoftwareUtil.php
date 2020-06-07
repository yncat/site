<?php

namespace Util;


class SoftwareUtil{

	static function makeTextVersion(array &$info){
		//必須情報の確認
		if (!isset($info["major"]) || !isset($info["minor"]) || !isset($info["patch"])){
			throw new \Exception("MakeTextVersion() cannot found required param");
		}

		$info["versionText"]=$info["major"].".".$info["minor"].".".$info["patch"];
	}

	//aがbより新しければtrueを返す
	//a,bともに書式チェック済みであることを前提とする
	static function conpareVersion(string $a,string $b):bool{
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
		print("last");
		return false;
	}
}
