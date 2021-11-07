<?php
require("twigExtention/initializer.php");
use src\twigExtention;
// DIC configuration
$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// CSRF‘Îô
$app->getContainer()['csrf'] = function ($c) {
    return new \Slim\Csrf\Guard();
};

// Register Twig View helper
$container['view'] = function ($c) {
	$settings = $c->get('settings')['renderer'];
    $view = new \Slim\Views\Twig(
		$settings['template_path'],
		["debug" => true]
	);
    // Instantiate and add Slim specific extension
    $router = $c->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
    $view->addExtension(new Twig_Extension_Debug());
    $view->getEnvironment()->addGlobal('session', $_SESSION);
	$view->offsetSet("page_title", "default");
	$view->offsetSet("page_description", "");
	$view->offsetSet("page_type", "article");
	$view->offsetSet("page_keywords", []);
	$view->offsetSet("csrf_field_name", $c->get('csrf')->getTokenName());
	$view->offsetSet("csrf_field_value", $c->get('csrf')->getTokenValue());
	twigExtention\registerTwigExtention($view);
    return $view;
};

// MySQL Container via DBAL
$container['db'] = function ($c) {
    $settings = $c->get('settings')['doctrine'];
    $config = new \Doctrine\DBAL\Configuration();
    $connectionParams = $settings["connection"];
    $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
    return $conn;
};

// Session Container
$container['session'] = function ($c) {
    return new \SlimSession\Helper($c->get('settings')['session']);
};

// 404 Not Found Container
$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
		$response->withStatus(404,"Not Found");
		return $c->view->render($c->response, 'error/404.twig');
    };
};
