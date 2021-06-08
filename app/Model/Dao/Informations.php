<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use PDO;


class Informations extends Dao
{
    // 年ごとにお知らせを取得
    function selectFromYear(int $year, $sort, $order, $limit=NULL){
        //ベースクエリを構築する
        $queryBuilder = parent::getQueryBuilder()
            ->select('*')
            ->from($this->_table_name)
            ->where("year(date) = ". (int)$year);

        //ソート順が指定されていたら指定します
        if ($sort) {
            $queryBuilder->orderBy($sort, $order);
        }

        //リミットが指定されていたら指定します
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        //クエリ実行
        $query = $queryBuilder->execute();

        //レコードの取得
        $result = $query->FetchALL();
        //結果を返送
        return $result;
    }

    // 年一覧取得
    function getYears($order="ASC"){
        //クエリを構築する
        $queryBuilder = parent::getQueryBuilder()
            ->select('DISTINCT YEAR(date) as year')
            ->from($this->_table_name)
            ->orderBy("date", $order);

        //クエリ実行
        $query = $queryBuilder->execute();

        //レコードの取得
        $result = $query->FetchALL();
        //結果を返送
        if (empty($result)){
            return [];
        } else{
            $ret = [];
            foreach($result as $d){
                array_push($ret, $d["year"]);
            }
            return $ret;
        }
    }

}
