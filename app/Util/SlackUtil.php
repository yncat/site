<?php
namespace Util;

use Util\EnvironmentUtil;

class SlackUtil{

	static function notify(string $text){
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

		$result=file_get_contents(getenv("SLACK_NOTIFY_URL"), false, stream_context_create($context));
		$result = json_decode($result,true);
	}
}
