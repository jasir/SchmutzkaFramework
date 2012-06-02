<?php

/**
 * Base model functions
 * @dev: possible fully-cached queries
 * @dev: cache invalidation on update/insert/delete of record + it's id? or whole? could be awesome :)
 */

namespace Models;

class Base extends \Nette\Object
{

    /** @var \Notorm */
    protected $db;

    /** @var \Nette\Caching\Cache */
    protected $cache;

	/** @var string */
	protected $tableName;


	public function __construct(\NotORM $notorm, \Nette\Caching\Cache $cache)
	{
		$this->db = $notorm;
		$this->cache = $cache;
		$this->tableName = $this->tableNameByClass(get_class($this));
	}


	/** 
	 * Retun records by condition
	 * @param string
	 * @param string
	 */
	public function all(array $key = array())
	{
		if ($key) {
			return $this->table($key);
		}
		else {
			return $this->table();
		}
	}


	/** 
	 * Insert record
	 * @param array
	 * @param string
	 * @return mixed
	 */
	public function insert($array, $returnColumn = "id")
	{
		$row = $this->table()->insert($array);

		if ($returnColumn) {
			return $row[$returnColumn];
		}

		return $row->fetchRow();
	}


	/**
	 * Update record
	 * @param array
	 * @param array
	 */
	public function update($array, array $key)
	{
		return $this->table($key)->update($array);	
	}

	
	/**
	 * Delete record
	 * @param array
	 * @param string
	 */
	public function delete(array $key)
	{
		if ($record = $this->exist($key)) {
			return $record->delete();
		}
		else {
			return FALSE;
		}
	}


	/**
	 * Check if record exists
	 * @param array
	 * @return array/FALSE
	 */
	public function exist(array $key)
	{		
		$record = $this->table()->where($key);	

		if ($record->count("*")) {
			return $record;
		}
		else {
			return FALSE;
		}
	}


	/**
	 * Get 1 item
	 * @param array
	 * @return array
	 */
	public function item(array $key)
	{
		$record = $this->exist($key);

		if ($record) {
			return $record->fetchRow();
		}
		else {
			return FALSE;
		}
	}
	
	
	/**
	 * Get number of table rows
	 * @param array
	 * @return int
	 */
	public function count($where = NULL)
	{
		$key = $this->tableName . ($where ?  "_" . md5(serialize($where)) : NULL);
	
		if (isset($this->cache[$key])) {
			return (int) $this->cache[$key];
		}

		if ($where) {
			$count = $this->table($where)->count("*");
		}
		else {
			$count = $this->table()->count("*");
		}

		if ($count > 1000) {
			$this->cache->save($key, $count, array(
				"expire" => (int) (time() + 60 * 60 * 24 * ($count/500000)), // Jean's magic constant
			));
		}

		return $count;
	}


	/**
	 * Get table rows as pairs
	 * @param string $column
	 * @return array
	 */
	public function fetchPairs($id = "id", $column = NULL, array $where = array())
	{
		$result = $this->table();
		if ($where) {
			$result->where($where);
		}

		return $result->fetchPairs($id, $column);
	}


	/**
	 * Fetch random table row
	 * @return array
	 */
	public function fetchRandom()
	{
		return $this->table()->order("RAND()")->limit(1)->fetchRow();
	}


	/**
	 * Fetch single
	 * @param array
	 * @param string
	 * @return mixed
	 */
	public function fetchSingle($where, $column)
	{
		return $this->table($where)->fetchSingle($column);
	}


	/**
	 * Get last id
	 * @param string
	 */
	public function getLastId($column = "id")
	{
		$record = $this->table()->order("$column DESC")->fetchSingle($column);
		if (is_null($record)) {
			return 0;
		}

		return $record;	
	}


	/**
	 * Insert, update on duplicate key (
	 * @param array
	 * @param array
	 */
	public function upsert($data, $unique)
	{
		return $this->table()->insert_update($unique, $data, $data);
	}


	/**
	 * Magic function
	 * @use 1: findByTag("apple") -> where("tag", "apple")
	 * @howtouse: http://pla.nette.org/cs/jednoduchy-model-s-notorm#toc-relacie-1-n
	 */
	public function __call($name, $args)
        {
        if (strpos($name, "findBy") !== FALSE) {
            $cammelCaseSplit = preg_split("~(?<=\\w)(?=[A-Z])~", str_replace("findBy", "", $name));
            $loweredCammels = array_map(function($in) {
                return strtolower($in);
            }, $cammelCaseSplit);
            $findCondition = implode(".", $loweredCammels);

            if (isset($args[1]) && true === $args[1]) {
                // M:N relation
                $relationTableName = $loweredCammels[0] . "s_" . $this->tableName;
                $mn = $this->db->{$relationTableName}($findCondition, $args[0])
                    ->select(substr($this->tableName, 0, -1) . "_id");

                try {
                    $result = $this->table("id", $mn);
                } catch (\PDOException $e) {
                    if (false !== strpos($e->getMessage(), "Table") && false !== strpos($e->getMessage(), "doesn't exist")) {
                        // switch table name elements
                        $relationTableName = $this->tableName . "_" . $loweredCammels[0] . "s";
                        $mn = $this->db->{$relationTableName}($findCondition, $args[0])
                            ->select(substr($this->tableName, 0, -1) . "_id");

                        $result = $this->table("id", $mn);
                    } else {
                        throw $e;
                    }
                }

                return $result;
            } 
			else {
                // no or 1:N relation
                return $this->table()->where($findCondition, $args[0])->fetchRow();
            }
        }
    }


	/********************* shortcuts & helpers *********************/


	/**
	 * Table shortcut
	 */
	final public function table()
	{
		return call_user_func_array(array($this->db, $this->tableName), func_get_args());
	}


	/**
	 * Get table name by class name [Pages => pages, ArticleTag => article_tag]
	 * @param string
	 * @return string
	 */
	private function tableNameByClass($className)
	{
		$tableName = explode("\\", $className);
		$tableName = lcfirst(array_pop($tableName));

		$replace = array();
		foreach (range("A", "Z") as $letter) {
			$replace[$letter] = "_".strtolower($letter);
		}

		return strtr($tableName, $replace); 
	}

}