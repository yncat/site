<?php
namespace src\twigExtention;

class SecurityFunctions{
	public static function csrf_field(){
		global $container;
		return '<input type="hidden" name="' .
			$container->get('csrf')->getTokenNameKey() .
			'" value="' .
			$container->get('csrf')->getTokenName() .
			'"><input type="hidden" name="' .
			$container->get('csrf')->getTokenValueKey() .
			'" value="' .
			$container->get('csrf')->getTokenValue() .
			'">' . "\n";
	}

	public static function csrf_query(){
		global $container;
		return 
			$container->get('csrf')->getTokenNameKey() .
			'=' .
			$container->get('csrf')->getTokenName() .
			'&' .
			$container->get('csrf')->getTokenValueKey() .
			'=' .
			$container->get('csrf')->getTokenValue();
	}

}
