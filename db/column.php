<?php
namespace Phalcon\Db;

use Phalcon\Db\Exception;
use Phalcon\Db\ColumnInterface;

class Column implements ColumnInterface
{
	const TYPE_INTEGER = 0;
	const TYPE_DATE = 1;
	const TYPE_VARCHAR = 2;
	const TYPE_DECIMAL = 3;
	const TYPE_DATETIME = 4;
	const TYPE_CHAR = 5;
	const TYPE_TEXT = 6;
	const TYPE_FLOAT = 7;
	const TYPE_BOOLEAN = 8;
	const TYPE_DOUBLE = 9;
	const TYPE_TINYBLOB = 10;
	const TYPE_BLOB = 11;
	const TYPE_MEDIUMBLOB = 12;
	const TYPE_LONGBLOB = 13;
	const TYPE_BIGINTEGER = 14;
	const TYPE_JSON = 15;
	const TYPE_JSONB = 16;
	const TYPE_TIMESTAMP = 17;
	const BIND_PARAM_NULL = 0;
	const BIND_PARAM_INT = 1;
	const BIND_PARAM_STR = 2;
	const BIND_PARAM_BLOB = 3;
	const BIND_PARAM_BOOL = 5;
	const BIND_PARAM_DECIMAL = 32;
	const BIND_SKIP = 1024;

	protected $_name;
	protected $_schemaName;
	protected $_type;
	protected $_typeReference = -1;
	protected $_typeValues;
	protected $_isNumeric = false;
	protected $_size = 0;
	protected $_scale = 0;
	protected $_default = null;
	protected $_unsigned = false;
	protected $_notNull = false;
	protected $_primary = false;
	protected $_autoIncrement = false;
	protected $_first = false;
	protected $_after;
	protected $_bindType = 2;

	public function __construct($name, $definition)
	{

		$this->_name = $name;

		if (function() { if(isset($definition["type"])) {$type = $definition["type"]; return $type; } else { return false; } }())
		{
			$this->_type = $type;

		}

		if (function() { if(isset($definition["typeReference"])) {$typeReference = $definition["typeReference"]; return $typeReference; } else { return false; } }())
		{
			$this->_typeReference = $typeReference;

		}

		if (function() { if(isset($definition["typeValues"])) {$typeValues = $definition["typeValues"]; return $typeValues; } else { return false; } }())
		{
			$this->_typeValues = $typeValues;

		}

		if (function() { if(isset($definition["notNull"])) {$notNull = $definition["notNull"]; return $notNull; } else { return false; } }())
		{
			$this->_notNull = $notNull;

		}

		if (function() { if(isset($definition["primary"])) {$primary = $definition["primary"]; return $primary; } else { return false; } }())
		{
			$this->_primary = $primary;

		}

		if (function() { if(isset($definition["size"])) {$size = $definition["size"]; return $size; } else { return false; } }())
		{
			$this->_size = $size;

		}

		if (function() { if(isset($definition["scale"])) {$scale = $definition["scale"]; return $scale; } else { return false; } }())
		{
			switch ($type) {
				case self::TYPE_INTEGER:

				case self::TYPE_FLOAT:

				case self::TYPE_DECIMAL:

				case self::TYPE_DOUBLE:

				case self::TYPE_BIGINTEGER:
					$this->_scale = $scale;
					break;

				default:
					throw new Exception("Column type does not support scale parameter");

			}

		}

		if (function() { if(isset($definition["default"])) {$defaultValue = $definition["default"]; return $defaultValue; } else { return false; } }())
		{
			$this->_default = $defaultValue;

		}

		if (function() { if(isset($definition["unsigned"])) {$dunsigned = $definition["unsigned"]; return $dunsigned; } else { return false; } }())
		{
			$this->_unsigned = $dunsigned;

		}

		if (function() { if(isset($definition["isNumeric"])) {$isNumeric = $definition["isNumeric"]; return $isNumeric; } else { return false; } }())
		{
			$this->_isNumeric = $isNumeric;

		}

		if (function() { if(isset($definition["autoIncrement"])) {$autoIncrement = $definition["autoIncrement"]; return $autoIncrement; } else { return false; } }())
		{
			if (!($autoIncrement))
			{
				$this->_autoIncrement = false;

			}

		}

		if (function() { if(isset($definition["first"])) {$first = $definition["first"]; return $first; } else { return false; } }())
		{
			$this->_first = $first;

		}

		if (function() { if(isset($definition["after"])) {$after = $definition["after"]; return $after; } else { return false; } }())
		{
			$this->_after = $after;

		}

		if (function() { if(isset($definition["bindType"])) {$bindType = $definition["bindType"]; return $bindType; } else { return false; } }())
		{
			$this->_bindType = $bindType;

		}

	}

