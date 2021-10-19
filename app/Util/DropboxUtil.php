<?php
namespace Util;

use Util\EnvironmentUtil;


class DropboxUtil {

	public static function save($fileName,$data){
		global $app;
		$logger = $app->getContainer()->get("logger");

		if (!EnvironmentUtil::isProduct()){
			$fileName = "[".EnvironmentUtil::getEnvName()."] ".$fileName;
		}

		$header=array(
			"Authorization: Bearer ".getenv("DROPBOX_TOKEN"),
			"Content-Type: application/octet-stream",
			"Content-Length: ".strlen($data),
			'Dropbox-API-Arg: {"path": "/actlab/'.$fileName.'","mode": "overwrite","autorename": false,"mute": false,"strict_conflict": false}',
			"User-Agent: ACTLaboratory-webadmin"
		);

		$context = array(
			"http" => array(
				"method"  => "POST",
				"header"  => implode("\r\n", $header),
				"content" => $data,
				"ignore_errors"=>true
			)
		);

		$response=file_get_contents("https://content.dropboxapi.com/2/files/upload", false, stream_context_create($context));
		$result = json_decode($response,true);
		if (isset($result["path_display"]) && isset($result["size"])){
			$logger->info("successfully uploaded. size={$result['size']}byte, path=\"{$result['path_display']}\"");
			return $result;
		} else {
			$logger->error("Dropbox save failed. ".var_export($response, true));
			return false;
		}
	}
}
