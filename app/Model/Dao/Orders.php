<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class Orders extends Dao{

	// 注文日別入金待ち注文件数
	public function countWaitingOrder(){
		return (new QueryBuilder($this->db))
			->select("date(created_at) ordered_on", "COUNT(*) cnt")
			->from($this->_table_name, "o")
			->where("status = " . \ORDER_STATUS_WAIT_PAY)
			->andWhere("flag &". \ORDER_FLAG_DELETED . " = 0")
			->groupBy("ordered_on")
			->execute()
			->fetchAll();
	}



	public function setDeletedById(int $id){
		$id = (int)$id;
		(new QueryBuilder($this->db))
			->update($this->_table_name)
			->set("flag", "flag |". \ORDER_FLAG_DELETED)
			->where("id = :id")
			->setParameter(":id", $id)
			->execute();
	}

	public function getAll(){
		return (new QueryBuilder($this->db))
		->select("o.*", "s.title product_name", "p.edition", "YEAR(sn.created_at) confirm_year","MONTH(sn.created_at) confirm_month")
		->from($this->_table_name, "o")
		->innerJoin("o", "products", "p", "p.id = o.product_id")
		->innerJoin("p", "softwares", "s", "p.software_id = s.id")
		->leftJoin(
			"o",
			"(".(new QueryBuilder($this->db))
				->select("order_id","created_at")
				->from("serialnumbers")
				->groupBy("order_id")
				->getSql().")",
			"sn",
			"sn.order_id = o.id")
		->where("o.flag &". \ORDER_FLAG_DELETED . " = 0")
		->orderBy("o.status")
		->addOrderBy("o.id","DESC")
		->execute()
		->fetchAll();
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
		->andWhere("flag &". \ORDER_FLAG_DELETED . " = 0")
		->execute()
		->fetchAll();
	}
}
