<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Members;

$app->get('/admin/login', function (Request $request, Response $response) {
    // Render index view
    return $this->view->render($response, 'admin/login.twig');
});

//ログイン
$app->post('/admin/login', function (Request $request, Response $response) {
    $members=new Members($this->db);

	$input = $request->getParsedBody();
	$info=$members->select(array("email"=>$input["email"]));
	if($info===false){
	    return $this->view->render($response, 'admin/login.twig',array("email"=>$input["email"],"message"=>"メールアドレスまたはパスワードが誤っています。"));
	}
	if(!password_verify($input["password"],$info["password_hash"])){
		return $this->view->render($response, 'admin/login.twig',array("email"=>$input["email"],"message"=>"メールアドレスまたはパスワードが誤っています。"));
	}

	$_SESSION["ID"]=$info["id"];
	$_SESSION["updated"]=time();

    // Redirect index page
	return $response->withRedirect($request->getUri()->getBasePath()."/admin/"."?".SID);
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

