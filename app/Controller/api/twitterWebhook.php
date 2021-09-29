<?php
use Slim\Http\Request;
use Slim\Http\Response;

use Util\SlackUtil;
use Util\TwitterUtil;
use Util\EnvironmentUtil;

$app->get('/api/twitterWebhook', function (Request $request, Response $response) {
    $param = $request->getQueryParams();
    if(!isset($param["crc_token"])){
        return $response->withJson([
            "message" => "bad parameter"
        ], 400);
    }
    $signature = hash_hmac("sha256", $param["crc_token"], getenv("TWITTER_API_SECRET"), true);
    return $response->withJson([
        "response_token" => "sha256=".base64_encode($signature)
    ]);
});

$app->post('/api/twitterWebhook', function (Request $request, Response $response) {
    global $app;
    $logger = $app->getContainer()->get("logger");
    $logger->info("received twitter request");
    if(!TwitterUtil::verifyTwitterRequest($request)){
        $logger->info("request verification failed.");
        return $response->withJson(["message" => "verification failed."], 400);
    }
    $logger->info("twitter valid request.");
    $data = $request->getParsedBody();
    $logger->debug(var_export($data, true));
    if(array_key_exists("tweet_create_events", $data)){
        process_tweet_create_event($data);
    }
    if(array_key_exists("favorite_events", $data)){
        process_favorite_event($data);
    }
    if(array_key_exists("direct_message_events", $data)){
        process_direct_message_events($data);
    }
    if(array_key_exists("follow_events", $data)){
        process_follow_events($data);
    }
    return $response->withJson([], 200);
});

function process_tweet_create_event($event){
    foreach($event["tweet_create_events"] as $tweet){
        $text = "";
        $created_user = $tweet["user"];
        $created_user_link = "<https://twitter.com/".$created_user["screen_name"]."|".$created_user["name"].">";
        $message_footer = "<https://twitter.com/".$created_user["screen_name"]."/status/".$tweet["id_str"]."|twitterで開く>";
        if(array_key_exists("retweeted_status", $tweet)){
            $text .= $created_user_link."が、リツイートしました。\n";
            $text .= $tweet["retweeted_status"]["text"] . "\n";
            $text .= $message_footer;
            SlackUtil::twitter_notify($text);
            continue;
        }
        if($tweet["in_reply_to_status_id_str"]){
            $text .= $created_user_link."が返信しました。\n";
            $text .= $tweet["text"] . "\n";
            $text .= $message_footer;
            SlackUtil::twitter_notify($text);
            continue;
        }
        if(array_key_exists("quoted_status", $tweet)){
            $text .= $created_user_link."がコメント付きでリツイートしました。\n";
            $text .= $tweet["text"]."\n";
            $text .= "引用 @" . $tweet["quoted_status"]["user"]["screen_name"].": \n";
            $text .= $tweet["quoted_status"]["text"];
            $text .= $message_footer;
            SlackUtil::twitter_notify($text);
            continue;
        }
        $text .= $created_user_link . "がツイートしました。\n";
        $text .= $tweet["text"] . "\n";
        $text .= $message_footer;
        SlackUtil::twitter_notify($text);
        continue;   
    }
}

function process_favorite_event($data){
    global $app;

    foreach($data["favorite_events"] as $event){
        $text = "";
        $created_user = $event["user"];
        $tweet = $event["favorited_status"];
        $text .= "<https://twitter.com/".$created_user["screen_name"]."|".$created_user["name"].">がいいねしました。\n";
        $text .= $tweet["text"] . "\n";
        $text .= "<https://twitter.com/".$created_user["screen_name"]."/status/".$tweet["id_str"]."|twitterで開く>";
        SlackUtil::twitter_notify($text);
    }
}

function process_direct_message_events($data){
    global $app;
    $logger = $app->getContainer()->get("logger");
    $logger->info("message_create_events");
    $my_user = TwitterUtil::getAuthorizedUserInfo();
    foreach($data["direct_message_events"] as $event){
        $text = "";
        $message = $event["message_create"];
        if($message["sender_id"] === $my_user->id_str){
            $logger->info("sent DM");
            $recipient_user = $data["users"][$message["target"]["recipient_id"]];
            $text .= "<https://twitter.com/".$recipient_user["screen_name"]."|".$recipient_user["name"].">にDMが送信されました。\n";
        }
        elseif ($message["target"]["recipient_id"] === $my_user->id_str){
            $send_user = $data["users"][$message["sender_id"]];
            $text .= "<https://twitter.com/".$send_user["screen_name"]."|".$send_user["name"].">からDMが届きました。\n";
        }
        else{
            $recipient_user = $data["users"][$message["target"]["recipient_id"]];
            $send_user = $data["users"][$message["sender_id"]];
            $text .= "<https://twitter.com/".$send_user["screen_name"]."|".$send_user["name"].">から";
            $text .= "<https://twitter.com/".$recipient_user["screen_name"]."|".$recipient_user["name"].">にDMが送信されました。\n";
        }
        $text .= $message["message_data"]["text"];
        SlackUtil::message($text);
    }
}

function process_follow_events($data){
    global $app;
    $logger = $app->getContainer()->get("logger");
    $logger->info("follow_events");
    $my_user = TwitterUtil::getAuthorizedUserInfo();
    foreach($data["follow_events"] as $event){
        $text = "";
        $target_user = $event["target"];
        $source_user = $event["source"];
        if($event["type"] === "follow"){
            if($source_user["id"] === $my_user->id_str){
                $text .= "<https://twitter.com/".$target_user["screen_name"]."|".$target_user["name"].">をフォローしました。";
            }
            elseif($target_user["id"] === $my_user->id_str){
                $text .= "<https://twitter.com/".$source_user["screen_name"]."|".$source_user["name"].">にフォローされました。";
            }
        }
        elseif($event["type"] === "unfollow"){
            $text .= "<https://twitter.com/".$target_user["screen_name"]."|".$target_user["name"].">のフォローを解除しました。";
        }
        SlackUtil::twitter_notify($text);
    }
}

