<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Members;
use Util\EncryptUtil;
use Util\ValidationUtil;

$app->get('/api/registmailaddress', function (Request $request, Response $response) {
	$members = new members($this->db);

	$data = $request->getQueryParams();

	//必須パラメータチェック
	if(!ValidationUtil::checkParam($data,array(
		"key"=>"/^.+-.+$/",
	))){
		$response->write("key required!");
		$response->withStatus(400,"key required!");
	}
	$data=EncryptUtil::decrypt($data["key"]);
	if($data===false){
		$response->write("wrong key!");
		$response->withStatus(400,"wrong key!");
	}
	$data=explode("#",$data);
	if(count($data)!==3 || !ctype_digit($data[0]) || !ctype_digit($data[2])){
		$response->write("wrong key!");
		$response->withStatus(400,"wrong key!");
	}
	if(time()>$data[2]){
		$response->write("your url was expired!");
		$response->withStatus(400,"your url was expired!");
	}
	$result=$members->update(array("id"=>$data[0],"email"=>$data[1],"updated"=>time()));
	if($result>0){
		$response->write("updated!");
		$response->withStatus(200,"updated!");
	} else {
		$response->write("failed!");
		$response->withStatus(500,"failed!");
	}
});

