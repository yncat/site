<?php

use Slim\Http\Request;
use Slim\Http\Response;


$app->get('/error', function (Request $request, Response $response) {
    // Render view
    return $this->view->render($response, 'error/500.twig');
});

$app->get('/store/error', function (Request $request, Response $response) {
    // Render view
    return $this->view->render($response, 'error/500.twig');
});
