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

	public static function get_current_page_path(){
		global $app;
		return $app->getContainer()->request->getUri()->getBasePath() . $app->getContainer()->request->getUri()->getPath();
	}

	public static function get_base_path(){
		global $container;
		return $container["request"]->getUri()->getBasePath();
	}
}
