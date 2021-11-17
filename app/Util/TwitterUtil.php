<?php
namespace Util;


use Abraham\TwitterOAuth\TwitterOAuth;
use Twitter\Text;
use slim\Http\Request;
use Util\SlackUtil;

class TwitterUtil{
	protected static $twitter = null;

	static function initializeIfNeeded(){
		if(!self::$twitter){
			self::$twitter = new TwitterOAuth(getenv("TWITTER_API_KEY"), getenv("TWITTER_API_SECRET"), getenv("TWITTER_ACCESS_TOKEN"), getenv("TWITTER_ACCESS_TOKEN_SECRET"));
		}
		return;
	}

	public static function tweet(string $text, string $url = ""){
		self::initializeIfNeeded();
		$body = self::makeTweetString($text, $url);
		if(EnvironmentUtil::isProduct()){
			self::$twitter->post("statuses/update", ["status" => $body]);
		} else {
			SlackUtil::notify("Tweet:" . $body);
		}
	}

	public static function makeTweetString(string $tweet, string $url = ""){
		$tweetText = $tweet;
		if($url != ""){
			$tweetText .= "\n" . UrlUtil::toAbsUrl($url);
		}
		return $tweetText;
	}

	public static function getLength(string $tweet, string $url = ""){
		$validator = Text\Validator::create();
		return $validator->getTweetLength(self::makeTweetString($tweet, $url));
	}

	public static function isTweetable(string $tweet, string $url = ""){
		return (self::getLength($tweet, $url) <= 280);
	}

	public static function verifyTwitterRequest(Request $request){
		$signature = $request->getHeaderLine("x-twitter-webhooks-signature");
		$hash = hash_hmac("sha256", $request->getBody()->getContents(), getenv("TWITTER_API_SECRET"), true);
		$request->getBody()->rewind();
		$created_signature = "sha256=".base64_encode($hash);
		return $signature === $created_signature;
	}

	static function getAuthorizedUserInfo(){
		global $app;
		$logger = $app->getContainer()->get("logger");
		self::initializeIfNeeded();
		$result = self::$twitter->get("account/verify_credentials", [], true);
		return $result;
	}
}
