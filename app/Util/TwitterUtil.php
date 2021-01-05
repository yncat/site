<?php
namespace Util;
use Abraham\TwitterOAuth\TwitterOAuth;
use Twitter\Text;

class TwitterUtil{
	public static function tweet(string $text, string $url = ""){
		$connection = new TwitterOAuth(getenv("TWITTER_API_KEY"), getenv("TWITTER_API_SECRET"), getenv("TWITTER_ACCESS_TOKEN"), getenv("TWITTER_ACCESS_TOKEN_SECRET"));
		$result = $connection->post("statuses/update", ["status" => self::makeTweetString($text, $url)]);
		return $result;
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
}
