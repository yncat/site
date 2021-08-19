<?php
use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Updaterequests;
use Util\AdminUtil;

require_once "profile.php";
require_once "edit.php";

$app->get('/admin/request', function (Request $request, Response $response) {
	$updaterequests=new Updaterequests($this->db);

	$data=array();
	$data["requests"]=$updaterequests->select(array(),"","",100000,true);
	foreach($data["requests"] as &$request){
		$request["title"]=AdminUtil::makeRequestTitle($request);
	}
	// Render index view
	return $this->view->render($response, 'admin/request/index.twig', $data);
});

// 更新リクエスト送信処理
$app->post('/admin/request', function (Request $request, Response $response) {
	$input = $request->getParsedBody();
	$message="";
	if(!empty($input)){
		if(!empty($input["type"])){
			if($input["type"]==="publicInformation"){
				if(profileParamCheck($input)==""){
					$message=setProfile($input,$this->db);
				}
			} else if ($input["type"]=="new"){
				$check=paramCheck($input);
				$check.=paramCheck2($input);
				$check.=paramCheck3($input);
				if ($check==""){
					$message=setNew($input,$this->db);
				}
			} else if ($input["type"]=="edit"){
				$check=paramCheck($input);
				if ($check==""){
					$message=setEdit($input,$this->db);
				}
			} else if ($input["type"]=="update"){
				$check=paramCheck2($input);
				$check.=paramCheck3($input);
				//TODO:patchのチェックする
				if ($check==""){
					$message=setUpdate($input,$this->db);
				}
			}
		}
	}
	if($message===""){
		$message="不正なリクエストのため、処理を中止しました。";
	}
	$data=array("message"=>$message);
	$data["topPageUrl"]=$request->getUri()->getBasePath()."/admin/?".SID;
	return $this->view->render($response, 'admin/request/request.twig', $data);
});

// 更新リクエスト承認処理
$app->post('/admin/request/{id}/request', function (Request $request, Response $response) {
	$input = $request->getParsedBody();
	$message="";
	if(!empty($input)){
		if(!empty($input["type"])){
			if($input["type"]==="publicInformation"){
				if(profileParamCheck($input)==""){
					$message=setProfile($input,$this->db);
				}
			}
			if($input["type"]==="new"){
				$check=paramCheck($input);
				$check.=paramCheck2($input);
				$check.=paramCheck3($input);
				if ($check==""){
					$message=setNew($input,$this->db);
				}
			}
			if ($input["type"]=="edit"){
				$check=paramCheck($input);
				if ($check==""){
					$message=setEdit($input,$this->db);
				}
			}
			if($input["type"]==="update"){
				$check=paramCheck2($input);
				$check.=paramCheck3($input);
				if ($check==""){
					$message=setUpdate($input,$this->db);
				}
			}
			if($input["type"]==="informations"){
				$check=informationsCheck($input);
				if($check===""){
					$message=setInformationsApprove($input,$this->db,$this->view,$response);
				}
			}
		}
	}
	if($message===""){
		$message="不正なリクエストのため、処理を中止しました。";
	}
	$data=array("message"=>$message);
	$data["topPageUrl"]=$request->getUri()->getBasePath()."/admin/?".SID;
	return $this->view->render($response, 'admin/request/request.twig', $data);
});

// 更新リクエスト確認URLへ
$app->post('/admin/request/{id}/', function (Request $request, Response $response,$args) {
	$id=$args["id"];
	return $response->withRedirect($request->getUri()->getBasePath().'/admin/request/'.$id.'/request',307);
});

// 更新リクエスト確認および削除確認画面表示
$app->get('/admin/request/{id}/', function (Request $request, Response $response,$args) {
	$id=$args["id"];
	$updaterequests=new Updaterequests($this->db);
	$info=$updaterequests->select(array("id"=>$id));
	$data=unserialize($info["value"]);

	$message="";
	if($info!==false){
		if($info["requester"]==$_SESSION["ID"]){
			// 自分のリクエストならば削除へ
			return deleteRequestConfirm($info,$this->db,$this->view,$response,"");
		}

		if($info["type"]==="publicInformation"){
			return showProfileConfirm($data,$this->db,$this->view,$response,"");
		}
		if($info["type"]==="new" || $info["type"]==="edit"){
			return showNewConfirm($data,$this->db,$this->view,$response,"");
		}
		if($info["type"]==="update"){
			return showUpdateConfirm($data,$this->db,$this->view,$response,"");
		}
		if($info["type"]==="informations"){
			$data["requestId"]=$id;
			return showInformationsConfirm($data,"approve",$this->view,$response,"");
		}
	}
	if($message===""){
		$message="不正なリクエストのため、処理を中止しました。";
	}
	$data=array("message"=>$message);
	$data["topPageUrl"]=$request->getUri()->getBasePath()."/admin/?".SID;
	return $this->view->render($response, 'admin/request/request.twig', $data);
});

// 更新リクエスト削除処理
$app->get('/admin/request/{id}/delete', function (Request $request, Response $response,$args) {
	$id=$args["id"];
	$updaterequests=new Updaterequests($this->db);
	$info=$updaterequests->select(array("id"=>$id));

	if($info!==false && $_SESSION["ID"]==$info["requester"]){
		AdminUtil::completeRequest($id, AdminUtil::COMPLETE_TYPE_SELF_DELETED);
		$message="削除しました。";
	} else {
		$message="不正なリクエストです。";
	}

	$data=array("message"=>$message);
	$data["topPageUrl"]=$request->getUri()->getBasePath()."/admin/?".SID;
	return $this->view->render($response, 'admin/request/request.twig', $data);
});

// 更新リクエスト削除確認表示
function deleteRequestConfirm(array $data,$db,$view,$response,$message=""){
	$data["title"]=AdminUtil::makeRequestTitle($data);

	// Render view
    return $view->render($response, 'admin/request/delete.twig', $data);
}
