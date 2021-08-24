<?php

use Slim\Http\Request;
use Slim\Http\Response;

use Model\Dao\Members;
use Model\Dao\Softwares;
use Util\GitHubUtil;
use Util\SlackUtil;
use Util\ValidationUtil;

$app->post('/api/tweet', function (Request $request, Response $response) {
    global $app;
    $logger = $app->getContainer()->get("logger");
    $logger->info("start");
    
    if (!velifySlackRequest($request,$logger)){
        $logger->info("slack request velification failed.");
        return $response->withJson([
            'error' => 'signature error'
        ], 403);
    }
    return $response->withJson([
        "status"=>"ok"
    ], 200);
});

function velifySlackRequest(Request $request){
    $timestamp = $request->getHeaderLine('x-slack-request-timestamp');
    $signature = $request->getHeaderLine('x-slack-signature');
    $requestBody = $request->getBody()->getContents();
    $secret = getenv("SLACK_SIGNING_SECRET");

    $sigBasestring = 'v0:' . $timestamp . ':' . $requestBody;
    $hash = 'v0=' . hash_hmac('sha256', $sigBasestring, $secret);
    return $hash === $signature;
}
