<?php


#設定
$log_file = '/virtual/actlab/public_html/actlab.org/logs/git-pull.log';
$date = (new DateTime())->format('Y-m-d H:i:s');
$path_to_repository = '/virtual/actlab/public_html/actlab.org';
$branch_name = 'master';


$json_string = file_get_contents('php://input');
$json = json_decode($json_string,true);

error_log("[{$date}] Pushed to {$json['ref']}\n",3,$log_file);

if ($json['ref']=="refs/heads/{$branch_name}") { // 指定ブランチのpushイベントの時のみ実行
    $git_remote = "http://github.com/actlaboratory/site.git";
    $command = "cd {$path_to_repository} && git pull {$git_remote} {$branch_name} 2>&1";
    exec($command, $output, $exit_status);

    if ($exit_status > 0) {
        $error_msg = "[{$date}] Error: \n";
        foreach ($output as $line) {
            $error_msg .= "    {$line}\n";
        }
        error_log($error_msg,3,$log_file);
    } else {
        error_log("[{$date}] Updated successfully\n",3,$log_file);
    }
}