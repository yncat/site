<?php
namespace src\twigExtention;

class serverFunctions{

	public static function get_environment($name){
		return getenv($name);
	}
}
