<?php


namespace Util;


class GitHubUtil{


	static function connect($url,$method="GET",$param=array()){
		$baseUrl = 'https://api.github.com';

		if($method!="GET"){
			$data = json_encode($param,JSON_UNESCAPED_UNICODE);
		} else {
			$data = null;
		}

		$header=array(
			"User-Agent: ACTLaboratory-webadmin",
			"Accept: application/vnd.github.v3+json",

			"Authorization: token ".getenv("GITHUB_TOKEN"),
			"Time-Zone: Asia / Tokyo",
			"Content-Type: application/json; charset=utf-8",
			"Content-Length:".strlen($data)
		);

		$context = array(
			"http" => array(
				"method"  => $method,
				"header"  => implode("\r\n", $header),
				"content" => $data,
				"ignore_errors"=>true
				)
		);
		$result=file_get_contents($baseUrl.$url, false, stream_context_create($context));
		return json_decode($result,true);
	}

	static function get_assets($assets_url){
		$header=array(
			"Accept: application/octet-stream",
			"Authorization: token ".getenv("GITHUB_TOKEN"),
		);
		$handle = curl_init($assets_url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_USERAGENT, "ACTLaboratory-webadmin");
		curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);
		$content = curl_exec($handle);
		$fileUrl = curl_getinfo($handle, CURLINFO_REDIRECT_URL);
		curl_close($handle);

		$header=array("Accept: application/octet-stream");
		$handle = curl_init($fileUrl);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_USERAGENT, "ACTLaboratory-webadmin");
		curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
		$content = curl_exec($handle);

		curl_close($handle);
		return $content;
	}

	# idのユーザの指定日のコントリビューション数を返す
	# id間違い・古い・未来である等で取得できない場合にはFalseを返す
	public static function getContributionCountByUser($id,$date){
		try {
			$page = file_get_contents("https://github.com/$id");
			$ret = preg_replace(
				'/^.+data-count="([0-9]+)" data-date="'.$date.'" data-level=.+$/s',
				"$1",
				$page);
			if (is_numeric($ret)){
				return intval($ret);
			} else {
				return -1;
			}
		} catch (Exception $e){
			return -1;
		}
	}

	public static function getTagByDownloadURL($url){
		return preg_replace("|^.+download/([^/]+)/.+$|","$1",$url);
	}
}
