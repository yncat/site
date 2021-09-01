<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Members;
use Util\EncryptUtil;
use Util\ValidationUtil;

$app->get('/admin/login', function (Request $request, Response $response) {
    // Render index view
	$data = [];
	if (isset($_GET["action"]) && isset($_GET["key"])){
		$data["action"] = $_GET["action"];
		$data["key"] = $_GET["key"];
	}

    return $this->view->render($response, 'admin/login.twig', $data);
});

//ログイン
$app->post('/admin/login', function (Request $request, Response $response) {
    $members=new Members($this->db);

	$input = $request->getParsedBody();
	$info=$members->select(array("email"=>$input["email"]));

	// ログイン失敗の場合
	if($info===false || !password_verify($input["password"],$info["password_hash"])){
		$data = [
			"email"=>$input["email"],
			"message"=>"メールアドレスまたはパスワードが誤っています。"
		];
		if(isset($input["action"]) && isset($input["key"])){
			$data["action"] = $input["action"];
			$data["key"] = $input["key"];
		}
	    return $this->view->render($response, 'admin/login.twig', $data);
	}

	$_SESSION["ID"]=$info["id"];
	$_SESSION["updated"]=time();

	$message = "";

	// 指定アクションの実行
	if(isset($input["action"]) && isset($input["key"])){
		// Slackアカウントの関連付け
		if($input["action"] === "slack"){
			$key = EncryptUtil::decrypt($input["key"]);
			if($key !== false && ValidationUtil::validate("|^U[A-Z0-9]+#[0-9]+$|",$key)){
				$prm = explode("#", $key);
				if ($prm[1] > time()){
					$info["slack"] = $prm[0];
					$members->update($info);
					$message = "Slackアカウントとの関連付けに成功しました。";
				} else {
					$message = "URLの有効期限が切れているため、通常のログインのみを行いました。";
				}
			} else {
				$message = "URLが不正なため、通常のログインのみを行いました。";
			}
		}
	}
    // Redirect index page
	return $response->withRedirect($request->getUri()->getBasePath()."/admin/"."?message=".urlencode($message)."&".SID);
});

//新規登録車用補助ツール
$app->post('/admin/login/new', function (Request $request, Response $response) {
	$input = $request->getParsedBody();

	if($input["password"]!==$input["password_confirm"]){
		return $response->withRedirect($request->getUri()->getBasePath()."/admin/login");
	}
    // Render index view
    return $this->view->render($response, 'admin/new.twig',array("hash"=>password_hash($input["password"],PASSWORD_DEFAULT)));
});

