<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class AuthorizationLog extends Dao{

	public const STATUS_AUTH_CHALLENGE = 0;		// 認証を試みた
	public const STATUS_AUTH_SUCCESS = 1;		// 認証に成功し、登録された
	public const STATUS_AUTH_RETRY = 2;			// 既に認証済みだったため認証ファイルを再発行した

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

	// 同一シリアルで一定期間内に行われた新規認証の回数を返す
	public function checkAuthCount(string $key,int $rate_time):int{
		$query = (new QueryBuilder($this->db))
			->select("COUNT(*) cnt")
			->from($this->_table_name)
			->where('serialnumber = :serialnumber')
			->setParameter(":serialnumber", $key)
			->andWhere('status = '.self::STATUS_AUTH_SUCCESS)
			->setParameter(":serialnumber", $key)
			->andWhere('created_at >= "'. date("Y-m-d H:i:s",time()-$rate_time) . '"');

		//クエリ実行
		$query = $query->execute();
		$result = $query->Fetch();

		//結果を返送
		return $result["cnt"];
	}

}
