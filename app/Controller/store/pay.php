<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Orders;
use Model\Dao\Products;
use Util\mailUtil;
use Util\ValidationUtil;


// 支払い登録
// 成功したら注文番号とメールアドレスを返す
$app->post("/api/store/pay", function (request $request, Response $response){
    try{
        return $response->withJson(paymentProcess($request, $this->db));
    } catch(Exception $e) {
        $response->withJson([
            "code"=> 400,
            "reason"=> "request faild"
        ]);
    }
});

function paymentProcess($r, $db) {
    $data = json_decode($r->getBody(), TRUE);
    
    // 内容確認
    if ((array_key_exists("cardToken", $data) === false) || (is_string($data["cardToken"]) === false)) {
        return [
            "code"=> 400,
            "reason"=> "invalid cardToken"
        ];
    }
    if ((array_key_exists("orderId", $data) === false) || (is_int($data["orderId"]) === false) || ($data["orderId"] <= 0 )) {
        return [
            "code"=> 400,
            "reason"=> "invalid orderId"
        ];
    }
    if ((array_key_exists("email", $data) === false) || (is_string($data["email"]) === false) || (preg_match(ValidationUtil::EMAIL_PATTERN, $data["email"]) == false)) {
        return [
            "code"=> 400,
            "reason"=> "invalid email"
        ];
    }

    // 注文確認して在庫処理
    $ordersTable = new Orders();

    $order = $ordersTable->select([
        "id"=> $data["orderId"],
        "email"=> $data["email"]
    ]);
    if (empty($order)) {
        return [
            "code"=> 400,
            "reason"=> "order not found"
        ];
    }

    // 支払いと在庫
    $confirmation = "";
    if ($order["payment_type"] == \PAYMENT_TYPE["credit"]) {
        $confirmation = setOrderConfirmation($order["id"], $db, true, $data["cardToken"]);
    } else {
        $confirmation = setOrderConfirmation($order["id"], $db);
    }
    if ($confirmation !== "") {
        return ["code"=> 400, "reason"=> $confirmation];
    }
    
    // クレカのときはSN発行
    if ($order["payment_type"] == \PAYMENT_TYPE["credit"]) {
        $serialInfo = getSerialnumber($order["id"]);
        mailUtil::sendWithTemplate($serialInfo["email"], "シリアルキー発行のお知らせ", "notice_serial.twig", $serialInfo);
        return ["code"=> 200, "serialnumbers"=> $serialInfo["serialnumbers"]];
    } else {
        $serialInfo = getSerialnumber($order["id"], true);
        $serialInfo["bankName"] = \BANK_NAME;
        $serialInfo["bankBranch"] = \BANK_BRANCH;
        $serialInfo["bankAccount"] = \BANK_ACCOUNT;
        $serialInfo["bankAccountNo"] = \BANK_ACCOUNT_NO;
        $serialInfo["bankAccountOwner"] = \BANK_ACCOUNT_OWNER;
        $serialInfo["transferMax"] = date("m月d日", strtotime("+7 day"));
        $serialInfo["totalPrice"] = number_format($serialInfo["totalPrice"]);

        mailUtil::sendWithTemplate($serialInfo["email"], "代金お振込みのお願い", "request_transfer.twig", $serialInfo);
        return ["code"=> 200];
    }
}


function createCharge($token, $amount) {
    try {
        \Payjp\Payjp::setApiKey(getenv("PAYJP_SECRET"));
        $charge = \Payjp\Charge::create(array(
            "card"=> $token,
            "amount"=> $amount,
            "currency"=> "jpy",
            "capture"=> false,
            "expiry_days"=> 1
        ));
        if ((property_exists("error", $charge)) || (!empty($charge["failure_message"]))) {
            return [0, "card error"];
        }

        return [$charge["id"], null];
    } catch(\Payjp\Error\Card $e) {
        return [0, "card error"];
    } catch (\Payjp\Error\InvalidRequest $e) {
        return [0, "network error"];
    } catch (\Payjp\Error\Authentication $e) {
        return [0, "network error"];
    } catch (\Payjp\Error\ApiConnection $e) {
        return [0, "network error"];
    } catch (\Payjp\Error\Base $e) {
        return [0, "card error"];
    } catch (Exception $e) {
        return [0, "unknown error"];
    }
}

function setOrderConfirmation($id, $db, $card = false, $token=""){
    // テーブル
    $productsTable = new Products();
    $ordersTable = new Orders();

    // 注文状況の確認
    $order = $ordersTable->select(["id"=> $id]);
    if (empty($order)) {
        return "order not found";
    }
    if ($order["status"] === \ORDER_STATUS_PAID) {
        return "already paid";
    }
    $product = $productsTable->select(["id"=> $order["product_id"]]);

    // クレカ払いなら枠を確保
    $payjpRes = [null, null];
    if ($card) {
        $amount = $product["price"] * $order["quantity"] * (1 + \STORE_SETTINGS["TAX_RATE"]);
        $payjpRes = createCharge($token, $amount);
        if (!empty($payjpRes[1])) {
            return $payjpRes[1];
        }
    }

    // 在庫確認
    if (isStockOk($order["product_id"], $order["quantity"], true) === false) {
        return "no stock";
    }
    $ordersTable->update([
        "id"=> $order["id"],
        "status"=> \ORDER_STATUS_WAIT_PAY,
        "payjp_id"=> $payjpRes[0]
    ]);
    
    // いったんコミット
    $db->commit();

    if (($card === true) && (!empty($payjpRes[0]))) {
        $ch = \Payjp\Charge::retrieve($payjpRes[0]);
        $newCh = $ch->capture();
        if (!empty($newCh["paid"])) {
            $ordersTable->update([
                "id"=> $order["id"],
                "status"=> \ORDER_STATUS_PAID
            ]);
            return "";
        } else {
            isStockOk($order["product_id"], $order["quantity"], true, true);
            $ordersTable->update([
                "id"=> $order["id"],
                "status"=> \ORDER_STATUS_TMP,
                "payjp_id"=> ""
            ]);
            return "card error";
        }
    }

    return "err";
}
