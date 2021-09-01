<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/admin/', function (Request $request, Response $response) {

	$data = [];
	if (isset($_GET["message"])){
		$data["message"] = $_GET["message"];
	}

    // Render index view
	return $this->view->render($response, 'admin/index.twig', $data);
});

