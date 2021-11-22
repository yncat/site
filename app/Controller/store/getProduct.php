<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Products;
use Model\Dao\Softwares;


// 製品情報取得
// productid がなければ、販売中の全商品リスト
$app->get("/api/store/getproduct", function (request $request, Response $response){
    // productidの存在確認
    $data = $request->getQueryParams();
    if (array_key_exists("productid", $data)) {
        $ret = getProductInfoById($data["productid"]);
        if (!empty($ret)) {
            return $response->withJson(["code"=> 200, "software"=> $ret]);
        }
    } else {
        $ret = getProductInfos();
        if (!empty($ret)) {
            return $response->withJson(["code"=> 200, "software"=> $ret]);
        }
    }
    return $response->withJson(["code"=> 400, "reason"=> "not found"]);
});


function getProductInfos(){
    // 必要なテーブル
    $productsTable = new Products();
    $softwaresTable = new Softwares();

    // 販売中の全商品リスト
    $return = [];
    $r0 = $productsTable->select([
        "start_at"=> [">=", date("Y-m-d H:i:s")],
        "end_at"=> ["<", date("Y-m-d H:i:s")]
    ], "", "ASC", 1000, true);
    $r1 = $productsTable->select([
        "start_at"=> null,
        "end_at"=> ["<", date("Y-m-d H:i:s")]
    ], "", "ASC", 1000, true);
    $r2 = $productsTable->select([
        "start_at"=> [">=", date("Y-m-d H:i:s")],
        "end_at"=> null
    ], "start_at", "DESC", 1000, true);
    $r3 = $productsTable->select([
        "start_at"=> null,
        "end_at"=> null
    ], "start_at", "DESC", 1000, true);

    
    // 販売期限がある場合
    if (is_array($r0)) {
        $return = array_merge($return, $r0);
    }
    if (is_array($r1)) {
        $return = array_merge($return, $r1);
    }
    $sortDate = [];
    foreach($return as $k=> $v) {
        $sortDate[$k] = $v["end_at"];
    }
    array_multisort($sortDate, SORT_ASC|SORT_NUMERIC, $return);

    // 販売期限なし
    if (is_array($r2)) {
        $return = array_merge($return, $r2);
    }
    if (is_array($r3)) {
        $return = array_merge($return, $r3);
    }

    foreach($return as $k=> $v) {
        if (!empty($v["software_id"])) {
            $software = $softwaresTable->select(["id"=> $v["software_id"]]);
            $return[$k]["name"] = $software["title"];
        }
        unset($return[$k]["software_id"]);
        unset($return[$k]["stock_id"]);
        unset($return[$k]["required_stock"]);
        unset($return[$k]["sn_quantity"]);
    }
    
    return $return;
}

function getProductInfoById($id){
    $productsTable = new Products();
    $softwaresTable = new Softwares();

    $product = $productsTable->select(["id"=> $id]);
    if (empty($product)) {
        return null;
    }

    if (!empty($product["software_id"])) {
        $software = $softwaresTable->select(["id"=> $product["software_id"]]);
        $product["name"] = $software["title"];
    }
    unset($product["software_id"]);
    unset($product["stock_id"]);
    unset($product["required_stock"]);
    unset($product["sn_quantity"]);

    return $product;
}
