<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Informations;


// TOPページのコントローラ
$app->get('/', function (Request $request, Response $response) {

    $info = new Informations($this->db);

    $data = [];
	$data["informations"]=$info->select(array(),"date","desc",5,true);
	//相対パスのURLは絶対に直す
	foreach($data["informations"] as &$info){
		if ($info["url"][0]=="/"){
			$info["url"]=$request->getUri()->getBasePath().$info["url"];
		}
	}

    // Render index view
    return $this->view->render($response, 'top/index.twig', $data);
});

