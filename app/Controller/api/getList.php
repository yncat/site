<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Softwares;
use Util\SoftwareUtil;

$app->get('/api/getList', function (Request $request, Response $response) {

    $softwares = new softwares($this->db);

    $data = [];
    // フラグによって隠されたソフトウェア以外を抽出
    $data["softwares"]=$softwares->getLatest(null, FLG_HIDDEN);
	foreach($data["softwares"] as &$software){
		SoftwareUtil::makeTextVersion($software);
	}

	return $response->withJson($data,200,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
});

