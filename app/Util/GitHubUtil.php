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
			"Authorization: token 84b3f4fcae5afb0dd67d1831e1dcb3cefd144d47",
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
		//var_dump($http_response_header);
		return json_decode($result,true);
	}

}