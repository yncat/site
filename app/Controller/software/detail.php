<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Softwares;
use Model\Dao\SoftwareVersions;
use Model\Dao\Members;
use Util\SoftwareUtil;
use Util\MembersUtil;

$app->get('/software/{keyword}', function (Request $request, Response $response, $args) {
	$keyword=$args{"keyword"};

    $softwares = new Softwares($this->db);
    $softwareVersions = new SoftwareVersions($this->db);
	$members=new Members($this->db);


    $data = [];

	$data["about"]=$softwares->select(array("keyword"=>$keyword),"","",1);

	$data["versions"]=$softwareVersions->select(array("software_id"=>$data["about"]["id"]),"released_at","DESC",0,true);
	foreach($data["versions"] as &$version){
		SoftwareUtil::makeTextVersion($version);
	}

	setlocale(LC_CTYPE,"ja_JP.UTF-8");	//windows�ł̃e�X�g���s�ɕK�v�B\t��CP932���[�h�ł͔F������Ȃ��ꍇ������B
	$f=fopen("viewParts/SoftwareFeatures/".$data["about"]["keyword"].".tsv","r");
	$data["features"]=array();
	while($feature=fgetcsv($f,0,"\t")){
		$data["features"][]=$feature;
	}
	fclose($f);

	$data["staff"]=$members->select(array("id"=>$data["about"]["staff"]));
	MembersUtil::makeLinkCode($data["staff"]);

    // Render view
    return $this->view->render($response, 'software/detail.twig', $data);
});

