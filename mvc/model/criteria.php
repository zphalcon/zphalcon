<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Di;
use Phalcon\Db\Column;
use Phalcon\DiInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Model\CriteriaInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\Query\BuilderInterface;

class Criteria implements CriteriaInterface, InjectionAwareInterface
{
	protected $_model;
	protected $_params;
	protected $_bindParams;
	protected $_bindTypes;
	protected $_hiddenParamNumber = 0;

	public function setDI($dependencyInjector)
	{
		$this["di"] = $dependencyInjector;

	}

	public function getDI()
	{

		if (function() { if(isset($this->_params["di"])) {$dependencyInjector = $this->_params["di"]; return $dependencyInjector; } else { return false; } }())
		{
			return $dependencyInjector;
		}

		return null;
	}

	public function setModelName($modelName)
	{
		$this->_model = $modelName;

		return $this;
	}

	public function getModelName()
	{
		return $this->_model;
	}

	public function bind($bindParams, $merge = false)
	{

		if ($merge)
		{
			if (isset($this->_params["bind"]))
			{
				$bind = $this->_params["bind"];

			}

			if (typeof($bind) == "array")
			{
				$this["bind"] = $bind + $bindParams;

			}

		}

		return $this;
	}

	public function bindTypes($bindTypes)
	{
		$this["bindTypes"] = $bindTypes;

		return $this;
	}

	public function distinct($distinct)
	{
		$this["distinct"] = $distinct;

		return $this;
	}

	public function columns($columns)
	{
		$this["columns"] = $columns;

		return $this;
	}

	public function join($model, $conditions = null, $alias = null, $type = null)
	{

		$join = [$model, $conditions, $alias, $type];

		if (function() { if(isset($this->_params["joins"])) {$currentJoins = $this->_params["joins"]; return $currentJoins; } else { return false; } }())
		{
			if (typeof($currentJoins) == "array")
			{
				$mergedJoins = array_merge($currentJoins, [$join]);

			}

		}

		$this["joins"] = $mergedJoins;

		return $this;
	}

	public function innerJoin($model, $conditions = null, $alias = null)
	{
		return $this->join($model, $conditions, $alias, "INNER");
	}

	public function leftJoin($model, $conditions = null, $alias = null)
	{
		return $this->join($model, $conditions, $alias, "LEFT");
	}

	public function rightJoin($model, $conditions = null, $alias = null)
	{
		return $this->join($model, $conditions, $alias, "RIGHT");
	}

	public function where($conditions, $bindParams = null, $bindTypes = null)
	{

		$this["conditions"] = $conditions;

		if (typeof($bindParams) == "array")
		{
			if (function() { if(isset($this->_params["bind"])) {$currentBindParams = $this->_params["bind"]; return $currentBindParams; } else { return false; } }())
			{
				$this["bind"] = array_merge($currentBindParams, $bindParams);

			}

		}

		if (typeof($bindTypes) == "array")
		{
			if (function() { if(isset($this->_params["bindTypes"])) {$currentBindTypes = $this->_params["bindTypes"]; return $currentBindTypes; } else { return false; } }())
			{
				$this["bindTypes"] = array_merge($currentBindTypes, $bindTypes);

			}

		}

		return $this;
	}

	deprecated public function addWhere($conditions, $bindParams = null, $bindTypes = null)
	{
		return $this->andWhere($conditions, $bindParams, $bindTypes);
	}

	public function andWhere($conditions, $bindParams = null, $bindTypes = null)
	{

		if (function() { if(isset($this->_params["conditions"])) {$currentConditions = $this->_params["conditions"]; return $currentConditions; } else { return false; } }())
		{
			$conditions = "(" . $currentConditions . ") AND (" . $conditions . ")";

		}

		return $this->where($conditions, $bindParams, $bindTypes);
	}

	public function orWhere($conditions, $bindParams = null, $bindTypes = null)
	{

		if (function() { if(isset($this->_params["conditions"])) {$currentConditions = $this->_params["conditions"]; return $currentConditions; } else { return false; } }())
		{
			$conditions = "(" . $currentConditions . ") OR (" . $conditions . ")";

		}

		return $this->where($conditions, $bindParams, $bindTypes);
	}

	public function betweenWhere($expr, $minimum, $maximum)
	{

		$hiddenParam = $this->_hiddenParamNumber;
		$nextHiddenParam = $hiddenParam + 1;

		$minimumKey = "ACP" . $hiddenParam;

		$maximumKey = "ACP" . $nextHiddenParam;

		$this->andWhere($expr . " BETWEEN :" . $minimumKey . ": AND :" . $maximumKey . ":", [$minimumKey => $minimum, $maximumKey => $maximum]);

		$nextHiddenParam++;
		$this->_hiddenParamNumber = $nextHiddenParam;

		return $this;
	}

	public function notBetweenWhere($expr, $minimum, $maximum)
	{

		$hiddenParam = $this->_hiddenParamNumber;

		$nextHiddenParam = $hiddenParam + 1;

		$minimumKey = "ACP" . $hiddenParam;

		$maximumKey = "ACP" . $nextHiddenParam;

		$this->andWhere($expr . " NOT BETWEEN :" . $minimumKey . ": AND :" . $maximumKey . ":", [$minimumKey => $minimum, $maximumKey => $maximum]);

		$nextHiddenParam++;

		$this->_hiddenParamNumber = $nextHiddenParam;

		return $this;
	}

