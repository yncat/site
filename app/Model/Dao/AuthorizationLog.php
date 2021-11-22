<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class AuthorizationLog extends Dao{

	public const STATUS_AUTH_CHALLENGE = 0;		// �F�؂����݂�
	public const STATUS_AUTH_SUCCESS = 1;		// �F�؂ɐ������A�o�^���ꂽ
	public const STATUS_AUTH_RETRY = 2;			// ���ɔF�؍ς݂��������ߔF�؃t�@�C�����Ĕ��s����

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

	// ����V���A���ň����ԓ��ɍs��ꂽ�V�K�F�؂̉񐔂�Ԃ�
	public function checkAuthCount(string $key,int $rate_time):int{
		$query = (new QueryBuilder($this->db))
			->select("COUNT(*) cnt")
			->from($this->_table_name)
			->where('serialnumber = :serialnumber')
			->setParameter(":serialnumber", $key)
			->andWhere('status = '.self::STATUS_AUTH_SUCCESS)
			->setParameter(":serialnumber", $key)
			->andWhere('created_at >= "'. date("Y-m-d H:i:s",time()-$rate_time) . '"');

		//�N�G�����s
		$query = $query->execute();
		$result = $query->Fetch();

		//���ʂ�ԑ�
		return $result["cnt"];
	}

}
