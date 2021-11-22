<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Exception\NotFoundException;
use Model\Dao\Softwares;
use Model\Dao\SoftwareVersions;
use Model\Dao\SoftwareLinks;
use Model\Dao\Members;
use Model\Dao\Products;
use Util\SoftwareUtil;
use Util\StoreUtil;
use Util\MembersUtil;

// software/informationを除いてソフトウェア詳細表示へ
$app->get('/software/{keyword:[^(^information$)].+}', function (Request $request, Response $response, $args) {
	$keyword=$args["keyword"];

	$softwares = new Softwares();
	$softwareVersions = new SoftwareVersions();
	$members=new Members();
	$links = new SoftwareLinks();
	$products = new Products();

	$data = [];

	// ソフトウェア情報の取得
	$data["about"]=$softwares->select(array("keyword"=>$keyword),"","",1);
	if(!$data["about"]){
		throw new NotFoundException($request, $response);
	}

	//split features
	$data["about"]["features"]=explode("\n",$data["about"]["features"]);
	for($i=0;$i<count($data["about"]["features"]);$i++){
		$data["about"]["features"][$i]=explode("\\t",$data["about"]["features"][$i]);
	}

	// 公開中バージョンの取得
	$data["versions"]=$softwareVersions->select(array("software_id"=>$data["about"]["id"]),"major*1000000+minor*1000+patch","DESC",0,true);
	if(!$data["about"]){
		throw new NotFoundException($request, $response);
	}
	foreach($data["versions"] as &$version){
		SoftwareUtil::makeTextVersion($version);
		$version["hist_text"]=explode("\n",$version["hist_text"]);
	}

	// スタッフ情報の取得
	$data["staff"]=$members->select(array("id"=>$data["about"]["staff"]));
	MembersUtil::makeLinkCode($data["staff"]);

	// 関連リンク情報の取得
	$data["links"] = $links->getBySoftwareId($data["about"]["id"]);

	// 販売商品関連処理
	$data["products"] = $products->select(["software_id"=>$data["about"]["id"]],"","ASC",10,true);
	if($data["products"]){
		$data["is_paid"] = true;

		// 価格に消費税を載せる
		foreach ($data["products"] as &$product){
			$product["price"] = StoreUtil::addTax($product["price"]);
		}

		// 関連リンクを追加
		$data["links"] = array_merge(
			[
				[
					"name" => "販売・ライセンス条件",
					"url" => "/disclose/store_info",
				],
				[
					"name" => "特定商取引法に基づく表示",
					"url" => "/disclose/leagal",
				],
				[
					"name" => "オフライン登録ページ",
					"url" => "/store/manual_authorization",
				],
				[
					"name" => "シリアルキー再送ツール",
					"url" => "/store/resend",
				],
			],
			$data["links"]
		);
	} else {
		$data["is_paid"] = false;
	}

	// Render view
	return $this->view->render($response, 'software/detail.twig', $data);
});
