<?php

use Slim\Http\Request;
use Slim\Http\Response;


// TOPページのコントローラ
$app->get('/store/', function (Request $request, Response $response) {


    // Render index view
    return $this->view->render($response, 'store/index.html');
});

