<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Softwares;
use Model\Dao\SoftwareVersions;
use Model\Dao\Members;
use Util\SoftwareUtil;
use Util\MembersUtil;
use Util\GitHubUtil;


$app->get('/admin/softwares/{keyword}', function (Request $request, Response $response, $args) {
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

	//split features
	$data["about"]["features"]=explode("\n",$data["about"]["features"]);
	for($i=0;$i<count($data["about"]["features"]);$i++){
		$data["about"]["features"][$i]=explode("\\t",$data["about"]["features"][$i]);
	}

	$data["staff"]=$members->select(array("id"=>$data["about"]["staff"]));
	MembersUtil::makeLinkCode($data["staff"]);

	$repositoryName=substr($data["about"]["gitHubURL"],19);
	$gitData=GitHubUtil::connect("/repos/".$repositoryName."releases","GET");
	foreach($gitData as $release){
		if(strpos($release["tag_name"],"latestcommit")==false){
			if($release["draft"]){
				$data["draft"][]=$release;
			} else {
				$data["release"][]=$release;
			}
		} else {
			$data["latest"][]=$release;
		}
	}

    // Render view
    return $this->view->render($response, 'admin/detail.twig', $data);
});

