<?php

use Slim\Http\Request;
use Slim\Http\Response;

use Model\Dao\members;
use Model\Dao\Softwares;
use Util\GitHubUtil;
use Util\SlackUtil;
use Util\ValidationUtil;

$app->get('/api/dailyBatch', function (Request $request, Response $response) {
	$data = $request->getQueryParams();

	//必須パラメータチェック
	if(!ValidationUtil::checkParam($data,[
		"password"=>"/^".getenv("SCRIPT_PASSWORD")."$/",
	])){
		$json["code"] = 401;
		$json["message"] = "Authorization failed.";
		return $response->withJson($json);
	}

	sendContributionCount();
	sendDownloadCount();
});

function sendContributionCount(){
	$members = new members();

	// ユーザ一覧と昨日の日付の取得、送信用文字列の用意
	$users = array_column($members->select([], "id", "ASC", 1000, true), "github","name");
	$yesterday = date("Y-m-d",time()-86400);
	$text = "Contributions counts on $yesterday\n\n";

	foreach($users as $name=>$id){
		$text .= "$name: " . GitHubUtil::getContributionCountByUser($id,$yesterday) . "\n";
	}
	SlackUtil::daily($text);
}

function sendDownloadCount(){
	$softwares = new softwares();
	$text = "Software download count\n\n";

    $softwares=$softwares->getLatest(null, FLG_HIDDEN);
	foreach($softwares as &$software){
		$tag = GitHubUtil::getTagByDownloadURL($software["package_URL"]);
		$data = GitHubUtil::connect("/repos/".$software["gitHubURL"]."releases/tags/".$tag);
		foreach($data["assets"] as $asset){
			$text .= $asset["name"] . ":" . $asset["download_count"] . "\n";
		}
		$text .= "\n";
	}
	SlackUtil::daily($text);
}
