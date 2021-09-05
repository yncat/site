<?php

use Slim\Http\Request;
use Slim\Http\Response;

use Model\Dao\Members;
use Model\Dao\Softwares;
use Util\EncryptUtil;
use Util\SlackUtil;
use Util\TwitterUtil;
use Util\UrlUtil;

$app->post('/api/tweet', function (Request $request, Response $response) {
    global $app;
    $logger = $app->getContainer()->get("logger");

    if (!SlackUtil::velifySlackRequest($request)){
        $logger->info("slack request velification failed.");
        return $response->withJson([
            'error' => 'signature error'
        ], 403);
    }

	$params = $request->getParsedBody();
    $logger->info("request is valid.");

	// リクエストの種類で分岐
	if(isset($params["command"]) && $params["command"]==="/tweet"){
		// ツイート承認要求
		$logger->info("received tweet command request");

		// リクエストユーザの状況確認
		$result = checkUser($params["user_id"]);
		if ($result){
			return $result;
		}

		//ツイートの長さチェック
		if (!TwitterUtil::isTweetable($params["text"])){
		    return $response->withJson([
				"response_type" => "ephemeral",		//"ephemeral"にすると投稿者のみになる。"in_channel"にするとチャネル全体になる
				"text" => "ツイートが長すぎるため投稿できませんでした。内容を変更して再度お試しください。"
			],200);
		} elseif (mb_strlen($params["text"])<5){
		    return $response->withJson([
				"response_type" => "ephemeral",		//"ephemeral"にすると投稿者のみになる。"in_channel"にするとチャネル全体になる
				"text" => "ツイートするには５文字以上の本文を指定する必要があります。"
			],200);
		}

		//承認要求を送信
	    return $response->withJson([
    	    "response_type" => "in_channel",		//"ephemeral"にすると投稿者のみになる
        	"text" => "ツイートを承認してください。\n\n" . $params["text"] . "\n\n起案者：" . (new Members)->getBySlackId($params["user_id"])["name"],
			"blocks" => [
				[
					"type" => "section",
					"text"=> [
						"type"=> "plain_text",
		            	"text"=> "ツイートを承認してください。\n\n" . $params["text"] . "\n\n起案者：" . (new Members)->getBySlackId($params["user_id"])["name"],
					],
				],
				[
            	    "type" => "divider"
				],
				[
					"type" => "actions",
					"elements"=>[
						[
							"type"=>"button",
							"text"=>[
								"type"=> "plain_text",
					            "text"=> "承認"
							],
							"value" => $params["user_id"] . "," . $params["text"],
							"action_id"=>"accept"
						],
						[
							"type"=>"button",
							"text"=>[
								"type"=> "plain_text",
					            "text"=> "却下"
							],
							"value" => $params["user_id"] . "," . $params["text"],
							"action_id"=>"reject"
						]
					]
				],
			]
	    ], 200);
	} elseif (isset($params["payload"])){
		//承認または否認のボタン操作

		$logger->info("recieved request from pushButton.");
		$params = json_decode($params["payload"],true);
		$logger->info(var_export($params,true));

		//パラメータを取得
		$request_uid = explode(",",$params["actions"][0]["value"])[0];
		$logger->info("起案者：" . $request_uid);
		$logger->info("レスポンスURL：" . $params["response_url"]);
		$tweet = substr($params["actions"][0]["value"], strlen($request_uid)+1);
		$logger->info("ツイート内容：" . $tweet);
		$operation = $params["actions"][0]["action_id"];
		$logger->info("操作：" . $operation);
		$operator = $params["user"]["id"];
		$logger->info("決済者：" . $operator);

		$message = checkUser($operator);
		if ($message){
			$result = SlackUtil::send(
				[
					"replace_original" => false,
					"text" => $message,
					"response_type" => "ephemeral"
				],
				$params["response_url"]);
			$logger->info(var_export($result,true));

		    return $response->withJson([
				"replace_original" => false,
				"text" => $message,
				"response_type" => "ephemeral"
			],200);
		}

		//承認・避妊で分岐
		if ($operation == "accept"){
			//起案者自身では承認できない
			if ($request_uid === $operator){
				$logger->notice("自己決済拒否");

				$result = SlackUtil::send(
					[
						"replace_original" => false,
						"text"=>"起案者自身では承認できません。",
						"response_type" => "ephemeral"
					],
					$params["response_url"]);
				$logger->info(var_export($result,true));

			    return $response->withJson([
					"replace_original" => false,
					"text" => "起案者自身では承認できません。",
					"response_type" => "ephemeral"
				],200);
			}


			$result = SlackUtil::send("ツイートしました。\n\n" . $tweet . "\n\n起案者：" . (new Members)->getBySlackId($request_uid)["name"] . "\n決済者：" . (new Members)->getBySlackId($operator)["name"], $params["response_url"]);
			$logger->info(var_export($result,true));

			TwitterUtil::tweet($tweet);

		    return $response->withJson([
				"replace_original" => true,
				"text" => "ツイートしました。\n\n" . $tweet . "\n\n起案者：" . (new Members)->getBySlackId($request_uid)["name"]. "\n決済者：" . (new Members)->getBySlackId($operator)["name"]
			],200);
		} else {
			$result = SlackUtil::send("ツイートは却下されました。\n\n" . $tweet . "\n\n起案者：" . (new Members)->getBySlackId($request_uid)["name"] . "\n決済者：" . (new Members)->getBySlackId($operator)["name"], $params["response_url"]);
			$logger->info(var_export($result,true));

		    return $response->withJson([
				"replace_original" => true,
				"text" => "ツイートは却下されました。\n\n" . $tweet . "\n\n起案者：" . (new Members)->getBySlackId($request_uid)["name"] . "\n決済者：" . (new Members)->getBySlackId($operator)["name"]
			],200);
		}
	}

	//その他の形式には非対応
	return $response->withJson([
		"message" => "bad request"
	],400);
});

// 指定されたSlackIDのメンバーが機能を利用できない場合はエラーメッセージを返す
function checkUser(string $id){
    global $app;
    $logger = $app->getContainer()->get("logger");
	$logger->info("come");
	$user = (new Members)->getBySlackId($id);
	$logger->info(var_export($user,true));
	if (!$user){
		return "この機能を利用するには、以下のリンクをクリックし、ラボの登録者とSlackのアカウントの関連付けを行ってください。\n\n<" . UrlUtil::toAbsUrl("admin/login?action=slack&key=" . EncryptUtil::encrypt($id."#".(time()+3600))) . "|登録するには、このリンクからログインしてください>";
	}
	if (!(new Softwares)->hasOfficialSoftware($user["id"])){
		return "この機能を利用するには、バージョン1.0.0以上のソフトウェア１つ以上の主担当メンバーとして登録されている必要があります。";
	}
	return null;
}
