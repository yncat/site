<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Informations;


// TOPページのコントローラ
$app->get('/', function (Request $request, Response $response) {

    $info = new Informations($this->db);

    $data = [];
	$data["informations"]=$info->select(array(),"date","desc",5,true);

    // Render index view
    return $this->view->render($response, 'top/index.twig', $data);
});

