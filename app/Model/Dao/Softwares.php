<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class Softwares extends Dao{

	function getLatest($keyword=null, int $excludeFlag=0){

		$subQueryBuilder = new QueryBuilder($this->db);
		$subQueryBuilder
			->select("t1.software_id, t1.major, t1.minor, t1.patch, t1.released_at, t1.updater_URL, t1.hist_text, t1.updater_hash")
			->from("software_versions","t1")
			->orderBy("t1.released_at","DESC")
			->where("NOT EXISTS(
				SELECT 1 FROM software_versions t2 WHERE t1.software_id=t2.software_id AND t1.major*1000000+t1.minor*1000+t1.patch < t2.major*1000000+t2.minor*1000+t2.patch
			)");

		$queryBuilder = new QueryBuilder($this->db);
		$queryBuilder
			->select(
				"A.id, A.title, A.keyword, A.description, A.gitHubURL, A.flag,".
				"B.major, B.minor, B.patch,B.released_at, B.updater_URL, B.hist_text, B.updater_hash")
			->from("softwares","A")
			->join("A","(".$subQueryBuilder->getSQL().")","B","A.id=B.software_id")
			->orderBy("B.released_at","DESC");

		$queryBuilder->where("A.flag & $excludeFlag = 0");

		//キーワード指定の時は１件しかいらない
		if($keyword!=null){
			$queryBuilder->andWhere("A.keyword = :kwd")
				-> setParameter(":kwd", $keyword);
		}


		$query = $queryBuilder->execute();
		return $query->FetchALL();
	}

	function getVersions($textVersion = "1.0.0", $keyword = ""){
		$versions = explode(".", $textVersion);
		$queryBuilder = new QueryBuilder($this->db);
		$queryBuilder
		->select("t1.software_id, t1.major, t1.minor, t1.patch, t1.released_at, t1.updater_URL, t1.hist_text, t1.updater_hash")
		->from("software_versions","t1")
		->orderBy("t1.major*1000000+t1.minor*1000+t1.patch","DESC")
		->where("t1.major*1000000+t1.minor*1000+t1.patch > :version")
		->setParameter(":version", $versions[0]*1000000+$versions[1]*1000+$versions[2]);
		if(!$keyword == ""){
			$queryBuilder->andWhere("exists(
			select * from softwares t2 where t1.software_id = t2.id and t2.keyword = :kwd)")
			->setParameter(":kwd", $keyword);
		}
		$query = $queryBuilder->execute();
		return $query->FetchALL();
	}
}
//クエリ組み立て参考資料
//https://qiita.com/fukumoto/items/caad9b1c0c17e796b4f4
