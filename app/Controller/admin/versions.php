<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Exception\NotFoundException;

use Model\Dao\Softwares;
use Model\Dao\SoftwareVersions;
use Util\AdminUtil;
use Util\GitHubUtil;
use Util\MembersUtil;
use Util\SoftwareUtil;


$app->get('/admin/softwares/{keyword:[^(^information$)].+}/versions', function (Request $request, Response $response, $args) {
	$keyword=$args["keyword"];

	$softwares = new Softwares();
	$softwareVersions = new SoftwareVersions();


	$data = [];

	$data["about"] = $softwares->select(array("keyword"=>$keyword));

	if(!$data["about"]){
		throw new NotFoundException($request, $response);
	}

	$data["versions"] = $softwareVersions->select(array("software_id"=>$data["about"]["id"]),"major*1000000+minor*1000+patch","DESC",0,true);

	// バージョンテキストとダウンロード数データの追加
	foreach($data["versions"] as &$version){
		SoftwareUtil::makeTextVersion($version);
		$version["hist_text"]=explode("\n",$version["hist_text"]);

		$tmp = explode("/", $version["package_URL"]);
		$file_name = $tmp[count($tmp)-1];
		$tmp = explode("/", $version["updater_URL"]);
		$patch_name = $tmp[count($tmp)-1];

		$tag = GitHubUtil::getTagByDownloadURL($version["package_URL"]);
		$tmp = GitHubUtil::connect("/repos/" . ($data["about"]["gitHubURL"])."releases/tags/".$tag);
		foreach($tmp["assets"] as $asset){
			if($asset["name"] === $file_name){
				$version["dl_count"] = $asset["download_count"];
			} elseif($asset["name"] === $patch_name){
				$version["patch_dl_count"] = $asset["download_count"];
			} 
		}
	}

	// Render view
	return $this->view->render($response, 'admin/software/versions.twig', $data);
});


$app->get('/admin/softwares/{keyword:[^(^information$)].+}/{version_id}/delete', function (Request $request, Response $response, $args) {
	$keyword=$args["keyword"];
	$version_id=$args["version_id"];

	return showDeleteVersionConfirm(["keyword"=>$keyword, "version_id"=>$version_id], $this->view, $request, $response);
});

function showDeleteVersionConfirm(array $data,$view,$request, $response){
	$softwares = new Softwares();
	$softwareVersions = new SoftwareVersions();

	$data["version"] = $softwareVersions->select(array("id"=>$data["version_id"]));
	if(!$data["version"]){
		throw new NotFoundException($request, $response);
	}
	SoftwareUtil::makeTextVersion($data["version"]);
	$data["version"]["hist_text"]=explode("\n",$data["version"]["hist_text"]);

	$data["about"] = $softwares->select(array("id"=>$data["version"]["software_id"]));
	if(!$data["about"] || (!is_null($data["keyword"]) && $data["keyword"] !== $data["about"]["keyword"])){
		throw new NotFoundException($request, $response);
	}

    // Render view
    return $view->render($response, 'admin/software/confirmVersionDelete.twig', $data);
}

$app->post('/admin/softwares/{keyword:[^(^information$)].+}/{version_id}/delete', function (Request $request, Response $response, $args) {
	$keyword=$args["keyword"];
	$version_id=$args["version_id"];

	// パラメータの検証
	$softwares = new Softwares();
	$softwareVersions = new SoftwareVersions();
	$about = $softwares->select(array("keyword"=>$keyword));
	if(!$about){
		throw new NotFoundException($request, $response);
	}
	$version = $softwareVersions->select(array("id"=>$version_id));
	if(!$version){
		throw new NotFoundException($request, $response);
	}
	SoftwareUtil::makeTextVersion($version);


	$no=AdminUtil::sendRequest("delete_software_version",["keyword"=>$keyword,"version_id"=>$version_id], $about["keyword"] . $version["versionText"]);
	$data["message"] ="リクエストを記録し、他のメンバーに承認を依頼しました。[リクエストNo:".$no."]";
	return $this->view->render($response, 'admin/request/request.twig', $data);
});

function ApproveDeleteVersion(array $data, int $request_id):string{
	$softwares = new Softwares();
	$softwareVersions = new SoftwareVersions();

	$data["version"] = $softwareVersions->select(array("id"=>$data["version_id"]));
	if(!$data["version"]){
		return "指定されたバージョンが存在しません。";
	}
	SoftwareUtil::makeTextVersion($data["version"]);

	$data["about"] = $softwares->select(array("id"=>$data["version"]["software_id"]));
	if(!$data["about"] || $data["keyword"] !== $data["about"]["keyword"]){
		return "データの不整合が発生しています。";
	}

	//最後の1バージョンは削除できない
	if($softwareVersions->count(["software_id"=>$data["version"]["software_id"]]) === 1){
		return "公開中のバージョンが１つしかないため、このバージョンを削除できません。";
	}

	if($softwareVersions->delete(array("id"=>$data["version_id"])) === 1){
		AdminUtil::completeRequest($request_id);
		return $data["keyword"]." Version".$data["version"]["versionText"]."を削除しました。";
	} else {
		return $data["identifier"]."の削除に失敗しました。";
	}
}
