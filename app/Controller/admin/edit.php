<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Softwares;
use Model\Dao\SoftwareVersions;
use Model\Dao\Members;
use Util\SoftwareUtil;
use Util\ValidationUtil;
use Util\MembersUtil;
use Util\GitHubUtil;


$app->get('/admin/softwares/edit/{keyword}',function (Request $request, Response $response, $args) {
	$keyword=$args{"keyword"};

    $softwares = new Softwares($this->db);
    $softwareVersions = new SoftwareVersions($this->db);

    $data = [];
	if ($keyword=="NEW"){
		$data["keyword"]="";
		$data["type"]="new";
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
			$data["gitHubURL"]=substr($about["gitHubURL"],19);
			$data["staff"]=$about["staff"];
			$data["snapshotTag"]=preg_replace("[^.+releases/download/([^/]+)/.+$]","$1",$about["snapshotURL"]);
			$data["snapshotFile"]=preg_replace("[^.+releases/download/[^/]+/(.+)$]","$1",$about["snapshotURL"]);
		}

		setlocale(LC_CTYPE,"ja_JP.UTF-8");
		$f=fopen("viewParts/SoftwareFeatures/".$data["keyword"].".tsv","r");
		$data["features"]="";
		while($feature=fgetcsv($f,0,"\t")){
			$data["features"].=$feature[0]."\\t".$feature[1]."\n";
		}
		fclose($f);
	}
	showEditor($data,$this->db,$this->view,$response);
});

function showEditor(array $data,$db,$view,$response){
	$members=new Members($db);

	$data["members"]=$members->select(array(),"","","",true);

    // Render view
    return $view->render($response, 'admin/edit.twig', $data);
}

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

	//最終処理結果
	if($message!=""){
		$data=$input;
		$data["message"]=$message;
		return showEditor($data,$this->db,$this->view,$response);
	} else {
		return showVersionSelector($imput);
	}
});




function paramCheck($input){
	$message="";
	if (ValidationUtil::checkParam($input,array(
		"type"=>"/^(edit)|(new)$/"
	))==false){
		$message.="不正なリクエストです。最初からやり直してください。";
	}
	if (ValidationUtil::checkParam($input,array(
		"title"=>"/^.{3,30}$/u"
	))==false){
		$message.="タイトルは3文字以上30文字以内で入力してください。";
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
		"gitHubURL"=>"/[a-zA-Z0-9-_]+\\/[a-zA-Z0-9-_]+/"
	))==false){
		$message.="githubURLは所有者名/リポジトリ名の部分のみを入力してください。";
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
