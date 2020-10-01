<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/disclose/', function (Request $request, Response $response) {
    $data=array();

    // Render index view
    return $this->view->render($response, 'disclose/index.twig', $data);
});

$app->get('/disclose/policy', function (Request $request, Response $response) {
    $data=array();

    // Render index view
    return $this->view->render($response, 'disclose/policy.twig', $data);
});

