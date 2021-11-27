<?php
use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Members;
use Model\Dao\Softwares;
use Model\Dao\SoftwareVersions;
use Model\Dao\Updaterequests;
use Util\AdminUtil;
use Util\SoftwareUtil;
use Util\ValidationUtil;
use Util\GitHubUtil;
use Util\TwitterUtil;

// ソフトウェア情報作成・修正へ
$app->get('/admin/softwares/edit/{keyword}',function (Request $request, Response $response, $args) {
	$keyword=$args["keyword"];

    $softwares = new Softwares($this->db);
    $softwareVersions = new SoftwareVersions($this->db);

    $data = [];
	if ($keyword=="NEW"){
		$data["keyword"]="";
		$data["type"]="new";
		$data["flag"]="0";
	} else {
		$data["keyword"]=$keyword;
		$data["type"]="edit";
	}

	if($data["type"]!="new"){
		$about=$softwares->select(array("keyword"=>$keyword),"","",1);
		if($about==null){
			print("keyword is not valid");
			exit(1);
		} else {
			$data["title"]=$about["title"];
			$data["description"]=$about["description"];
			$data["features"]=$about["features"];
			$data["gitHubURL"]=$about["gitHubURL"];
			$data["staff"]=$about["staff"];
			$data["snapshotTag"]=preg_replace("[^.+releases/download/([^/]+)/.+$]","$1",$about["snapshotURL"]);
			$data["snapshotFile"]=preg_replace("[^.+releases/download/[^/]+/(.+)$]","$1",$about["snapshotURL"]);
			if($about["flag"]&FLG_HIDDEN==FLG_HIDDEN){
				$data["hidden"]="yes";
			}else{
				$data["hidden"]="no";
			}
			$data["flag"]=$about["flag"];
		}

	}
	showEditor($data,$this->db,$this->view,$response);
});

function showEditor(array $data,$db,$view,$response){
	$members=new Members($db);

	$data["members"]=$members->select(array(),"","","",true);

    // Render view
    return $view->render($response, 'admin/software/edit.twig', $data);
}

// ソフトウェア情報を検証して確認へ
$app->post('/admin/softwares/edit/{keyword}', function (Request $request, Response $response) {
	$input = $request->getParsedBody();

	$message=paramCheck($input);

	//ここまでで問題ないならgitの確認へ進む
	if($message==""){
		$input["git"]=gitCheck($input["gitHubURL"]);
		$message.=$input["git"]["message"];
		$found=false;
		$foundTag=false;
		if($message==""){
			foreach($input["git"]["latest"] as $r){
				if($r["tag_name"]==$input["snapshotTag"]){
					$foundTag=true;
					foreach($r["assets"] as $a){
						if($a["name"]==$input["snapshotFile"]){
							$found=true;
						}
					}
					if($found==false){
						$message.="snapshotのファイルが見つかりません。gitHubに事前にアップロードしたファイルの名前との一致を確認してください。";
					}
				}
			}
			if($foundTag==false){
				$message.="snapshotで指定されたタグが見つかりません。gitHubに事前にアップロードしたファイルの名前との一致を確認してください。";
			}
		}
	}
	if($message!=""){
		$data=$input;
		$data["message"]=$message;
		return showEditor($data,$this->db,$this->view,$response);
	} else {
		if($input["type"]=="edit"){
			if ($input["step"]!="confirm"){
				return showConfirm($input,$this->db,$this->view,$response);
			} else {
				$message=setEdit($input,$this->db);
				return showResultMessage($message);
			}
		}

		$data=$input;
		$data["drafts"]=$input["git"]["draft"];
		if (isset($input["step"])){
			$message.=paramCheck2($input);
			if ($message==""){
				$data["files"]=null;
				foreach ($data["drafts"] as $version){
					if ($version["tag_name"]==$data["version"]){
						$data["files"]=$version["assets"];
						$data["releaseId"]=$version["id"];
						$data["releaseUrl"]=$version["html_url"];
						break;
					}
				}
				if ($data["files"]!=null){
					if (isset($input["file"])){
						$message.=paramCheck3($input);
						$data["fileUrl"]=null;
						if ($message==""){
							foreach ($data["files"] as $file){
								if ($file["name"]==$input["file"]){
									$data["fileUrl"]=$file["browser_download_url"];
								}
							}
							if ($data["fileUrl"]!=null){
								if ($input["step"]=="confirm"){
									//登録処理
									$message=setNew($input,$this->db);
									return showResultMessage($message);
								}
								return showConfirm($data,$this->db,$this->view,$response);
							} else {
								$message.="指定されたファイルが存在しません。";
							}
						}
					}
					$data["message"]=$message;
					return showFileSelector($data,$this->db,$this->view,$response);
				} else {
					$message.="指定されたバージョンがgit上で見つかりません。";
				}
			}
		}
		$data["message"]=$message;
		return showVersionSelector($data,$this->db,$this->view,$response);
	}
});

