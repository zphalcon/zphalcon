<?php
namespace Phalcon\Db;


class Index implements IndexInterface
{
	protected $_name;
	protected $_columns;
	protected $_type;

	public function __construct($name, $columns, $type = null)
	{
		$this->_name = $name;

		$this->_columns = $columns;

		$this->_type = (string) $type;

	}

	public static function __set_state($data)
	{

		if (!(function() { if(isset($data["_name"])) {$indexName = $data["_name"]; return $indexName; } else { return false; } }()))
		{
			throw new Exception("_name parameter is required");
		}

		if (!(function() { if(isset($data["_columns"])) {$columns = $data["_columns"]; return $columns; } else { return false; } }()))
		{
			throw new Exception("_columns parameter is required");
		}

		if (!(function() { if(isset($data["_type"])) {$type = $data["_type"]; return $type; } else { return false; } }()))
		{
			$type = "";

		}

		return new Index($indexName, $columns, $type);
	}


}