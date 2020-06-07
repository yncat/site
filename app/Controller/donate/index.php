<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/donate/', function (Request $request, Response $response) {
    $data=array();

    // Render index view
    return $this->view->render($response, 'donate/index.twig', $data);
});

