<?php
namespace src\twigExtention;


function registerTwigExtention($twig){
	$extention_files = glob(__dir__.DIRECTORY_SEPARATOR."*.php");
	$current_file_index = array_search(__DIR__.DIRECTORY_SEPARATOR."initializer.php", $extention_files);
	unset($extention_files[$current_file_index]);
	$extention_files = array_values($extention_files);
	foreach($extention_files as $ext){
		require($ext);
		$class_name = basename($ext, ".php");
		$classInfo = new \ReflectionClass("src\\twigExtention\\".$class_name);
		foreach($classInfo->getMethods(\ReflectionMethod::IS_STATIC) as $method){
			$function = new \Twig_SimpleFunction($method->name, $method->getClosure());
			$twig->getEnvironment()->addFunction($function);
		}
	}
}
