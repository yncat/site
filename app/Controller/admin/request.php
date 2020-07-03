<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\members;
use Model\Dao\Updaterequests;

require_once "profile.php";

$app->get('/admin/request', function (Request $request, Response $response) {
	$members=new Members($this->db);
	$updaterequests=new Updaterequests($this->db);

	$data=array();
	$data["requests"]=$updaterequests->select(array(),"","",100000,true);
	foreach($data["requests"] as &$request){
		$request["requester"]=$members->select(array("id"=>$request["requester"]))["name"];
		if($request["type"]==="publicInformation"){
			$request["title"]=$request["requester"]."の公開プロフィール変更";
		}
	}
	// Render index view
	return $this->view->render($response, 'admin/requestIndex.twig', $data);
});

$app->post('/admin/request', function (Request $request, Response $response) {
	$input = $request->getParsedBody();
	$message="";
	if(!empty($input)){
		if(!empty($input["type"])){
			if($input["type"]==="publicInformation"){
				if(profileParamCheck($input)==""){
					$message=setProfile($input,$this->db);
				}
			}
		}
	}
	if($message===""){
		$message="不正なリクエストのため、処理を中止しました。";
	}
	$data=array("message"=>$message);
	return $this->view->render($response, 'admin/request.twig', $data);
});

$app->post('/admin/request/request', function (Request $request, Response $response) {
	$input = $request->getParsedBody();
	$message="";
	if(!empty($input)){
		if(!empty($input["type"])){
			if($input["type"]==="publicInformation"){
				if(profileParamCheck($input)==""){
					$message=setProfile($input,$this->db);
				}
			}
		}
	}
	if($message===""){
		$message="不正なリクエストのため、処理を中止しました。";
	}
	$data=array("message"=>$message);
	return $this->view->render($response, 'admin/request.twig', $data);
});


$app->get('/admin/request/{id}', function (Request $request, Response $response,$args) {
	$id=$args{"id"};
	$updaterequests=new Updaterequests($this->db);
	$info=$updaterequests->select(array("id"=>$id));
	$data=unserialize($info["value"]);
	$message="";
	if($info!==false){
		if($info["type"]==="publicInformation"){
			return showProfileConfirm($data,$this->db,$this->view,$response,"");
		}
	}
	if($message===""){
		$message="不正なリクエストのため、処理を中止しました。";
	}
	$data=array("message"=>$message);
	return $this->view->render($response, 'admin/request.twig', $data);
});
