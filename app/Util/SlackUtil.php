<?php
namespace Util;

use Slim\Http\Request;

use Util\EnvironmentUtil;


class SlackUtil {

	static function notify($body){
		self::send($body,getenv("SLACK_NOTIFY_URL"));
	}

	static function daily($body){
		self::send($body,getenv("SLACK_DAILY_URL"));
	}

	public static function send($body, string $url){
		$header=array(
			"Content-Type: application/json",
			"User-Agent: ACTLaboratory-webadmin"
		);

		if (is_string($body)){
			if (!EnvironmentUtil::isProduct()){
				$body = "[".EnvironmentUtil::getEnvName()."] ".$text;
			}

			$body = [
				"text" => $text
			];
		}

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
		return $result;
	}

	public static function velifySlackRequest(Request $request){
	    $timestamp = $request->getHeaderLine('x-slack-request-timestamp');
    	$signature = $request->getHeaderLine('x-slack-signature');
	    $requestBody = $request->getBody()->getContents();
    	$secret = getenv("SLACK_SIGNING_SECRET");

		//カーソル位置を先頭に戻しておく
		$request->getBody()->rewind();

	    $sigBasestring = 'v0:' . $timestamp . ':' . $requestBody;
    	$hash = 'v0=' . hash_hmac('sha256', $sigBasestring, $secret);
	    return $hash === $signature;
	}
}
