<?php
// Application middleware
// e.g: $app->add(new \Slim\Csrf\Guard);

use Util\MembersUtil;

$app->add(new adminPageHandler($app->getContainer()));

class AdminPageHandler{

	private $container;

	public function __construct($container) {
		$this->container = $container;
	}

	//admin/の時だけ、管理者ログインなどを行う
	public function __invoke($request, $response, $next){
		if(explode("/",$request->getUri()->getPath())[0]==="admin"){
			if(explode("/",$request->getUri()->getPath())[1]==="login"){	//ログインページだけは無視
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

