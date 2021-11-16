<?php

use Slim\Http\Request;
use Slim\Http\Response;


use Util\DropboxUtil;
use Util\ValidationUtil;

$app->get('/api/dbDump', function (Request $request, Response $response) {
	$data = $request->getQueryParams();

	//必須パラメータチェック
	if(!ValidationUtil::checkParam($data,[
		"password"=>"/^".getenv("SCRIPT_PASSWORD")."$/",
	])){
		$json["code"] = 401;
		$json["message"] = "Authorization failed.";
		return $response->withJson($json);
	}

	if (dbDump()){
		$json["code"] = 200;
		$json["message"] = "Success!";
		return $response->withJson($json);
	} else {
		$json["code"] = 500;
		$json["message"] = "Error!";
		return $response->withJson($json,500);
	}
});

function dbDump(){
	$cmd = "mysqldump " . getenv('DB_NAME') . " -u " . getenv('DB_USER') . " -p" . getenv('DB_PASS') . " --default-character-set=utf8mb4 --no-tablespaces";
	$data = shell_exec($cmd);
	return DropboxUtil::save("server・ITサービス利用/DB_backups/backup_".date("y-m-d-His").".sql",$data);
}