function showVersionSelector(array $data,$db,$view,$response){
    // Render view
    return $view->render($response, 'admin/software/versionSelect.twig', $data);
}

function showFileSelector(array $data,$db,$view,$response){
    // Render view
    return $view->render($response, 'admin/software/fileSelect.twig', $data);
}

function showConfirm(array $data,$db,$view,$response){
	$data["display_features"]=explode("\n",$data["features"]);
	for($i=0;$i<count($data["display_features"]);$i++){
		$data["display_features"][$i]=explode("\\t",$data["display_features"][$i]);
	}
	$data["display_staff"] = (new Members())->select(["id"=>$data["staff"]])["name"];
    // Render view
    return $view->render($response, 'admin/software/confirm.twig', $data);
}


function paramCheck($input){
	$message="";
	if (ValidationUtil::checkParam($input,array(
		"type"=>"/^(edit)|(new)$/"
	))==false){
		$message.="不正なリクエストです。最初からやり直してください。";
	}
	if (ValidationUtil::checkParam($input,array(
		"title"=>"/^.{3,41}$/u"
	))==false){
		$message.="タイトルは3文字以上41文字以内で入力してください。";
	}
	if (ValidationUtil::checkParam($input,array(
		"keyword"=>"/^[A-Z]{3,10}$/"
	))==false){
		$message.="キーワードは半角大文字3～10字で入力してください。";
	}
	if (ValidationUtil::checkParam($input,array(
		"description"=>"/^.{15,120}$/u"
	))==false){
		$message.="概要は15～120字で入力してください。";
	}
	if (ValidationUtil::checkParam($input,array(
		"features"=>"/^(.{3,}(\t|(\\\\t)).{5,}(\r)?\n)*(.{3,}(\t|(\\\\t)).{5,})(\r*\n)*$/u"
	))==false){
		$message.="特徴は3文字以上\\t5文字以上\nの繰り返しで入力してください。";
	}
	if (ValidationUtil::checkParam($input,array(
		"snapshotTag"=>"/^[a-zA-Z0-9\\-\\._]*latest[a-zA-Z0-9\\-\\._]*$/"
	))==false){
		$message.="タグ名は半角英数および_と-のみからなり、latestという単語を含む必要があります。";
	}
	if (ValidationUtil::checkParam($input,array(
		"snapshotFile"=>"/^.{3,}\\..{3,}/"
	))==false){
		$message.="スナップショットファイル名は拡張子を含み、ファイル名・拡張子各3文字以上で入力してください。";
	}
	if (ValidationUtil::checkParam($input,array(
		"gitHubURL"=>"/[a-zA-Z0-9-_]+\\/[a-zA-Z0-9-_]+\\//"
	))==false){
		$message.="githubURLは所有者名/リポジトリ名/の形式で入力してください。";
	}
	return $message;
}

//バージョン選択画面通過後のチェック
function paramCheck2($input){
	$message="";
	if (ValidationUtil::checkParam($input,array(
		"version"=>"/^.+$/"
	))==false){
		$message.="バージョンが指定されていません。";
	}

	if(!empty($input["infoString"])){
		if(!TwitterUtil::isTweetable($input["infoString"], SoftwareUtil::makeSoftwareUrl($input["keyword"]))){
			$message .= "お知らせ文字列がツイートできる長さを超えています。";
		}
	}

	if (ValidationUtil::checkParam($input,array(
		"detailString"=>"/.{3,}/u"
	))==false){
		$message.="バージョン履歴掲載情報は３文字以上で入力してください。";
	}
	return $message;
}

//ファイル選択通過後のチェック
function paramCheck3($input){
	$message="";
	if (ValidationUtil::checkParam($input,array(
		"file"=>"/^.+$/"
	))==false){
		$message.="ファイルが指定されていません。";
	}
	return $message;
}


