<?php
function connect_github($url,$method="GET",$param=array()){
	$baseUrl = 'https://api.github.com';

	if($method!="GET"){
		$data = json_encode($param,JSON_UNESCAPED_UNICODE);
	}
	else {
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
if(!isset($_GET["repo_name"], $_GET["tag_name"], $_GET["password"])){
	http_response_code(400);
	exit("bad request");
}
if($_GET["password"] != getenv("SCRIPT_PASSWORD")){
	http_response_code(400);
	exit("invalid password");
}
$repo_url = $_GET["repo_name"];
$tag_name = $_GET["tag_name"];
$json = connect_github("/repos/".$repo_url."/releases/tags/".$tag_name);
if(isset($json["message"])){
	http_response_code(400);
	exit("skiped to delete release because the release not found.");
}
$release_id = $json["id"];
$json = connect_github("/repos/".$repo_url."/releases/".$release_id, "DELETE");
echo("success");
?>