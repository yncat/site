<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Members;
use Model\Dao\Updaterequests;
use Util\ValidationUtil;
use Util\MembersUtil;
use Util\GitHubUtil;
use Util\AdminUtil;
use Util\EncryptUtil;


$app->get('/admin/profile',function (Request $request, Response $response, $args) {
	showProfileEditor(array(),$this->db,$this->view,$response);
});

function showProfileEditor(array $data,$db,$view,$response,$message=""){
	if(empty($data)){
		$members=new Members($db);
		$data=$members->select(array("id"=>$_SESSION["ID"]));
	}
	$data["message"]=$message;

    // Render view
    return $view->render($response, 'admin/profile/edit.twig', $data);
}

$app->post('/admin/profile', function (Request $request, Response $response) {
	$input = $request->getParsedBody();
	$members=new Members($this->db);
	$info=$members->select(array("id"=>$_SESSION["ID"]));
	$message="";

	if($input["type"]==="password"){		//パスワード変更
		if(!ValidationUtil::checkParam($input,array("pw_new"=>"/^[!-~]{8,32}$/"))){
			$message.="新しいパスワードの形式が不正です。";
		} else if($input["pw_new"]!==$input["pw_new_confirm"]){
			$message.="パスワードと確認入力が一致しません。";
		} else if(!password_verify($input["pw_now"],$info["password_hash"])){
			$message.="現在のパスワードが正しくありません。";
		}

		if($message===""){
			$members->update(array("id"=>$_SESSION["ID"],"password_hash"=>password_hash($input["pw_new"],PASSWORD_DEFAULT),"updated"=>time()));
			return $response->withRedirect($request->getUri()->getBasePath()."/admin/"."?".SID);
		}
	} else if($input["type"]==="email"){
		if(!ValidationUtil::checkParam($input,array("new"=>"#^[a-zA-Z]([a-zA-Z0-9._\\-?+/]{0,63}[a-zA-Z0-9])*@([a-zA-Z0-9]+\\.)+[a-zA-Z0-9]+$#"))){
			$message.="入力されたメールアドレスの形式が不正です。";
		} else if($input["new"]!==$input["new_confirm"]){
			$message.="確認入力が一致しません。";
		}
		if($message===""){
			$message="確認メールを送信しました。メール内のリンクをクリックすると変更が反映され、現在のセッションからログアウトします。";
			mb_language("Japanese");
			mb_internal_encoding("UTF-8");
			$url=EncryptUtil::encrypt($_SESSION["ID"]."#".$input["new"]."#".(time()+3600));
			$url=$request->getUri()->getBasePath()."/api/registmailaddress?key=".$url;
			$url="https://".$_SERVER["HTTP_HOST"].$url;
			mb_send_mail($input["new"],"[actLab]メールアドレス登録・変更確認","以下のリンクをクリックして、登録を完了させてください。\r\n".$url,"From: info@actlab.org");
			return showProfileEditor(array(),$this->db,$this->view,$response,$message);
		}
	} else if($input["type"]==="publicInformation"){
		$message=profileParamCheck($input);

		if($message===""){
			return showProfileConfirm($input,$this->db,$this->view,$response,$message);
		}
	} else if($input["type"]==="slack-del"){
		if($input["slack-del"] === $info["slack"]){
			$info["slack"]=null;
			$members->update($info);
			$message = "Slackアカウントの登録を解除しました。";
			$input["slack"] = null;
		} else {
			$message = "確認入力が正しく行われていないため、Slackアカウントの削除に失敗しました。";
		}
	}
	return showProfileEditor($input,$this->db,$this->view,$response,$message);
});

function showProfileConfirm(array $data,$db,$view,$response,$message=""){
	$data["message"]=$message;
	MembersUtil::makeLinkCode($data);
    // Render view
    return $view->render($response, 'admin/profile/confirm.twig', $data);
}


function profileParamCheck($input){
	$message="";
	if(!ValidationUtil::checkParam($input,array("name"=>"/^.{1,16}$/u"))){
		$message.="表示名は１～16字で入力してください。";
	}
	if(!ValidationUtil::checkParam($input,array("introduction"=>"/^.{10,700}$/us"))){
		$message.="自己紹介は10～300字で入力してください。";
	}
	if(!empty($input["twitter"]) && !ValidationUtil::checkParam($input,array("twitter"=>"/^[a-zA-Z0-9_]{3,15}$/"))){
		$message.="TwitterIDは先頭の@をつけずに、英数字及び_からなる3～15字の文字列で指定してください。";
	}
	if(!ValidationUtil::checkParam($input,array("github"=>"/(?=^[a-zA-Z0-9][a-zA-Z0-9\\-]{1,28}[a-zA-Z0-9]$)(?!^.*-{2,}.*$)/"))){
		$message.="githubユーザ名が不正です。";
	}
	if(!empty($input["url"]) && !ValidationUtil::checkParam($input,array("url"=>ValidationUtil::URL_PATTERN))){
		$message.="URLの形式が不正です。";
	}
	return $message;
}

function setProfile($input,$db){
	if($_SESSION["ID"]==$input["id"]){	#本人リクエストなのでペンディング
		$no = AdminUtil::sendRequest("publicInformation", $input, "".$_SESSION["ID"]);
		return "リクエストを記録し、他のメンバーに承認を依頼しました。[リクエストNo:".$no."]";
	} else {	#本人以外のリクエストなので確定してDB反映
		$updaterequests=new Updaterequests($db);
		$request = $updaterequests->select(array(
			"type"=>"publicInformation",
			"identifier"=>"".$input["id"]
		));
		$info=unserialize($request["value"]);
		unset($info["type"]);
		$info["id"]=$request["requester"];		//念のため対策
		$members=new Members($db);
		$members->update($info);
		AdminUtil::completeRequest($request["id"]);

		return "更新が完了しました。";
	}
}
