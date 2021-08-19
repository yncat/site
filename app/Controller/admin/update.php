<?php
use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Softwares;
use Model\Dao\SoftwareVersions;
use Model\Dao\Updaterequests;
use util\AdminUtil;
use Util\SoftwareUtil;
use Util\GitHubUtil;


$app->get('/admin/softwares/update/{keyword}',function (Request $request, Response $response, $args) {
	$keyword=$args["keyword"];
	return versionSelect($keyword,"",array(),$this->db,$this->view,$response);
});

function versionSelect($keyword,$message,$out,$db,$view,$response){
    $data = [];
    $softwares = new Softwares($db);

	$out["keyword"]=$keyword;
	$out["type"]="update";
	$out["message"]=$message;

	$info=$softwares->getLatest($keyword)[0];
	if($info==null){
		print("keyword is not valid");
		exit(1);
	}
	$data["git"]=gitCheck($info["gitHubURL"]);
	$version=$info["major"].".".$info["minor"].".".$info["patch"];

	$out["message"].=$data["git"]["message"];
	$out["title"]=$info["title"];
	$out["drafts"]=[];


	foreach($data["git"]["draft"] as $r){
		if (SoftwareUtil::conpareVersion($r["tag_name"],$version)){
			$out["drafts"][]=$r;
		}
	}
	if(empty($out["drafts"])){
		$out["message"].="現在のバージョンより新しいドラフトリリースがありません。\n";
	}
	
	if($info["flag"]&FLG_HIDDEN==FLG_HIDDEN){
		$out["hidden"]="yes";
	}else{
		$out["hidden"]="no";
	}
	$out["flag"]=$info["flag"];
	
	return showVersionSelector($out,$db,$view,$response);
}

//function gitCheck($data) はeditのものと共通利用。
//function showVersionSelector($data) はeditのものと共通利用。


$app->post('/admin/softwares/update/{keyword}', function (Request $request, Response $response) {
	$input = $request->getParsedBody();
	$message=paramCheck2($input);
	//ここまでで問題あるならgitの確認へ進まず戻る
	if($message!=""){
		return versionSelect($input["keyword"],$message,$input,$this->db,$this->view,$response);
	}
    $softwares = new Softwares($this->db);
	$info=$softwares->select(array("keyword"=>$input["keyword"]));
	$info=gitCheck($info["gitHubURL"]);
	$message.=$info["message"];
	if($message!=""){
		return versionSelect($input["keyword"],$message,$input,$this->db,$this->view,$response);
	}
	$data=$input;
	$data["files"]=null;
	foreach ($info["draft"] as $version){
		if ($version["tag_name"]==$input["version"]){
			$data["files"]=$version["assets"];
			$data["releaseId"]=$version["id"];
			$data["releaseUrl"]=$version["html_url"];
			break;
		}
	}
	if ($data["files"]==null){
		$message.="このリリースにはファイルがありません。";
	}
	if ($message=="" && !empty($input["file"]) && !empty($input["patch"]) && $input["file"]!=$input["patch"]){
		$data["fileUrl"]=null;
		foreach($data["files"] as $file){
			if($file["name"]==$input["file"]){
				$data["fileUrl"]=$file["browser_download_url"];
			}
			if($file["name"]==$input["patch"]){
				$data["patchUrl"]=$file["browser_download_url"];
			}
		}
		if (empty($data["fileUrl"]) || empty($data["patchUrl"])){
			$message.="選択されたファイルがGitで見つかりませんでした。";
		}
	} else if ($message=="") {
		$message.="リリースファイルとパッチファイルに異なるファイルを１つずつ選択する必要があります。";
	}
	if ($message!=""){
		$data["message"]=$message;
		$data["needPatch"]=true;
		return showFileSelector($data,$this->db,$this->view,$response);
	} else {
		if($input["step"]!="confirm"){
			return showUpdateConfirm($data,$this->db,$this->view,$response);
		} else {
			return $response->withRedirect($request->getUri()->getBasePath().'/admin/request',307);
		}
	}

});

function showUpdateConfirm(array $data,$db,$view,$response){
    // Render view
    return $view->render($response, 'admin/software/confirmChange.twig', $data);
}



function setUpdate($input,$db){
	$updaterequests=new Updaterequests($db);
	$request = $updaterequests->select(array(
		"type"=>"update",
		"identifier"=>"".$input["keyword"]
	));

	if($request===false or $request["requester"]==$_SESSION["ID"]){
		$no = AdminUtil::sendRequest("update", $input, $input["keyword"]);
		return "リクエストを記録し、他のメンバーに承認を依頼しました。[リクエストNo:".$no."]";
	} else {	#他人が確認したのでDB反映
		$updaterequests=new Updaterequests($db);
		$request = $updaterequests->select(array(
			"type"=>"update",
			"identifier"=>$input["keyword"]
		));
		$info=unserialize($request["value"]);

		preg_match("/v?([0-9]+)\\.([0-9]+)\\.([0-9]+)/",$info["version"],$version);

		//ソフトIDをとってくる
		$softwares=new Softwares($db);
		$soft=$softwares->select(array("keyword"=>$info["keyword"]));
		$assets_info = GitHubUtil::connect("/repos/".$soft["gitHubURL"]."releases/".$info["releaseId"]."/assets");
		$info_assets = null;
		foreach ($assets_info as $assets){
			if($assets["name"] == $info["keyword"]."-".$info["version"]."_info.json"){
				$info_assets = $assets;
				break;
			}
		}
		if ($info_assets == null){
			throw new Exception("updater hash notfound.");
		}
		$content = GitHubUtil::get_assets($info_assets["url"]);
		$file_info = json_decode($content, true);
		$versionData=array(
			"software_id"=>$soft["id"],

			"major"=>$version[1],
			"minor"=>$version[2],
			"patch"=>$version[3],

			"hist_text"=>$info["detailString"],
			"package_URL"=>"https://github.com/".$soft["gitHubURL"]."releases/download/".$info["version"]."/".$info["file"],
			"updater_URL"=>"https://github.com/".$soft["gitHubURL"]."releases/download/".$info["version"]."/".$info["patch"],
			"updater_hash" => $file_info["patch_hash"],
			"update_min_Major"=>0,
			"update_min_minor"=>0,
			"released_at"=>date("Y-m-d"),
			"flag"=>0
		);
		$softwareVersions = new SoftwareVersions($db);
		$softwareVersions->insert($versionData);

		// お知らせ配信が必要ならば書き込み
		if(!empty($info["infoString"])){
			publishInformation($info["infoString"],"/software/".$info["keyword"]);
		}
		$ret = GitHubUtil::connect("/repos/".$soft["gitHubURL"]."releases/assets/".$info_assets["id"], "DELETE");

		//検証とドラフトのリリース
		$gitData=GitHubUtil::connect("/repos/".$soft["gitHubURL"]."releases/".$info["releaseId"],"PATCH",array("draft"=>false, "body" => $versionData["hist_text"]));
		AdminUtil::completeRequest($request["id"]);
		return "更新が完了しました。";
	}
}
