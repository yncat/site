<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/admin/', function (Request $request, Response $response) {

    // Render index view
	return $this->view->render($response, 'admin/index.twig');
});