	public function isUnsigned()
	{
		return $this->_unsigned;
	}

	public function isNotNull()
	{
		return $this->_notNull;
	}

	public function isPrimary()
	{
		return $this->_primary;
	}

	public function isAutoIncrement()
	{
		return $this->_autoIncrement;
	}

	public function isNumeric()
	{
		return $this->_isNumeric;
	}

	public function isFirst()
	{
		return $this->_first;
	}

	public function getAfterPosition()
	{
		return $this->_after;
	}

	public function getBindType()
	{
		return $this->_bindType;
	}

	public static function __set_state($data)
	{

		if (!(function() { if(isset($data["_columnName"])) {$columnName = $data["_columnName"]; return $columnName; } else { return false; } }()))
		{
			if (!(function() { if(isset($data["_name"])) {$columnName = $data["_name"]; return $columnName; } else { return false; } }()))
			{
				throw new Exception("Column name is required");
			}

		}

		$definition = [];

		if (function() { if(isset($data["_type"])) {$columnType = $data["_type"]; return $columnType; } else { return false; } }())
		{
			$definition["type"] = $columnType;

		}

		if (function() { if(isset($data["_typeReference"])) {$columnTypeReference = $data["_typeReference"]; return $columnTypeReference; } else { return false; } }())
		{
			$definition["typeReference"] = $columnTypeReference;

		}

		if (function() { if(isset($data["_typeValues"])) {$columnTypeValues = $data["_typeValues"]; return $columnTypeValues; } else { return false; } }())
		{
			$definition["typeValues"] = $columnTypeValues;

		}

		if (function() { if(isset($data["_notNull"])) {$notNull = $data["_notNull"]; return $notNull; } else { return false; } }())
		{
			$definition["notNull"] = $notNull;

		}

		if (function() { if(isset($data["_primary"])) {$primary = $data["_primary"]; return $primary; } else { return false; } }())
		{
			$definition["primary"] = $primary;

		}

		if (function() { if(isset($data["_size"])) {$size = $data["_size"]; return $size; } else { return false; } }())
		{
			$definition["size"] = $size;

		}

		if (function() { if(isset($data["_scale"])) {$scale = $data["_scale"]; return $scale; } else { return false; } }())
		{
			switch ($definition["type"]) {
				case self::TYPE_INTEGER:

				case self::TYPE_FLOAT:

				case self::TYPE_DECIMAL:

				case self::TYPE_DOUBLE:

				case self::TYPE_BIGINTEGER:
					$definition["scale"] = $scale;
					break;


			}

		}

		if (function() { if(isset($data["_default"])) {$defaultValue = $data["_default"]; return $defaultValue; } else { return false; } }())
		{
			$definition["default"] = $defaultValue;

		}

		if (function() { if(isset($data["_unsigned"])) {$dunsigned = $data["_unsigned"]; return $dunsigned; } else { return false; } }())
		{
			$definition["unsigned"] = $dunsigned;

		}

		if (function() { if(isset($data["_autoIncrement"])) {$autoIncrement = $data["_autoIncrement"]; return $autoIncrement; } else { return false; } }())
		{
			$definition["autoIncrement"] = $autoIncrement;

		}

		if (function() { if(isset($data["_isNumeric"])) {$isNumeric = $data["_isNumeric"]; return $isNumeric; } else { return false; } }())
		{
			$definition["isNumeric"] = $isNumeric;

		}

		if (function() { if(isset($data["_first"])) {$first = $data["_first"]; return $first; } else { return false; } }())
		{
			$definition["first"] = $first;

		}

		if (function() { if(isset($data["_after"])) {$after = $data["_after"]; return $after; } else { return false; } }())
		{
			$definition["after"] = $after;

		}

		if (function() { if(isset($data["_bindType"])) {$bindType = $data["_bindType"]; return $bindType; } else { return false; } }())
		{
			$definition["bindType"] = $bindType;

		}

		return new self($columnName, $definition);
	}

	public function hasDefault()
	{
		if ($this->isAutoIncrement())
		{
			return false;
		}

		return $this->_default !== null;
	}


}