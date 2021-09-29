<?php
use Slim\Http\Request;
use Slim\Http\Response;

use Util\ValidationUtil;
use Util\UrlUtil;

use Abraham\TwitterOAuth\TwitterOAuth;

$app->get('/api/registerTwitterWebhook', function (Request $request, Response $response) {
    global $app;
    $logger = $app->getContainer()->get("logger");
    $data = $request->getQueryParams();
    if(!ValidationUtil::checkParam($data,[
		"password"=>"/^".getenv("SCRIPT_PASSWORD")."$/",
	])){
        $logger->info("script password failed.");
        $json["code"] = 401;
		$json["message"] = "incorrect password!";
		return $response->withJson($json);
	}

    $name = getenv("TWITTER_ENV_NAME");
    $url = UrlUtil::toAbsUrl("api/twitterWebhook");
    $connection = new TwitterOAuth(getenv("TWITTER_API_KEY"), getenv("TWITTER_API_SECRET"), getenv("TWITTER_ACCESS_TOKEN"), getenv("TWITTER_ACCESS_TOKEN_SECRET"));
    $list_webhooks = $connection->get("account_activity/all/".$name."/webhooks");
    if($connection->getLastHttpCode() !== 200){
        return $response->withJson($list_webhooks, 500);
    }
    foreach($list_webhooks as $webhook){
        $connection->delete("account_activity/all/".$name."/webhooks/".$webhook->id);
        $logger->info("deleted webhook");
    }
    $result = $connection->post("account_activity/all/".$name."/webhooks", ["url" => $url]);
    if($connection->getLastHttpCode() !== 200){
        return $response->withJson($result, 500);
    }
    $ID = $result->id;
    $logger->info("webhook ID: ".$ID);
    $logger->info("registered webhook URL: ".$url);
    $result = $connection->post("account_activity/all/".$name."/subscriptions");
    if($connection->getLastHttpCode() === 204){
        $logger->info("twitter webhook registered");
        return $response->withJson(["message" => "success"]);
    }
    else{
        $logger->info("twitter webhook register error");
        return $response->withJson($result, 500);
    }
});
