<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Members;
use Util\MembersUtil;

$app->get('/about/', function (Request $request, Response $response) {
    $members=new Members($this->db);

    $data=array();

    $data["members"]=$members->select(array(),"","","",true);
    foreach($data["members"] as &$member){
        MembersUtil::makeLinkCode($member);
    }

    // Render index view
    return $this->view->render($response, 'about/index.twig', $data);
});

