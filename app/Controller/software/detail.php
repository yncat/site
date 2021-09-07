<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Exception\NotFoundException;
use Model\Dao\Softwares;
use Model\Dao\SoftwareVersions;
use Model\Dao\SoftwareLinks;
use Model\Dao\Members;
use Util\SoftwareUtil;
use Util\MembersUtil;

// software/informationを除いてソフトウェア詳細表示へ
$app->get('/software/{keyword:[^(^information$)].+}', function (Request $request, Response $response, $args) {
	$keyword=$args["keyword"];

	$softwares = new Softwares();
	$softwareVersions = new SoftwareVersions();
	$members=new Members();
	$links = new SoftwareLinks();

	$data = [];

	$data["about"]=$softwares->select(array("keyword"=>$keyword),"","",1);

	if(!$data["about"]){
		throw new NotFoundException($request, $response);
	}
	//split features
	$data["about"]["features"]=explode("\n",$data["about"]["features"]);
	for($i=0;$i<count($data["about"]["features"]);$i++){
		$data["about"]["features"][$i]=explode("\\t",$data["about"]["features"][$i]);
	}

	$data["versions"]=$softwareVersions->select(array("software_id"=>$data["about"]["id"]),"major*1000000+minor*1000+patch","DESC",0,true);
	foreach($data["versions"] as &$version){
		SoftwareUtil::makeTextVersion($version);
		$version["hist_text"]=explode("\n",$version["hist_text"]);
	}

	$data["staff"]=$members->select(array("id"=>$data["about"]["staff"]));
	MembersUtil::makeLinkCode($data["staff"]);

	$data["links"] = $links->getBySoftwareId($data["about"]["id"]);

	// Render view
	return $this->view->render($response, 'software/detail.twig', $data);
});
