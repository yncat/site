<?php
if(empty($_GET["password"]) || $_GET["password"] != getenv("SCRIPT_PASSWORD")){
	http_response_code(400);
	exit("Invalid password\n");
}
$cmd = "mysqldump $_ENV['DB_NAME'] -u $_ENV['DB_USER'] -p$_ENV['DB_PASS'] --default-character-set=utf8mb4 --no-tablespaces -B $_ENV['DB_NAME']";
echo(shell_exec($cmd));
