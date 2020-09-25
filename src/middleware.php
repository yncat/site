<?php
// Application middleware
// e.g: $app->add(new \Slim\Csrf\Guard);

use Util\MembersUtil;

$app->add(new DataBaseTransactionHandler($app->getContainer()));
$app->add(new adminPageHandler($app->getContainer()));

class DataBaseTransactionHandler{

	private $container;

	public function __construct($container) {
		$this->container = $container;
	}

	//DBのトランザクションの開始・停止を行う
	public function __invoke($request, $response, $next){
		$this->container->get("db")->beginTransaction();
		$this->container->get("db")->setAutoCommit(false);
		$this->container->get("logger")->info("DB: start transaction");
		$response = $next($request, $response);
		$this->container->get("db")->commit();
		$this->container->get("logger")->info("DB: commit transaction");
		return $response;
	}
}



class AdminPageHandler{

	private $container;

	public function __construct($container) {
		$this->container = $container;
	}

	//admin/の時だけ、管理者ログインなどを行う
	public function __invoke($request, $response, $next){
//		var_dump($request->getUri());
//		exit();
		if(explode("/",$request->getUri()->getPath())[1]==="admin"){
			if(explode("/",$request->getUri()->getPath())[2]==="login"){	//ログインページだけは無視
				return $response = $next($request, $response);
			}
			if(empty($_SESSION["ID"])){
				//認証していない場合にログインページへリダイレクト
				session_regenerate_id(true);
				return $response->withRedirect($request->getUri()->getBasePath()."/admin/login");
			} else {		//ログイン済みなので有効性チェック
				if(!MembersUtil::check($this->container->get("db"))){
					//セッション破棄
					session_destroy();
					return $response->withRedirect($request->getUri()->getBasePath()."/admin/login");
				}
			}
		}
		return $response = $next($request, $response);
	}
}


set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline, array $errcontext){
	$GLOBALS["app"]->getContainer()->get("logger")->error("ERROR lv.".$errno." ".$errstr." at ".$errfile." line:".$errline);
	if ($GLOBALS["app"]->getContainer()->get("db")->isTransactionActive()){
		$GLOBALS["app"]->getContainer()->get("logger")->info("DB rollback");
		$GLOBALS["app"]->getContainer()->get("db")->rollBack();
	}
	return false;
});
