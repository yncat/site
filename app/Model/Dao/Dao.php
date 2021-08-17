<?php

namespace Model\Dao;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class Dao
 *
 * DB接続及び汎用CRUDライブラリです
 *
 * 利用するには、
 * 1.src/settings.phpのdoctrineにDB接続情報を指定
 * 2.src/dependencies.phpにDB接続用のコンテナを$container['db']を作成
 * をしておきます。
 *
 */
abstract class Dao {
	/**
	 * @var \Doctrine\DBAL\Connection $db DB接続用コンテナを格納
	 */
	protected $db;

	/**
	 * @var string _table_name 子クラスのテーブル名を格納します
	 */
	protected $_table_name;

	/**
	 * Dao constructor.
	 *
	 * Dao のコンストラクタです。コネクションを格納し、子クラスのクラス名からテーブル名を指定します
	 *
	 * @param \Doctrine\DBAL\Connection $db データベース
	 */

	public function __construct($db)
	{
		//DBコネクションを格納
		$this->db = $db;

		//子クラスのファイル名を取得する
		$ref = get_class($this);

		// MODEL\DAO\CLASS から CLASS名のみを取得する
		$this->_table_name = str_replace(__NAMESPACE__ . "\\", "", $ref);

		//キャメルケースをスネークケースに変換
		$this->_table_name = preg_replace("/([A-Z])/", '_${1}', $this->_table_name);
		$this->_table_name = strtolower(preg_replace("/^_/", '', $this->_table_name));
	}

	/**
	 * select Function
	 *
	 * 情報を取得する汎用SELECT関数
	 *
	 * @param array $param WHERE句として指定したい条件を連想配列で指定します。値に%があると、部分一致などもできます
	 * @param string $sort ソートしたいカラム名を指定します
	 * @param string $order 昇順=ASC 降順=DESCを指定します
	 * @param int $limit 取得件数を指定します。デフォルト10件
	 * @param bool $fetch_all false=一件のみ取得します true=全件取得します
	 * @return array|mixed 取得した情報を配列で返送します
	 */
	public function select(array $param, $sort = "", $order = "ASC", $limit = 10, $fetch_all = false)
	{
		//クエリビルダをインスタンス化
		$queryBuilder = new QueryBuilder($this->db);

		//ベースクエリを構築する
		$queryBuilder
			->select('*')
			->from($this->_table_name);

		//引数の配列からWhere句を生成
		foreach ($param as $key => $val) {
			//値があれば処理をする
			if ($val) {
				$queryBuilder->andWhere($key . " LIKE :$key");
				$queryBuilder->setParameter(":$key", $val);
			}
		}

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

		//レコードの取得方法。全件モードのとき
		if ($fetch_all) {
			//その結果を取得する実行
			$result = $query->FetchALL();
		} else {
			//レコードの取得方法。1件モードのとき
			$result = $query->Fetch();
		}

		//結果を返送
		return $result;
	}

	/**
	 * exist Function
	 *
	 * 条件に合うレコードの存在確認を行う汎用exist関数
	 *
	 * @param array $param WHERE句として指定したい条件を連想配列で指定します。値に%があると、部分一致などもできます
	 * @return bool
	 */
	public function exist(array $param):bool
	{
		//クエリビルダをインスタンス化
		$queryBuilder = new QueryBuilder($this->db);

		//ベースクエリを構築する
		$queryBuilder
			->select('count(*)')
			->from($this->_table_name);

		//引数の配列からWhere句を生成
		foreach ($param as $key => $val) {
			//値があれば処理をする
			if ($val) {
				$queryBuilder->andWhere($key . " LIKE :$key");
				$queryBuilder->setParameter(":$key", $val);
			}
		}

		//クエリを実行して結果を返送
		return intval($queryBuilder->execute()->fetch()["count(*)"]) > 0;
	}

	/**
	 * insert Function
	 *
	 * 汎用INSERT関数
	 *
	 * 連想配列で指定した情報を新規レコードとして挿入します
	 *
	 * @param array $param 挿入したいデータを連想配列で指定します
	 * @return int|bool 発番されればidを返送、失敗したらfalseを返送します
	 */
	public function insert(array $param)
	{

		//クエリビルダをインスタンス化
		$queryBuilder = new QueryBuilder($this->db);

		//ベースクエリを構築する
		$queryBuilder
			->insert($this->_table_name);

		//引数の配列からWhere句を生成
		foreach ($param as $key => $val) {
			//値があれば処理をする
			if ($val) {
				$queryBuilder->setValue($key, ":$key");
				$queryBuilder->setParameter(":$key", $val);
			}
		}

		//クエリ実行
		$queryBuilder->execute();

		//最終発行IDを返送
		$lastInsertId = $queryBuilder->getConnection()->lastInsertId();

		//結果を返送
		return $lastInsertId;
	}

	/**
	 * update Function
	 *
	 * 情報の更新を行う関数
	 *
	 * @param array $param 更新したい情報をid込みでセットします
	 * @param array $primaryKeys 更新する行を特定するために使うカラムの名前を指定します。ここで指定したカラムは$paramに含まれている必要があります。
	 */

	public function update(array $param,array $primaryKeys=array("id"))
	{

		//クエリビルダをインスタンス化
		$queryBuilder = new QueryBuilder($this->db,true);

		//ベースクエリを構築する
		$queryBuilder
			->update($this->_table_name);

		//引数の配列からWhere句を生成
		foreach ($param as $key => $val) {

			//id以外の場合
			if (!in_array($key,$primaryKeys)) {
				$queryBuilder->set($key, ":$key");
				$queryBuilder->setParameter(":$key", $val);
			} else {
				$queryBuilder->where($key."=:$key");
				$queryBuilder->setParameter(":$key", $val);
			}
		}

		//クエリ実行
		return $queryBuilder->execute();
	}

	/**
	 * delete Function
	 *
	 * 情報の削除を行う関数
	 *
	 * idのレコードを削除します
	 *
	 * @param array $param WHERE句として指定したい条件を連想配列で指定します。値に%があると、部分一致などもできます
	 */
	public function delete(array $param){
		//クエリビルダをインスタンス化
		$queryBuilder = new QueryBuilder($this->db);

		//ベースクエリを構築する
		$queryBuilder
			->delete($this->_table_name);

		//引数の配列からWhere句を生成
		foreach ($param as $key => $val) {
			if($val){
				$queryBuilder->andWhere($key."= :$key")
				->setParameter(":$key", $val);
			}
		}
		//クエリ実行
		$queryBuilder->execute();
	}

	public function getQueryBuilder(){
		return new QueryBuilder($this->db);
	}
}
