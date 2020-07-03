<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\members;
use Util\EncryptUtil;
use Util\ValidationUtil;


$app->get('/api/registmailaddress', function (Request $request, Response $response) {

	$members = new members($this->db);

	$data = $request->getQueryParams();

	//必須パラメータチェック
	if(!ValidationUtil::checkParam($data,array(
		"key"=>"/^.+-.+$/",
	))){
		print("有効なパラメータがありません。");
		exit(1);
	}
	$data=EncryptUtil::decrypt($data["key"]);
	if($data===false){
		print("キーが不正です。");
		exit(1);
	}
	$data=explode("#",$data);
	if(count($data)!==3 || !ctype_digit($data[0]) || !ctype_digit($data[2])){
		print("キーが不正です。");
		exit(1);
	}
	if(time()>$data[2]){
		print("URLの有効期限が切れています。");
		exit(0);
	}
	$result=$members->update(array("id"=>$data[0],"email"=>$data[1],"updated"=>time()));
	if($result>0){
		print("updated!");
	} else {
		print("URLの有効期限が切れています。");
	}
	exit(0);
});

