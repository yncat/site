<?php

use Slim\Http\Request;
use Slim\Http\Response;

use Model\Dao\Members;
use Model\Dao\Orders;
use Model\Dao\Softwares;
use Model\Dao\TrialAuthorizationLog;
use Util\GitHubUtil;
use Util\SlackUtil;
use Util\ValidationUtil;

// レポートに表示する数値の桁数
// この桁数未満の場合はパディングされる						
const ANALYTICS_COUNT_LENGTH = 4;

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

	$json["code"] = 200;
	$json["message"] = "Started.";
	print(json_encode($json));
	ob_flush();
	flush();

	sendContributionCount();
	sendDownloadCount();
	sendSalesNotice();

	return $response;
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

// ストア関連のデイリー通知
function sendSalesNotice(){
	// 体験版起動数
	$data = (new TrialAuthorizationLog())->getAnalyticsData();
	$text = "Software trial count\n\n";
	foreach($data as $row){
		$text .= str_pad($row["name"],10," ", STR_PAD_LEFT) . " : "
			. "total "  . str_pad($row["total"],  ANALYTICS_COUNT_LENGTH," ", STR_PAD_LEFT) . "  "
			. "unique " . str_pad($row["unique"], ANALYTICS_COUNT_LENGTH," ", STR_PAD_LEFT)
			. "\n";
	}
	SlackUtil::daily($text);

	// 入金待ち注文件数
	$text = "Orders waiting for payment\n\n";
	$data = (new Orders())->countWaitingOrder();
	if (!$data){
		// 該当の注文がなければ出力しない
		return;
	}

	foreach($data as $row){
		$text .= $row["ordered_on"]."注文分 : "
			. str_pad($row["cnt"],ANALYTICS_COUNT_LENGTH," ",STR_PAD_LEFT) . "件\n";
	}
	SlackUtil::daily($text);
}
