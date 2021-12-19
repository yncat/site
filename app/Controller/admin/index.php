<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/admin/', function (Request $request, Response $response) {

	$data = [];
	if (isset($_GET["message"])){
		$data["message"] = $_GET["message"];
	}

	$data["menu"] = [
		"リクエストの承認" => [
				"link" => "request/",
				"description" => "他のメンバーからの各種リクエストの承認、自分が出したリクエストの削除を行います。",
			],
		"ストアの注文一覧" => [
				"link" => "orders/",
				"description" => "確定済み注文の確認、振込の承認などができます。",
			],
		"新規ソフト公開" => [
				"link" => "softwares/edit/NEW",
				"description" => "GitHubでの準備が整ったソフトの公開作業を行います。",
			],

		"公開ソフト管理" => [
				"link" => "softwares/",
				"description" => "公開中ソフトウェアの上方修正・アップデート配信・緊急時のバージョン削除を行います。",
			],
		"お知らせ配信" => [
				"link" => "informations",
				"description" => "トップページに掲載するお知らせの配信を行います。",
			],
		"プロフィール修正" => [
				"link" => "profile/",
				"description" => "メンバー情報を修正します。",
			],
		"サーバログの閲覧" => [
				"link" => "logviewer/",
				"description" => "サーバ上のログファイルを閲覧します。",
			],
		"DBのバックアップ" => [
				"link" => "dbdump",
				"method" => "POST",
				"description" => "データベースのダンプファイルをDropboxに保存します。",
			],
	];

    // Render index view
	return $this->view->render($response, 'admin/index.twig', $data);
});

