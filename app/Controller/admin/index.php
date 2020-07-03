<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/admin/', function (Request $request, Response $response) {

    // Render index view
	$r=$this->view->render($response, 'admin/index.twig');
	return $r;
});

