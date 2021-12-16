<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class TrialAuthorizationLog extends Dao{

	public const STATUS_AUTH_CHALLENGE = 0;		// 認証を試みた
	public const STATUS_AUTH_SUCCESS = 1;		// 認証に成功し、実行した


	// hostからrate_time秒間にあったリクエストの数を返す
	public function checkRateCount(string $host,int $rate_time):int{
		$query = (new QueryBuilder($this->db))
			->select("COUNT(*) cnt")
			->from($this->_table_name)
			->where('host = :host')
			->andWhere('created_at >= "'. date("Y-m-d H:i:s",time()-$rate_time) . '"')
			->setParameter(":host", $host);

		//クエリ実行
		$query = $query->execute();
		$result = $query->Fetch();

		//結果を返送
		return $result["cnt"];
	}

	// 同一マシンに対して一定期間内に行われた認証の回数を返す
	public function checkAuthCount(int $software_id, string $code,int $rate_time):int{
		$query = (new QueryBuilder($this->db))
			->select("COUNT(*) cnt")
			->from($this->_table_name)
			->where('code = :code')
			->setParameter(":code", $code)
			->andWhere('software_id = :software_id')
			->setParameter(":software_id", $software_id)
			->andWhere('status = '.self::STATUS_AUTH_SUCCESS)
			->andWhere('created_at >= "'. date("Y-m-d H:i:s",time()-$rate_time) . '"');

		//クエリ実行
		$query = $query->execute();
		$result = $query->Fetch();

		//結果を返送
		return $result["cnt"];
	}

	// 各ソフトごとに類型の起動成功・失敗数を返す
	public function getAnalyticsData(){
		return (new QueryBuilder($this->db))
			->select("s.keyword name","COUNT(*) total","COUNT(DISTINCT code) `unique`")
			->from($this->_table_name,"l")
			->innerJoin("l","softwares","s","l.software_name = s.keyword")
			->innerJoin("s","products","p","s.id = p.software_id")
			->where("status = " . self::STATUS_AUTH_SUCCESS)
			->groupBy("s.keyword")
			->execute()
			->fetchAll();
	}
}
