<?php

use Slim\Http\Request;
use Slim\Http\Response;

//ファイル選択
$app->get('/admin/logviewer/', function (Request $request, Response $response) {
    // Render index view
	$list = glob(dirname($_SERVER["SCRIPT_FILENAME"]). '/logs/*.*');
	foreach ($list as $file) {
		$files[]=basename($file);
	}

	$data["files"]=$files;
    return $this->view->render($response, 'admin/logviewer/index.twig',$data);
});

//ファイル内容表示
$app->get('/admin/logviewer/{fileName}', function (Request $request, Response $response, $args) {
	$fileName=dirname($_SERVER["SCRIPT_FILENAME"]). '/logs/'.$args["fileName"];
	if (file_exists($fileName)){
		return $response->write(file_get_contents($fileName));
	} else {
		$response->withStatus(404,"not found");
		return $response->write("file not found. :".$fileName);
	}
});
