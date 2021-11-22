<?php

namespace Util;

use Model\Dao\Members;

class MembersUtil{

	public static function makeLinkCode(array &$info){
		if(!empty($info["twitter"])){
			$info["twitter_link"]=
			"<a href=\"https://twitter.com/".$info["twitter"]."?ref_src=twsrc%5Etfw\" class=\"twitter-follow-button\" data-show-count=\"false\">Twitter: @".$info["twitter"]."</a><script async src=\"https://platform.twitter.com/widgets.js\" charset=\"utf-8\"></script>";
			//"<p><a href=\"https://twitter.com/".$info["twitter"]."\">Twitter</a></p>";
		} else {
			$info["twitter_link"]=null;
		}

		if(!empty($info["github"])){
			$info["github_link"]="<p><a href=\"https://github.com/".$info["github"]."\">gitHub</a></p>";
		} else {
			$info["github_link"]=null;
		}

		if(!empty($info["url"])){
			$info["URL_link"]="<p><a href=\"".$info["url"]."\">個人サイト</a></p>";
		} else {
			$info["URL_link"]=null;
		}

		$info["links"]="";
		if(isset($info["twitter_link"])){
			$info["links"].=$info["twitter_link"]."\n";
		}
		if(isset($info["github_link"])){
			$info["links"].=$info["github_link"]."\n";
		}
		if(isset($info["URL_link"])){
			$info["links"].=$info["URL_link"]."\n";
		}
	}

	//セッションのログイン情報の有効性確認
	static function check($db){
	    $members=new Members($db);

		if(empty($_SESSION["ID"]) || empty($_SESSION["updated"])){
			return false;		//パラメータ不足
		}
		$info=$members->select(array("id"=>$_SESSION["ID"]));
		if($info===false){
			return false;
		}
		if($info["updated"]>$_SESSION["updated"]){
			return false;
		}
		return true;
	}
}
