<?php
namespace src\twigExtention;

class urlFunctions{
	public static function get_current_url(){
		$url = "http://";
		if(!empty($_SERVER["HTTPS"])){
			$url = "https://";
		}
		$url .= $_SERVER["HTTP_HOST"];
		$url .= $_SERVER["REQUEST_URI"];
		return $url;
	}
}
