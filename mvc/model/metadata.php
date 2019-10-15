<?php
namespace Phalcon\Mvc\Model;

use Phalcon\DiInterface;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Model\MetaData\Strategy\Introspection;
use Phalcon\Mvc\Model\MetaData\StrategyInterface;
abstract 
class MetaData implements InjectionAwareInterface, MetaDataInterface
{
	const MODELS_ATTRIBUTES = 0;
	const MODELS_PRIMARY_KEY = 1;
	const MODELS_NON_PRIMARY_KEY = 2;
	const MODELS_NOT_NULL = 3;
	const MODELS_DATA_TYPES = 4;
	const MODELS_DATA_TYPES_NUMERIC = 5;
	const MODELS_DATE_AT = 6;
	const MODELS_DATE_IN = 7;
	const MODELS_IDENTITY_COLUMN = 8;
	const MODELS_DATA_TYPES_BIND = 9;
	const MODELS_AUTOMATIC_DEFAULT_INSERT = 10;
	const MODELS_AUTOMATIC_DEFAULT_UPDATE = 11;
	const MODELS_DEFAULT_VALUES = 12;
	const MODELS_EMPTY_STRING_VALUES = 13;
	const MODELS_COLUMN_MAP = 0;
	const MODELS_REVERSE_COLUMN_MAP = 1;

	protected $_dependencyInjector;
	protected $_strategy;
	protected $_metaData;
	protected $_columnMap;

	protected final function _initialize($model, $key, $table, $schema)
	{

		$strategy = null;
		$className = get_class($model);

		if ($key !== null)
		{
			$metaData = $this->_metaData;

			if (!(isset($metaData[$key])))
			{
				$prefixKey = "meta-" . $key;
				$data = $this->read($prefixKey);

				if ($data !== null)
				{
					$this[$key] = $data;

				}

			}

		}

		if (!(globals_get("orm.column_renaming")))
		{
			return null;
		}

		$keyName = strtolower($className);

		if (isset($this->_columnMap[$keyName]))
		{
			return null;
		}

		$prefixKey = "map-" . $keyName;
		$data = $this->read($prefixKey);

		if ($data !== null)
		{
			$this[$keyName] = $data;

			return null;
		}

		if (typeof($strategy) <> "object")
		{
			$dependencyInjector = $this->_dependencyInjector;
			$strategy = $this->getStrategy();

		}

		$modelColumnMap = $strategy->getColumnMaps($model, $dependencyInjector);
		$this[$keyName] = $modelColumnMap;

		$this->write($prefixKey, $modelColumnMap);

	}

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	public function setStrategy($strategy)
	{
		$this->_strategy = $strategy;

	}

	public function getStrategy()
	{
		if (typeof($this->_strategy) == "null")
		{
			$this->_strategy = new Introspection();

		}

		return $this->_strategy;
	}

	public final function readMetaData($model)
	{

		$source = $model->getSource();
		$schema = $model->getSchema();

		$key = get_class_lower($model) . "-" . $schema . $source;

		if (!(isset($this->_metaData[$key])))
		{
			$this->_initialize($model, $key, $source, $schema);

		}

		return $this->_metaData[$key];
	}

	public final function readMetaDataIndex($model, $index)
	{

		$source = $model->getSource();
		$schema = $model->getSchema();

		$key = get_class_lower($model) . "-" . $schema . $source;

		if (!(isset($this->_metaData[$key][$index])))
		{
			$this->_initialize($model, $key, $source, $schema);

		}

		return $this->_metaData[$key][$index];
	}

	public final function writeMetaDataIndex($model, $index, $data)
	{

		if (typeof($data) <> "array" && typeof($data) <> "string" && typeof($data) <> "boolean")
		{
			throw new Exception("Invalid data for index");
		}

		$source = $model->getSource();
		$schema = $model->getSchema();

		$key = get_class_lower($model) . "-" . $schema . $source;

		if (!(isset($this->_metaData[$key])))
		{
			$this->_initialize($model, $key, $source, $schema);

		}

		$this[$key] = $data;

	}

