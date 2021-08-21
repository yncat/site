<?php
namespace Util;

use Util\EnvironmentUtil;

class SlackUtil{

	static function notify(string $text){
		self::send($text,getenv("SLACK_NOTIFY_URL"));
	}

	static function daily(string $text){
		self::send($text,getenv("SLACK_DAILY_URL"));
	}

	private static function send(string $text, string $url){
		$header=array(
			"Content-Type: application/json",
			"User-Agent: ACTLaboratory-webadmin"
		);

		if (!EnvironmentUtil::isProduct()){
			$text = "[".EnvironmentUtil::getEnvName()."] ".$text;
		}

		$body = [
			"text" => $text
		];
		$context = array(
			"http" => array(
				"method"  => "POST",
				"header"  => implode("\r\n", $header),
				"content" => json_encode($body),
				"ignore_errors"=>true
			)
		);

		$result=file_get_contents($url, false, stream_context_create($context));
		$result = json_decode($result,true);
	}
}
