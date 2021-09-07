<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class SoftwareLinks extends Dao{
	public function getBySoftwareId($id){
		return self::select(["software_id"=>$id],"sort_order","ASC",10,true);
	}
}
