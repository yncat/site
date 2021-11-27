<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class Orders extends Dao{
	public function setDeletedById(int $id){
		$id = (int)$id;
		(new QueryBuilder($this->db))
			->update($this->_table_name)
			->set("flag", "flag |". \ORDER_FLAG_DELETED)
			->where("id = :id")
			->setParameter(":id", $id)
			->execute();
	}

	public function getAllByEmailAndName(string $email, string $name, int $status): array{
		return (new QueryBuilder($this->db))
		->select("o.*", "s.title product_name", "p.edition")
		->from($this->_table_name, "o")
		->innerJoin("o", "products", "p", "p.id = o.product_id")
		->innerJoin("p", "softwares", "s", "p.software_id = s.id")
		->where("email = :email")
		->setParameter(":email", $email)
		->andWhere("o.name = :name")
		->setParameter(":name", $name)
		->andWhere("o.status = :status")
		->setParameter(":status", $status)
		->execute()
		->fetchAll();
	}

}
