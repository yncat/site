<?php

use Model\Dao\Orders;
use Model\Dao\Products;
use Model\Dao\Stocks;
use Model\Dao\Serialnumbers;
use Model\Dao\Softwares;

function isStockOk($productId, $quantity, $apply = false, $restore = false) {
    $productsTable = new Products();
    $stocksTable = new Stocks();

    $product = $productsTable->select(["id"=> $productId]);
    if (empty($product)) {
        return false;
    }

    $stock = $stocksTable->select(["id"=> $product["stock_id"]]);
    if (empty($stock)) {
        return false;
    }

    // 処理
    $requiredStock = $product["required_stock"] * $quantity;

    // restoreのときは追加する
    if (($restore === true) && ($apply === true)) {
        $stocksTable->update([
            "id"=> $stock["id"],
            "count"=> $stock["count"] + $requiredStock
        ]);
        return true;
    }

    // 残りの在庫を確認
    if ($requiredStock > $stock["count"]) {
        return false;
    }

    // applyならば減算実行
    if ($apply === true) {
        $stocksTable->update([
            "id"=> $stock["id"],
            "count"=> $stock["count"] - $requiredStock
        ]);
    }

    return true;
}

function makeRandStr($length) {
    $str = array_merge(range('0', '9'), range('A', 'Z'));
    $r_str = null;
    for ($i = 0; $i < $length; $i++) {
        $r_str .= $str[rand(0, count($str) - 1)];
    }
    return $r_str;
}


function getSerialNumber($id, $getInfoOnly=false){
    // テーブル
    $ordersTable = new Orders();
    $productsTable = new Products();
    $serialsTable = new Serialnumbers();
    
    $order = $ordersTable->select(["id"=> $id]);
    $product = $productsTable->select(["id"=> $order["product_id"]]);
    
    $quantity = $order["quantity"] * $product["sn_quantity"];
    
    // SN発行
    if ($getInfoOnly === true) {
        $quantity = 0;
    }
    $return = [];
    for ($i = 0; $i < $quantity; $i++) {
        $sn = "";
        $count = 0;
        while ($count < 100) {
            $sn = $product["sn_prefix"]. makeRandStr(12);
            if (empty($serialsTable->select(["serialnumber"=> $sn]))) {
                break;
            }
            $count += 1;
        }
        if ($count >= 100) {
            return false;
        }
        array_push($return, $sn);
    }
    
    // DB書き込み
    foreach($return as &$v) {
        $serialsTable->insert(["serialnumber"=> $v, "order_id"=> $order["id"]]);
        $v = join("-", str_split($v, 4));
    }
    $productName = "";
    if (empty($product["name"])) {
        $softwaresTable = new Softwares();
        $productName = $softwaresTable->select(["id"=> $product["software_id"]])["title"];
    } else {
        $productName = $product["name"];
    }

    return [
        "id" => (int)$order["id"],
        "name"=> $order["name"],
        "productName"=> $productName,
        "edition"=> $product["edition"],
        "email"=> $order["email"],
        "createdAt"=> $order["created_at"],
        "totalPrice"=> $order["total_price"],
        "serialnumbers"=> $return
    ];
}
