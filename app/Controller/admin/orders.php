<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Orders;
use Model\Dao\Softwares;
use Model\Dao\Products;
use Model\Dao\Updaterequests;
use Util\ValidationUtil;
use Util\AdminUtil;
use Util\mailUtil;
use Util\SlackUtil;

// 注文一覧画面表示
$app->get('/admin/orders/',function (Request $request, Response $response, $args) {
	showOrdersList($this->view,$response);
});

function showOrdersList($view,$response){
	$data=[];
	$data["waiting_orders"] = [];
	$ordersTable = new Orders();


	// 注文一覧を取得
	$orders = ($ordersTable->getAll());
	foreach($orders as $order){
		$order = array_merge($order, getStringData($order));
		if ($order["status"] == \ORDER_STATUS_WAIT_PAY){
			array_push($data["waiting_orders"],$order);
		} else if ($order["status"] == \ORDER_STATUS_PAID){
			if(!isset($data["orders"][$order["confirm_year"]][$order["confirm_month"]])){
				$data["orders"][$order["confirm_year"]][$order["confirm_month"]] = [];
			}
			array_push($data["orders"][$order["confirm_year"]][$order["confirm_month"]],$order);
		}
	}
	// Render view
    return $view->render($response, 'admin/orders/index.twig', $data);
}

$app->get('/admin/orders/{order_id}',function (Request $request, Response $response, $args) {
	return showOrderInfo($args["order_id"], $this->view, $response);
});

function showOrderInfo($orderId, $view, $response){
	$data = getOrderInfo($orderId);

    // Render view
    return $view->render($response, 'admin/orders/order.twig', $data);
}

// 注文情報取得
function getOrderInfo($orderId){
	$ordersTable = new Orders();
	$productsTable = new Products();
	$order = $ordersTable->select(["id"=> $orderId]);
	$product = $productsTable->select(["id"=> $order["product_id"]]);
	$data = [
		"id"=> $orderId,
		"orderId"=> $orderId,
		"name"=> $order["name"],
		"email"=> $order["email"],
		"quantity"=> $order["quantity"],
		"totalPrice"=> $order["total_price"],
		"createdAt"=> $order["created_at"],
		"edition"=> $product["edition"],
		"price"=> $product["price"],
	];
	if (!empty($product["name"])) {
		$data["productName"] = $product["name"];
	} else {
		$softwaresTable = new Softwares();
		$software = $softwaresTable->select(["id"=> $product["software_id"]]);
		$data["productName"] = $software["title"];
	}
	$data = array_merge($data,getStringData($order));
	if (($order["payment_type"] == \PAYMENT_TYPE["transfer"]) && ($order["status"] == \ORDER_STATUS_WAIT_PAY)) {
		$data["needConfirm"] = true;
	} else {
		$data["needConfirm"] = false;
	}
	return $data;
}

function getStringData($order){
	$data = [];
	if ($order["payment_type"] == \PAYMENT_TYPE["credit"]) {
		$data["payment_type_text"] = "カード";
	} else {
		$data["payment_type_text"] = "振込";
	}
	if ($order["status"] == \ORDER_STATUS_WAIT_PAY) {
		$data["status_text"] = "支払待ち";
	} else if ($order["status"] == \ORDER_STATUS_PAID) {
		$data["status_text"] = "処理済";
	} else {
		$data["status_text"] = "未確定";
	}
	return $data;
}

// 振込承認処理確認
$app->post('/admin/orders/order', function (Request $request, Response $response) {
	$input = $request->getParsedBody();
	if ($input["type"] === "confirmTransfer") {
		if($input["step"]==="edit"){
			return showTransferConfirm($input,"confirm",$this->view,$response);
		}else if($input["step"]==="confirm"){
			return setConfirmTransferRequest($input,$this->db,$this->view,$response, $request);
		}
	} else if ($input["type"] === "orderDelete") {
		if($input["step"]==="edit"){
			return showOrderDelete($input,"confirm",$this->view,$response);
		}else if($input["step"]==="confirm"){
			return setOrderDeleteRequest($input,$this->db,$this->view,$response, $request);
		}
	}
});


// 振込承認確認画面($step="confirm":リクエスト前, $step="approve":配信確定前)
function showTransferConfirm(array $data,$step,$view,$response){
	$data["step"] = $step;
	$data = array_merge($data, getOrderInfo((int)$data["orderId"]));
	
	// Render view
    return $view->render($response, 'admin/orders/transferConfirm.twig', $data);
}

