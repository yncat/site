<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Orders;
use Model\Dao\Products;
use Model\Dao\Email_confirmations;
use Util\ValidationUtil;
use Util\mailUtil;


// 注文登録
// 成功したら注文番号とメールアドレスを返す
$app->post("/api/store/order", function (request $request, Response $response){
    try{
        return $response->withJson(setOrderFromJson($request, $this->db));
    } catch(Exception $e) {
        return $response->withJson([
            "code"=> 400,
            "reason"=> "invalid request"
        ]);
    }
});

$app->post("/api/store/setemail", function (request $request, Response $response){
    try{
        return $response->withJson(setEmailFromJson($request));
    } catch(Exception $e) {
        return $response->withJson([
            "code"=> 400,
            "reason"=> "invalid request"
        ]);
    }
});


function setOrderFromJson($request, $db){
    // DBオブジェクト
    $ordersTable = new Orders($db);
    $productsTable = new Products();
    $confirmationsTable = new Email_confirmations($db);
    
    $data = json_decode($request->getBody(), TRUE);
    
    // 内容確認
    if ((array_key_exists("name", $data) === false) || (is_string($data["name"]) === false) || (mb_strlen($data["name"]) <= 1)) {
        return [
            "code"=> 400,
            "reason"=> "invalid name"
        ];
    }
    if ((array_key_exists("productId", $data) === false) || (is_int($data["productId"]) === false) || ($data["productId"] <= 0 )) {
        return [
            "code"=> 400,
            "reason"=> "invalid productId"
        ];
    }
    if ((array_key_exists("quantity", $data) === false) || (is_int($data["quantity"]) === false) || ($data["quantity"] <= 0)) {
        return [
            "code"=> 400,
            "reason"=> "invalid quantity"
        ];
    }
    if ((array_key_exists("email", $data) === false) || (is_string($data["email"]) === false) || (preg_match("/^.+@.+\..+[a-zA-Z]$/", $data["email"]) == false)) {
        return [
            "code"=> 400,
            "reason"=> "invalid email"
        ];
    }
    if ((array_key_exists("confirmationCode", $data) === false) || (is_string($data["confirmationCode"]) === false)) {
        return [
            "code"=> 400,
            "reason"=> "invalid confirmationCode"
        ];
    }
    if ((array_key_exists("paymentType", $data) === false) || (is_string($data["paymentType"]) === false) || (array_key_exists($data["paymentType"], \PAYMENT_TYPE) === false)) {
        return [
            "code"=> 400,
            "reason"=> "invalid paymentType"
        ];
    }

    // 商品存在確認
    $product = $productsTable->select(["id"=> $data["productId"]]);
    if (empty($product)) {
        return [
            "code"=> 400,
            "reason"=> "product not found"
        ];
    }

    
    // メールアドレス認証
    $confirmationsTable->delete([
        "created_at"=> ["<=", date("Y-m-d H:i:s",strtotime("-15 minute"))]
    ]);
    $mail = $confirmationsTable->select(["email"=>$data["email"]]);
    if (empty($mail)){
        return [
            "code"=> 400,
            "reason"=> "wrong email"
        ];
    }
    if ($mail["count"] > \STORE_SETTINGS["confirmationMax"]) {
        $confirmationsTable->delete(["email"=> $data["email"]]);
        return [
            "code"=> 400,
            "reason"=> "confirmation faild"
        ];
    }
    if ($mail["code"] !== $data["confirmationCode"]) {
        $confirmationsTable->update(["count"=> $mail["count"] + 1, "id" => $mail["id"]]);
        return [
            "code"=> 400,
            "reason"=> "wrong confirmation code"
        ];
    }
    $confirmationsTable->delete(["email"=> $data["email"]]);

    // 在庫確認
    if (isStockOk($data["productId"], $data["quantity"], $db) === false) {
        return [
            "code"=> 400,
            "reason"=> "no stock"
        ];
    }

    // 合計金額を作成
    $totalPrice = $product["price"] * $data["quantity"];
    if ($data["paymentType"] == "transfer") {
        $totalPrice += \TRANSFER_FEE;
    }
    $totalPrice = (int)($totalPrice + ($totalPrice * \TAX_RATE));


    // 注文作成
    $orderId = $ordersTable->insert([
        "name"=> $data["name"],
        "email"=> $data["email"],
        "quantity"=> $data["quantity"],
        "product_id"=> $data["productId"],
        "payment_type"=> \PAYMENT_TYPE[$data["paymentType"]],
        "total_price"=> $totalPrice,
        "created_from"=> $_SERVER["REMOTE_ADDR"]
    ]);
    if (empty($orderId)) {
        return [
            "code"=> 400,
            "reason"=> "order faild"
        ];
    }

    $orderId = intval($orderId);

    // 振込の時は、payment処理へ
    if ($data["paymentType"] == "transfer") {
        $confirmation = setOrderConfirmation($orderId, $db);
        $serialInfo = getSerialnumber($orderId, true);
        $serialInfo["bankName"] = \BANK_NAME;
        $serialInfo["bankBranch"] = \BANK_BRANCH;
        $serialInfo["bankAccount"] = \BANK_ACCOUNT;
        $serialInfo["bankAccountNo"] = \BANK_ACCOUNT_NO;
        $serialInfo["bankAccountOwner"] = \BANK_ACCOUNT_OWNER;
        $serialInfo["transferMax"] = date("m月d日", strtotime("+7 day"));
        $serialInfo["totalPrice"] = number_format($serialInfo["totalPrice"]);

        mailUtil::sendWithTemplate($serialInfo["email"], "代金お振込みのお願い", "request_transfer.twig", $serialInfo);
    }

    // 成功を通知
    return [
        "code"=>200,
        "orderId"=>$orderId,
        "email"=>$data["email"]
    ];
}

function setEmailFromJson($request){
    $data = json_decode($request->getBody(), TRUE);
    
    // 内容確認
    if (ValidationUtil::checkParam($data, [
        "email"=> ValidationUtil::EMAIL_PATTERN
    ]) === false) {
        return [
            "code"=> 400,
            "reason"=> "invalid email"
        ];
    }

    // 認証テーブル登録
    $confirmationsTable = new Email_confirmations();
    $confirmationsTable->delete([
        "created_at"=> ["<=", date("Y-m-d H:i:s",strtotime("-15 minute"))]
    ]);
    $confirmationsTable->delete(["email"=> $data["email"]]);
    $code = sprintf("%06d", random_int(0,999999));
    $confirmationsTable->insert([
        "email"=> $data["email"],
        "code"=> $code
    ]);

    mailUtil::sendWithTemplate($data["email"], "認証コードのお知らせ", "email_confirmation.twig", ["code"=> $code]);
    return ["code"=> 200];
}
