<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/disclose/store_info', function (Request $request, Response $response) {
    $data = [];

    // Render index view
    return $this->view->render($response, 'disclose/store_info.twig', $data);
});

