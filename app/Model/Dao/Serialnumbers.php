<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;


class Serialnumbers extends Dao
{
	public function getAllByOrder(int $order_id): array{
		return $this->select(["order_id"=>$order_id],
		"",
		"ASC",
		1000,
		true);
	}

	// Žw’è‚³‚ê‚½ƒVƒŠƒAƒ‹”Ô†‚Ìî•ñ‚ð“¾‚é
	public function getNumberInfo(string $sn): array{
		$query = (new QueryBuilder($this->db))
			->select("s.keyword software_name","o.name name","o.email","v.major","v.minor","v.patch")
			->from($this->_table_name, "sn")
			->innerJoin("sn","orders","o","o.id = sn.order_id")
			->innerJoin("o","products","p","p.id = o.product_id")
			->innerJoin("p","softwares","s","s.id = p.software_id")
			->innerJoin("s","software_versions","v", "s.id = v.software_id")
			->where("sn.serialnumber = :sn")
			->setParameter(":sn", $sn)
			->orderBy("major*1000+minor*1000+patch","DESC")
			->setMaxResults(1);

		$query = $query->execute();

		//Œ‹‰Ê‚ð•Ô‘—
		return $query->FetchAll();
	}
}
