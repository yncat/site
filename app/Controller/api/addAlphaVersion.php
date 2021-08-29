<?php

use Slim\Http\Request;
use Slim\Http\Response;

use Model\Dao\CommitHash;
use Model\Dao\Softwares;
use Model\Dao\SoftwareAlphaVersions;
use Util\GitHubUtil;
use Util\SoftwareUtil;
use Util\ValidationUtil;

$app->get('/api/addAlphaVersion', function (Request $request, Response $response) {

	$softwares = new softwares($this->db);
	$softwareAlphaVersions = new SoftwareAlphaVersions($this->db);
	$commitHashTable = new CommitHash($this->db);

	$data = $request->getQueryParams();

	//必須パラメータチェック
	if(!ValidationUtil::checkParam($data,array(
		"repo_name"=>"|^.*/.*$|",
		"version"=>"/^[0-9]+\\.[0-9]+\\.[0-9]+$/",
		"commit_hash"=>"/^[a-f0-9]{40}$/",
		"password"=>"/^".getenv("SCRIPT_PASSWORD")."$/",
	))){
		$json["code"] = 400;
		$json["message"] = "Required parameter not found or password is not correct.";
		return $response->withJson($json);
	}

	if (SoftwareUtil::conpareVersion("2000.0.0",$data["version"])){
		//Ver2000未満は正式版
		$json["code"] = 400;
		$json["message"] = "alpha version must be grater than 2000.0.0";
		return $response->withJson($json);
	}

	$software = $softwares->select(["gitHubURL"=>$data["repo_name"]."/"]);
	if($software==null){
		$json["code"] = 404;
		$json["message"] = "Requested repo_name is not registed.";
		return $response->withJson($json);
	}

	$commit = GitHubUtil::connect("/repos/".$data["repo_name"]."/commits/".$data["commit_hash"]);
	if(isset($commit["documentation_url"])){
		$json["code"] = 404;
		$json["message"] = "Requested repo_name is not registed.";
		return $response->withJson($json);
	}

	//パッチハッシュの取得
	$tag = GitHubUtil::getTagByDownloadURL($software["snapshotURL"]);
	$assets = GitHubUtil::connect("/repos/".$software["gitHubURL"]."releases/tags/".$tag);
	$info = null;
	if(!isset($assets["assets"])){
		$json["code"] = 500;
		$json["message"] = "Release not found.";
		return $response->withJson($json);
	}
	$assets = $assets["assets"];

	$baseFileName = preg_replace("|^.+download/[^/]+/([^/]+)\\.[a-zA-Z0-9]+$|","$1",$software["snapshotURL"]);
	foreach ($assets as $asset){
		if($asset["name"] == $baseFileName."_info.json"){
			$info = $asset;
			break;
		}
	}

	if ($info === null){
		$json["code"] = 500;
		$json["message"] = "Release file ".$baseFileName."_info.json Not found.";
		return $response->withJson($json);
	}

	$info = GitHubUtil::get_assets($info["browser_download_url"]);
	$info = json_decode($info, true);

	//登録済みハッシュ一覧にInsert
	$hist = [$commit["commit"]["message"]];
	try {
		$commitHashTable->insert(["hash"=>$commit["sha"]]);
	} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $e){
		//キー重複＝重複登録でエラー
		$json["code"] = 400;
		$json["message"] = "Already registered.";
		return $response->withJson($json);
	}

	//履歴の生成
	$parents = getParents($commit, $commitHashTable);
	$cnt = 0;
	$MAX_DEPTH = 10;
	while($parents && $cnt < $MAX_DEPTH){
		$tmp = GitHubUtil::connect("/repos/".$data["repo_name"]."/commits/".array_shift($parents));
		array_unshift($hist,$tmp["commit"]["message"]);
		$parents = array_merge($parents,getParents($tmp,$commitHashTable));
		$cnt+=1;
	}
	if ($cnt === $MAX_DEPTH){
		array_push($hist,"その他にも更新点があります。");
	}
	$hist = implode("\n",$hist);

	$softwareAlphaVersions->insert([
		"software_id" => $software["id"],
		"major" => explode(".",$data["version"])[0],
		"minor" => explode(".",$data["version"])[1],
		"patch" => explode(".",$data["version"])[2],
		"hist_text" => $hist,
		"package_URL" => $software["snapshotURL"],
		"updater_URL" => substr($software["snapshotURL"],0,-4)."patch.zip",
		"updater_hash" => $info["patch_hash"],
		"update_min_Major" => 0,
		"update_min_minor" => 0,
		"released_at" => $info["released_date"],
		"flag" => 0,
	]);

	$json["code"] = 201;
	$json["message"] = "Successfully registered.";
	return $response->withJson($json);
});


function getParents($commit, $table){
	$result = [];
	foreach($commit["parents"] as $parent){
		if ($table->exist(["hash"=>$parent["sha"]])){
			continue;
		}
		$table->insert(["hash"=>$parent["sha"]]);
		$result[] = $parent["sha"];
	}
	return $result;
}