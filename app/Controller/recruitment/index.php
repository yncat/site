<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/recruitment/', function (Request $request, Response $response) {
    $data = [];

    // Render index view
    return $this->view->render($response, 'recruitment/index.twig', $data);
});

