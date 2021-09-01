<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class Members extends Dao{
	public function getBySlackId($id){
		return self::select(["slack"=>$id]);
	}
}
