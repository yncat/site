<?php
use Slim\Http\Request;
use Slim\Http\Response;

$app->post('/admin/dbdump', function (Request $request, Response $response) {
	if (dbDump()){
		$message = "バックアップを作成しました。";
	} else {
		$message = "バックアップに失敗しました。詳細はログを読んでください。";
	}

	$data=array("message"=>$message);
	return $this->view->render($response, 'admin/request/request.twig', $data);
});
