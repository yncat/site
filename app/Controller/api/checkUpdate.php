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

	$isAlpha = SoftwareUtil::conpareVersion($data["version"],"2000.0.0");
	if (!$isAlpha){
		//Ver2000未満は正式版
		$software=$softwares->getLatest($data["name"]);
	} else {
		//alpha版
		$software=$softwares->getLatestAlpha($data["name"]);
   	}
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
		$software_history = $softwares->getVersions($data["version"], $data["name"], $isAlpha);
		$descriptions = [];
		foreach($software_history as $history){
			SoftwareUtil::makeTextVersion($history);
			$descriptions[] = "バージョン".$history["versionText"]."更新情報";
			$descriptions[] = $history["hist_text"];
			$descriptions[] = "";
		}
		$json["update_description"] = implode("\n", $descriptions);
		$json["updater_hash"] = $software["updater_hash"];
		return $response->withJson($json,200,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	}
});
