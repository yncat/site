<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Softwares;
use Util\SoftwareUtil;
use Util\ValidationUtil;

$app->get('/api/checkUpdate', function (Request $request, Response $response) {

    $softwares = new softwares($this->db);

	$data = $request->getQueryParams();

	//必須パラメータチェック
	if(!ValidationUtil::checkParam($data,array(
		"version"=>"/^\d+\\.\d+\\.\d+$/",
		"updater_version"=>"/^\d+\\.\d+\\.\d+$/",
		"name"=>"/^[a-zA-Z0-9]+$/"
	))){
		$json["code"] = 400;
		$json["message"] = "Required parameter not found.";
		return $response->withJson($json);
	}

	$software=$softwares->getLatest($data["name"]);
	if($software==null){
		$json["code"] = 404;
		$json["message"] = "Requested name is not registed.";
		return $response->withJson($json);
	}
	$software=$software[0];
	SoftwareUtil::makeTextVersion($software);
	$ret=SoftwareUtil::conpareVersion($software["versionText"],$data["version"]);

	if ($ret==false){
		$json["code"] = 204;
		$json["message"] = "Your version is up to date.";
		return $response->withJson($json);
	} else {
		$json["code"] = 200;
		$json["message"] = "Please Update to version ".$software["versionText"].".";
		$json["updater_url"] = $software["updater_URL"];
		$json["update_version"] = $software["versionText"];
		$json["update_description"] = $software["hist_text"];
		$json["updater_hash"] = $software["updater_hash"];
		return $response->withJson($json,200,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	}

});

