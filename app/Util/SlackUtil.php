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

	static function alert($body){
		self::send($body,getenv("SLACK_ALERT_URL"));
	}

	static function message($body){
		self::send($body,getenv("SLACK_MESSAGE_URL"));
	}

	static function twitter_notify($body){
		self::send($body, getenv("SLACK_TWITTER_NOTIFY_URL"));
	}

	static function sales($body){
		self::send($body, getenv("SLACK_SALES_URL"));
	}

	public static function send($body, string $url){
		global $app;
		$logger = $app->getContainer()->get("logger");
		$header=array(
			"Content-Type: application/json",
			"User-Agent: ACTLaboratory-webadmin"
		);

		if (is_string($body)){
			if (!EnvironmentUtil::isProduct()){
				$body = "[".EnvironmentUtil::getEnvName()."] ".$body;
			}

			$body = [
				"text" => $body
			];
		}

		$logger->debug("send slack request to " . $url . " body=" . var_export($body, true));


		$context = array(
			"http" => array(
				"method"  => "POST",
				"protocol_version" => 1.1,
				"header"  => implode("\r\n", $header),
				"content" => json_encode($body),
				"ignore_errors" => false
			)
		);
		try {
			$result=file_get_contents($url, false, stream_context_create($context));
		} catch (Exception $e){
			$logger->error(var_export($e, true));
		}
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
