<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Informations;


// 過去の新着情報ページのコントローラ
$app->get('/informations/', function (Request $request, Response $response) {
    $data = makeInformationsPageData($request, $this->db);
    return $this->view->render($response, 'informations/index.twig', $data);
});

$app->post('/informations/', function (Request $request, Response $response) {
    $input = $request->getParsedBody();
    if (!empty($input["showYear"]) && $input["showYear"] > 2010 && $input["showYear"] < (int)date("Y")){
        $data = makeInformationsPageData($request, $this->db, $input["showYear"]);
    } else{
        $data = makeInformationsPageData($request, $this->db);
    }
    return $this->view->render($response, 'informations/index.twig', $data);
});

function makeInformationsPageData($request, $db, $year=NULL){
    $info = new Informations($db);

    $data = [];
    $data["informationYears"] = $info->getYears("DESC");
    if (empty($year)){
        if (!empty($data["informationYears"])){
            $year = $data["informationYears"][0];
        } else{
            $year = (int)date("Y");
        }
    }
    $data["informations"]=$info->selectFromYear($year, "id", "desc",65535,true);
    $data["showYear"] = $year;
    //相対パスのURLは絶対に直す
    foreach($data["informations"] as &$info){
        if (!empty($info["url"]) && $info["url"][0]=="/"){
            $info["url"]=$request->getUri()->getBasePath().$info["url"];
        }
    }

    return $data;
}