function gitCheck($url){
	$data=array(
		"message"=>"",
		"draft"=>array(),
		"releases"=>array(),
		"latest"=>array()
	);
	$gitData=GitHubUtil::connect("/repos/".$url."releases","GET");
	if(isset($gitData["message"])){
		$data["message"]="gitHubからエラーが返されました。".$gitData["message"];
		return $data;
	}
	foreach($gitData as $release){
		if($release["assets"]==null){
			continue;
		}
		if(strpos($release["tag_name"],"latestcommit")==false){
			if($release["draft"]){
				$data["draft"][]=$release;
			} else {
				$data["releases"][]=$release;
			}
		} else {
			$data["latest"][]=$release;
		}
	}
	return $data;
}



function setNew($input,$db){
	$updaterequests=new Updaterequests($db);
	$request = $updaterequests->select(array(
		"type"=>"new",
		"identifier"=>"".$input["keyword"]
	));

	if($request===false or $request["requester"]==$_SESSION["ID"]){
		if($input["hidden"]!="no"){
			$input["flag"]=FLG_HIDDEN;
		}else{
			$input["flag"]=0;
		}
		$no=AdminUtil::sendRequest("new", $input, $input["keyword"]);
		return "リクエストを記録し、他のメンバーに承認を依頼しました。[リクエストNo:".$no."]";
	} else {	#他人が確認したのでDB反映
		$updaterequests=new Updaterequests($db);
		$request = $updaterequests->select(array(
			"type"=>"new",
			"identifier"=>$input["keyword"]
		));
		$info=unserialize($request["value"]);

		preg_match("/v?([0-9]+)\\.([0-9]+)\\.([0-9]+)/",$info["version"],$version);

		//ソフト本体の登録
		$softData=array(
			"title"=>$info["title"],
			"keyword"=>$info["keyword"],
			"description"=>$info["description"],
			"features"=>$info["features"],
			"gitHubURL"=>$info["gitHubURL"],
			"snapshotURL"=>"https://github.com/".$info["gitHubURL"]."releases/download/".$info["snapshotTag"]."/".$info["snapshotFile"],
			"staff"=>$info["staff"],
			"flag"=>$info["flag"]
		);
		$softwares=new Softwares($db);
		$id=$softwares->insert($softData);


		$versionData=array(
			"software_id"=>$id,

			"major"=>$version[1],
			"minor"=>$version[2],
			"patch"=>$version[3],

			"hist_text"=>$info["detailString"],
			"package_URL"=>"https://github.com/".$info["gitHubURL"]."releases/download/".$info["version"]."/".$info["file"],
			"updater_URL"=>null,
			"update_min_Major"=>null,
			"update_min_minor"=>null,
			"released_at"=>date("Y-m-d"),
			"flag"=>0
		);
		//検証とドラフトのリリース
		$gitData=GitHubUtil::connect("/repos/".$info["gitHubURL"]."releases/".$info["releaseId"],"PATCH",array("draft"=>false));

		if(!empty($info["infoString"])){
			publishInformation($info["infoString"],"/software/".$info["keyword"]);
		}

		$softwareVersions = new SoftwareVersions($db);
		$softwareVersions->insert($versionData);

		AdminUtil::completeRequest($request["id"]);
		return "更新が完了しました。";
	}
}

function setEdit($input,$db){
	$updaterequests=new Updaterequests($db);
	$request = $updaterequests->select(array(
		"type"=>"edit",
		"identifier"=>"".$input["keyword"]
	));

	if($request===false or $request["requester"]==$_SESSION["ID"]){
		if($input["hidden"]!="no"){
			$input["flag"]=$input["flag"]|FLG_HIDDEN;
		}else{
			$input["flag"] = $input["flag"]&(~FLG_HIDDEN);
		}
		$no=AdminUtil::sendRequest("edit", $input, $input["keyword"]);
		return "リクエストを記録し、他のメンバーに承認を依頼しました。[リクエストNo:".$no."]";
	} else {	#他人が確認したのでDB反映
		$updaterequests=new Updaterequests($db);
		$request = $updaterequests->select(array(
			"type"=>"edit",
			"identifier"=>$input["keyword"]
		));
		$info=unserialize($request["value"]);
		$softData=array(
			"title"=>$info["title"],
			"keyword"=>$info["keyword"],
			"description"=>$info["description"],
			"features"=>$info["features"],
			"gitHubURL"=>$info["gitHubURL"],
			"snapshotURL"=>"https://github.com/".$info["gitHubURL"]."releases/download/".$info["snapshotTag"]."/".$info["snapshotFile"],
			"staff"=>$info["staff"],
			"flag"=>$info["flag"]
		);
		$softwares=new Softwares($db);
		$id=$softwares->update($softData,array("keyword"));

		AdminUtil::completeRequest($request["id"]);
		return "更新が完了しました。";
	}
}