	public function inWhere($expr, $values)
	{

		if (!(count($values)))
		{
			$this->andWhere($expr . " != " . $expr);

			return $this;
		}

		$hiddenParam = $this->_hiddenParamNumber;

		$bindParams = [];
		$bindKeys = [];

		foreach ($values as $value) {
			$key = "ACP" . $hiddenParam;
			$queryKey = ":" . $key . ":";
			$bindKeys = $queryKey;
			$bindParams[$key] = $value;
			$hiddenParam++;
		}

		$this->andWhere($expr . " IN (" . join(", ", $bindKeys) . ")", $bindParams);

		$this->_hiddenParamNumber = $hiddenParam;

		return $this;
	}

	public function notInWhere($expr, $values)
	{

		$hiddenParam = $this->_hiddenParamNumber;

		$bindParams = [];
		$bindKeys = [];

		foreach ($values as $value) {
			$key = "ACP" . $hiddenParam;
			$bindKeys = ":" . $key . ":";
			$bindParams[$key] = $value;
			$hiddenParam++;
		}

		$this->andWhere($expr . " NOT IN (" . join(", ", $bindKeys) . ")", $bindParams);

		$this->_hiddenParamNumber = $hiddenParam;

		return $this;
	}

	public function conditions($conditions)
	{
		$this["conditions"] = $conditions;

		return $this;
	}

	deprecated public function order($orderColumns)
	{
		$this["order"] = $orderColumns;

		return $this;
	}

	public function orderBy($orderColumns)
	{
		$this["order"] = $orderColumns;

		return $this;
	}

	public function groupBy($group)
	{
		$this["group"] = $group;

		return $this;
	}

	public function having($having)
	{
		$this["having"] = $having;

		return $this;
	}

	public function limit($limit, $offset = null)
	{
		$limit = abs($limit);

		if ($limit == 0)
		{
			return $this;
		}

		if (is_numeric($offset))
		{
			$offset = abs((int) $offset);

			$this["limit"] = ["number" => $limit, "offset" => $offset];

		}

		return $this;
	}

	public function forUpdate($forUpdate = true)
	{
		$this["for_update"] = $forUpdate;

		return $this;
	}

	public function sharedLock($sharedLock = true)
	{
		$this["shared_lock"] = $sharedLock;

		return $this;
	}

	public function cache($cache)
	{
		$this["cache"] = $cache;

		return $this;
	}

	public function getWhere()
	{

		if (function() { if(isset($this->_params["conditions"])) {$conditions = $this->_params["conditions"]; return $conditions; } else { return false; } }())
		{
			return $conditions;
		}

		return null;
	}

	public function getColumns()
	{

		if (function() { if(isset($this->_params["columns"])) {$columns = $this->_params["columns"]; return $columns; } else { return false; } }())
		{
			return $columns;
		}

		return null;
	}

	public function getConditions()
	{

		if (function() { if(isset($this->_params["conditions"])) {$conditions = $this->_params["conditions"]; return $conditions; } else { return false; } }())
		{
			return $conditions;
		}

		return null;
	}

	public function getLimit()
	{

		if (function() { if(isset($this->_params["limit"])) {$limit = $this->_params["limit"]; return $limit; } else { return false; } }())
		{
			return $limit;
		}

		return null;
	}

	public function getOrderBy()
	{

		if (function() { if(isset($this->_params["order"])) {$order = $this->_params["order"]; return $order; } else { return false; } }())
		{
			return $order;
		}

		return null;
	}

	public function getGroupBy()
	{

		if (function() { if(isset($this->_params["group"])) {$group = $this->_params["group"]; return $group; } else { return false; } }())
		{
			return $group;
		}

		return null;
	}

	public function getHaving()
	{

		if (function() { if(isset($this->_params["having"])) {$having = $this->_params["having"]; return $having; } else { return false; } }())
		{
			return $having;
		}

		return null;
	}

	public function getParams()
	{
		return $this->_params;
	}

	public static function fromInput($dependencyInjector, $modelName, $data, $operator = "AND")
	{

		$conditions = [];

		if (count($data))
		{
			$metaData = $dependencyInjector->getShared("modelsMetadata");

			$model = new $modelName(null, $dependencyInjector);
			$dataTypes = $metaData->getDataTypes($model);
			$columnMap = $metaData->getReverseColumnMap($model);

			$bind = [];

			foreach ($data as $field => $value) {
				if (typeof($columnMap) == "array" && count($columnMap))
				{
					$attribute = $columnMap[$field];

				}
				if (function() { if(isset($dataTypes[$attribute])) {$type = $dataTypes[$attribute]; return $type; } else { return false; } }())
				{
					if ($value !== null && $value !== "")
					{
						if ($type == Column::TYPE_VARCHAR)
						{
							$conditions = "[" . $field . "] LIKE :" . $field . ":";
							$bind[$field] = "%" . $value . "%";

							continue;

						}

						$conditions = "[" . $field . "] = :" . $field . ":";
						$bind[$field] = $value;

					}

				}
			}

		}

		$criteria = new self();

		if (count($conditions))
		{
			$criteria->where(join(" " . $operator . " ", $conditions));

			$criteria->bind($bind);

		}

		$criteria->setModelName($modelName);

		return $criteria;
	}

	public function createBuilder()
	{

		$dependencyInjector = $this->getDI();

		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = Di::getDefault();

			$this->setDI($dependencyInjector);

		}

		$manager = $dependencyInjector->getShared("modelsManager");

		$builder = $manager->createBuilder($this->_params);

		$builder->from($this->_model);

		return $builder;
	}

	public function execute()
	{

		$model = $this->getModelName();

		if (typeof($model) <> "string")
		{
			throw new Exception("Model name must be string");
		}

		return $model::find($this->getParams());
	}


}