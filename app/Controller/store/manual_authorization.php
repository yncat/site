<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\SoftwareUtil;
use Util\ValidationUtil;

$app->get('/store/manual_authorization', function (Request $request, Response $response) {
	return showManualAuthorizationPage();
});

function showManualAuthorizationPage($message=""){
	global $app;

	$data = $app->getContainer()->request->getParsedBody();
	$data["message"] = $message;
	return $app->getContainer()->get("view")->render($app->getContainer()->get('response'), 'store/manual_authorization.twig', $data);
}


$app->post('/store/manual_authorization', function (Request $request, Response $response) {
	$input = $request->getParsedBody();

	//必須パラメータチェック
	$message = "";
	if(!ValidationUtil::checkParam($input,array(
		"serial_key"=>"/^[a-zA-Z0-9]{4}-?[a-zA-Z0-9]{4}-?[a-zA-Z0-9]{4}-?[a-zA-Z0-9]{4}$/",
	))){
		$message .= "入力されたシリアルキーの形式が不正です。\n";
	}
	if(!ValidationUtil::checkParam($input,array(
		"offline_auth_code"=>"|^[0-9]+-[A-Za-z0-9\\+=/]{12}-[A-Za-z0-9\\+=/]{44}$|",
	))){
		$message .= "入力されたオフライン登録コードの形式が不正です。\n";
	}
	if ($message !== ""){
		return showManualAuthorizationPage($message);
	}

	// オフライン認証コードをパース
	$data = explode("-",$input["offline_auth_code"]);
	$received_check_sum = intval($data[0]);
	$version_code = base64_decode($data[1]);
	$version_data = unpack("Sserial_prefix_len/Smajor/Sminor/Spatch",$version_code);
	$serial_prefix_len = $version_data["serial_prefix_len"];
	$version = $version_data["major"] . "." . $version_data["minor"] . "." . $version_data["patch"];
	$unlock_code = bin2hex(base64_decode($data[2]));

	// 各値とチェックサムによるバリデーション
	$calcurated_check_sum = 0;
	foreach(array_merge(str_split($data[1]), str_split($data[2])) as $c){
		$calcurated_check_sum += ord($c);
	}
	if($calcurated_check_sum !== $received_check_sum ||
		$serial_prefix_len <= 0 || $serial_prefix_len > 6 ||
		$received_check_sum <= 0
	){
		return showManualAuthorizationPage("入力されたオフライン登録コードが誤っています。");
	}

	$serial_key = str_replace("-","",strtoupper($input["serial_key"]));

	$result = SoftwareUtil::authorizeProduct(
		substr($serial_key,0,$serial_prefix_len),
		$version,
		str_replace("-","",strtoupper($serial_key)),
		$unlock_code
	);

	if ($result === SoftwareUtil::AUTH_RESULT_RATE_LIMIT){
		return showManualAuthorizationPage("大変恐れ入りますが、しばらくたってから再度お試しください。");
	} else if ($result === SoftwareUtil::AUTH_RESULT_SERIAL_NOT_FOUND){
		return showManualAuthorizationPage("入力されたシリアルキーが誤っています。入力誤りが続くと、一定の時間入力できなくなりますのでご注意ください。");
	} else if ($result === SoftwareUtil::AUTH_RESULT_VALIDATION_FAILED){
		return showManualAuthorizationPage("入力されたシリアルキーとオフライン認証コードを発行したソフトウェアの組み合わせが誤っているか、古いバージョンのソフトウェアでオフライン認証コードの発行を行っているため、認証できませんでした。");
	} else if ($result === SoftwareUtil::AUTH_SERIAL_LOCKED){
		return showManualAuthorizationPage("このシリアルキーは、認証回数の上限に達しているため現在使用できません。");
	} else if ($result === SoftwareUtil::AUTH_RESULT_SUCCESS){
		// ライセンスファイルを生成
		$license_file = SoftwareUtil::makeLicenseFile(
			$serial_key,
			$unlock_code
		);

	    // 応答データを作成
		return $response->withHeader("Content-Type", "application/octet-stream")
			->withHeader('Content-Disposition', 'attachment; filename="license.dat"')
            ->withHeader('Content-Transfer-Encoding', 'binary')
			->withHeader('Expires', '0')
			->withHeader('Cache-Control', 'must-revalidate')
			->withHeader('Pragma', 'public')
			->write($license_file);
	} else {
		return showManualAuthorizationPage("不明なエラーが発生しました。後ほど再度お試しください。");
	}
});