	public final function readColumnMap($model)
	{

		if (!(globals_get("orm.column_renaming")))
		{
			return null;
		}

		$keyName = get_class_lower($model);

		if (!(function() { if(isset($this->_columnMap[$keyName])) {$data = $this->_columnMap[$keyName]; return $data; } else { return false; } }()))
		{
			$this->_initialize($model, null, null, null);

			$data = $this->_columnMap[$keyName];

		}

		return $data;
	}

	public final function readColumnMapIndex($model, $index)
	{

		if (!(globals_get("orm.column_renaming")))
		{
			return null;
		}

		$keyName = get_class_lower($model);

		if (!(function() { if(isset($this->_columnMap[$keyName])) {$columnMapModel = $this->_columnMap[$keyName]; return $columnMapModel; } else { return false; } }()))
		{
			$this->_initialize($model, null, null, null);

			$columnMapModel = $this->_columnMap[$keyName];

		}

		$map = $columnMapModel[$index]
		return $map;
	}

	public function getAttributes($model)
	{

		$data = $this->readMetaDataIndex($model, self::MODELS_ATTRIBUTES);

		if (typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function getPrimaryKeyAttributes($model)
	{

		$data = $this->readMetaDataIndex($model, self::MODELS_PRIMARY_KEY);

		if (typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function getNonPrimaryKeyAttributes($model)
	{

		$data = $this->readMetaDataIndex($model, self::MODELS_NON_PRIMARY_KEY);

		if (typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function getNotNullAttributes($model)
	{

		$data = $this->readMetaDataIndex($model, self::MODELS_NOT_NULL);

		if (typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function getDataTypes($model)
	{

		$data = $this->readMetaDataIndex($model, self::MODELS_DATA_TYPES);

		if (typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function getDataTypesNumeric($model)
	{

		$data = $this->readMetaDataIndex($model, self::MODELS_DATA_TYPES_NUMERIC);

		if (typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function getIdentityField($model)
	{
		return $this->readMetaDataIndex($model, self::MODELS_IDENTITY_COLUMN);
	}

	public function getBindTypes($model)
	{

		$data = $this->readMetaDataIndex($model, self::MODELS_DATA_TYPES_BIND);

		if (typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function getAutomaticCreateAttributes($model)
	{

		$data = $this->readMetaDataIndex($model, self::MODELS_AUTOMATIC_DEFAULT_INSERT);

		if (typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function getAutomaticUpdateAttributes($model)
	{

		$data = $this->readMetaDataIndex($model, self::MODELS_AUTOMATIC_DEFAULT_UPDATE);

		if (typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function setAutomaticCreateAttributes($model, $attributes)
	{
		$this->writeMetaDataIndex($model, self::MODELS_AUTOMATIC_DEFAULT_INSERT, $attributes);

	}

	public function setAutomaticUpdateAttributes($model, $attributes)
	{
		$this->writeMetaDataIndex($model, self::MODELS_AUTOMATIC_DEFAULT_UPDATE, $attributes);

	}

	public function setEmptyStringAttributes($model, $attributes)
	{
		$this->writeMetaDataIndex($model, self::MODELS_EMPTY_STRING_VALUES, $attributes);

	}

	public function getEmptyStringAttributes($model)
	{

		$data = $this->readMetaDataIndex($model, self::MODELS_EMPTY_STRING_VALUES);

		if (typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function getDefaultValues($model)
	{

		$data = $this->readMetaDataIndex($model, self::MODELS_DEFAULT_VALUES);

		if (typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function getColumnMap($model)
	{

		$data = $this->readColumnMapIndex($model, self::MODELS_COLUMN_MAP);

		if (typeof($data) <> "null" && typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function getReverseColumnMap($model)
	{

		$data = $this->readColumnMapIndex($model, self::MODELS_REVERSE_COLUMN_MAP);

		if (typeof($data) <> "null" && typeof($data) <> "array")
		{
			throw new Exception("The meta-data is invalid or is corrupt");
		}

		return $data;
	}

	public function hasAttribute($model, $attribute)
	{

		$columnMap = $this->getReverseColumnMap($model);

		if (typeof($columnMap) == "array")
		{
			return isset($columnMap[$attribute]);
		}

	}

	public function isEmpty()
	{
		return count($this->_metaData) == 0;
	}

	public function reset()
	{
		$this->_metaData = [];
		$this->_columnMap = [];

	}


}