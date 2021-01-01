<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/software/information', function (Request $request, Response $response) {
    $data=array();

    // Render index view
    return $this->view->render($response, '/software/information.twig', $data);
});