// 注文削除確認画面($step="confirm":リクエスト前, $step="approve":削除確定前)
function showOrderDelete(array $data,$step,$view,$response){
	$data["step"] = $step;
	$data = array_merge($data, getOrderInfo((int)$data["orderId"]));
	
	// Render view
    return $view->render($response, 'admin/orders/order_delete.twig', $data);
}

// 注文内容（array）検証
function ordersCheck(array $data){
	$message = "";
	if ((empty($data["orderId"])) || (!is_numeric($data["orderId"])) || ($data["orderId"] == "0")) {
		$message.="注文番号が不正です。";
	}
	$ordersTable = new Orders();
	$order = $ordersTable->select(["id"=> $data["orderId"]]);
	if (empty($order)) {
		$message.= "注文がありません。";
	}
	if ($data["type"] === "orderDelete") {
		if ((int)$order["status"] === \ORDER_STATUS_PAID) {
			$message .= "確定済みの注文は削除できません。";
		}
	}
	if ($data["type"] === "confirmTransfer") {
		if (((int)$order["flag"] & \ORDER_FLAG_DELETED) === \ORDER_FLAG_DELETED) {
			$message .= "削除済みの注文の振込承認をすることはできません。";
		}
		if ((int)$order["status"] === \ORDER_STATUS_PAID) {
			$message .= "この注文は確定済みです。";
		}
	}
	return $message;
}

// 振込注文確定リクエスト登録
function setConfirmTransferRequest(array $data,$db,$view,$response, $request){
	$check = ordersCheck($data);
	if ($check === "") {
		$no=AdminUtil::sendRequest("confirmTransfer",$data, $data["orderId"]);
		$message = "リクエストを記録し、他のメンバーに承認を依頼しました。[リクエストNo:".$no."]";
	} else {
		$message = $check;
	}
	return showResultMessage($message);
}

// 確定
function setConfirmTransferApprove(array $data,$db,$view,$response){	#本人以外のリクエストなので確定してDB反映
	$updaterequests=new Updaterequests($db);
	$request = $updaterequests->select(array(
		"id"=>$data["requestId"],
		"type"=>"confirmTransfer"
	));
	$confirmTransfer=unserialize($request["value"]);
	confirmTransfersOrder($confirmTransfer["orderId"]);
	AdminUtil::completeRequest($data["requestId"]);
	$price = (new Orders())->select(["id"=>$confirmTransfer["orderId"]])["total_price"];
	SlackUtil::sales("銀行振込による注文の入金が承認されました。\n\n決済金額：" . $price . "円");
	return "更新が完了しました。";
}



function confirmTransfersOrder($orderId){
	$ordersDB=new Orders();
	$ordersDB->update([
		"id"=> $orderId,
		"status"=> \ORDER_STATUS_PAID
	]);
	$serialInfo = getSerialnumber($orderId);
	mailUtil::sendWithTemplate($serialInfo["email"], "シリアルキー発行のお知らせ", "notice_serial.twig", $serialInfo);
}


// 注文削除確定リクエスト登録
function setOrderDeleteRequest(array $data,$db,$view,$response, $request){
	$check = ordersCheck($data);
	if ($check === "") {
		$no=AdminUtil::sendRequest("orderDelete",$data, $data["orderId"]);
		$message = "リクエストを記録し、他のメンバーに承認を依頼しました。[リクエストNo:".$no."]";
	} else {
		$message = $check;
	}
	return showResultMessage($message);
}

// 確定
function setOrderDeleteApprove(array $data,$db,$view,$response){	#本人以外のリクエストなので確定してDB反映
	$updaterequests=new Updaterequests();
	$request = $updaterequests->select(array(
		"id"=>$data["requestId"],
		"type"=>"orderDelete"
	));
	$orderDelete=unserialize($request["value"]);
	// 削除
	$orderDB = new Orders();
	$orderInfo = getOrderInfo($orderDelete["orderId"]);
	$orderDB->setDeletedById($orderDelete["orderId"]);
	mailUtil::sendWithTemplate($orderInfo["email"], "注文削除のお知らせ", "notice_order_delete.twig", $orderInfo);
	AdminUtil::completeRequest($data["requestId"]);
	return "更新が完了しました。";
}
