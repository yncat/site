<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class TrialAuthorizationLog extends Dao{

	public const STATUS_AUTH_CHALLENGE = 0;		// �F�؂����݂�
	public const STATUS_AUTH_SUCCESS = 1;		// �F�؂ɐ������A���s����


	// host����rate_time�b�Ԃɂ��������N�G�X�g�̐���Ԃ�
	public function checkRateCount(string $host,int $rate_time):int{
		$query = (new QueryBuilder($this->db))
			->select("COUNT(*) cnt")
			->from($this->_table_name)
			->where('host = :host')
			->andWhere('created_at >= "'. date("Y-m-d H:i:s",time()-$rate_time) . '"')
			->setParameter(":host", $host);

		//�N�G�����s
		$query = $query->execute();
		$result = $query->Fetch();

		//���ʂ�ԑ�
		return $result["cnt"];
	}

	// ����}�V���ɑ΂��Ĉ����ԓ��ɍs��ꂽ�F�؂̉񐔂�Ԃ�
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

		//�N�G�����s
		$query = $query->execute();
		$result = $query->Fetch();

		//���ʂ�ԑ�
		return $result["cnt"];
	}

	// �e�\�t�g���Ƃɗތ^�̋N�������E���s����Ԃ�
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
