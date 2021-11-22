<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\SoftwareUtil;
use Util\ValidationUtil;

$app->post('/api/authorize_product', function (Request $request, Response $response) {
	$input = $request->getParsedBody();

	//必須パラメータチェック
	if(!ValidationUtil::checkParam($input,array(
		"software_name"=>"/^[a-zA-Z0-9]+$/",
		"software_version"=>"/^\d+\\.\d+\\.\d+$/",
		"api_version"=>"/1\\.0\\.0/",
		"serialnumber"=>"/^[A-Z0-9]{16}$/",
		"unlock_code"=>"/^[0-9a-f]{64}$/",
	))){
		$json["code"] = 400;
		$json["message"] = "Required parameter not found or missing.";
		return $response->withJson($json,400);
	}

	$result = SoftwareUtil::authorizeProduct(
		$input["software_name"],
		$input["software_version"],
		$input["serialnumber"],
		$input["unlock_code"]
	);

	$json = [];
	$json["code"] = $result;
	if ($result === SoftwareUtil::AUTH_RESULT_RATE_LIMIT){
		$json["message"] = "Please request again later.";
	} else if ($result === SoftwareUtil::AUTH_RESULT_SERIAL_NOT_FOUND){
		$json["message"] = "serialnumber not found";
	} else if ($result === SoftwareUtil::AUTH_RESULT_VALIDATION_FAILED){
		$json["message"] = "Please update the software before authentication.";
	} else if ($result === SoftwareUtil::AUTH_SERIAL_LOCKED){
		$json["message"] = "This serial number is correct, but it is not available.";
	} else if ($result === SoftwareUtil::AUTH_RESULT_SUCCESS){
		$json["message"] = "Success!";

		$json["license_data"] = SoftwareUtil::makeLicenseFile(
				$input["serialnumber"],
				$input["unlock_code"]
			);
	} else {
		$json["message"] = "Unknown";
	}
	return $response->withJson($json, 200, JSON_UNESCAPED_SLASHES);
});
