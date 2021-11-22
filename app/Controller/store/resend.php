<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Serialnumbers;
use Model\Dao\Orders;
use Util\MailUtil;
use Util\ValidationUtil;


$app->get('/store/resend', function (Request $request, Response $response) {

	return showResendPage();
});


function showResendPage($message=""){
	global $app;

	$data = $app->getContainer()->request->getParsedBody();
	$data["message"] = $message;
	return $app->getContainer()->get("view")->render($app->getContainer()->get('response'), 'store/resend.twig', $data);
}


$app->post('/store/resend', function (Request $request, Response $response) {
	$input = $request->getParsedBody();

	//必須パラメータチェック
	if(!ValidationUtil::checkParam($input,array(
		"email" => ValidationUtil::EMAIL_PATTERN,
		"name"  => "/..+/",
	))){
		showResendPage("入力された内容の形式が不正です。名前には空白文字を使用できないことに注意して、再度入力してください。");
	}

	// 入力に該当する注文を検索
	$orders = (new Orders())->getAllByEmailAndName($input["email"], $input["name"], \ORDER_STATUS_PAID);

	// 注文が存在した場合
	if ($orders){

		$order["sereals"] = [];
		$serial_numbers = new Serialnumbers();
		foreach($orders as &$order){
			$serials = $serial_numbers->getAllByOrder($order["id"]);
			foreach($serials as $serial){
				$order["serials"][] = join("-", str_split($serial["serialnumber"], 4));
			}
		}

		MailUtil::sendWithTemplate($orders[0]["email"], "シリアル番号の再送", "serial_resend.twig", ["orders" => $orders]);
	}

	return showResendPage("処理が完了しました。入力情報が購入時の情報と一致していた場合には、入力されたメールアドレス宛にシリアルキーが記載されたメールをお送りしています。メールが見つからない場合には、迷惑メールフォルダを確認してください。それでもメールが見当たらなかった場合には、入力情報が誤っていた可能性があります。");
});
