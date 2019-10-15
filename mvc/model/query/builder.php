<?php
namespace Phalcon\Mvc\Model\Query;

use Phalcon\Di;
use Phalcon\Db\Column;
use Phalcon\DiInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Model\QueryInterface;
use Phalcon\Mvc\Model\Query\BuilderInterface;

class Builder implements BuilderInterface, InjectionAwareInterface
{
	protected $_dependencyInjector;
	protected $_columns;
	protected $_models;
	protected $_joins;
	protected $_with;
	protected $_conditions;
	protected $_group;
	protected $_having;
	protected $_order;
	protected $_limit;
	protected $_offset;
	protected $_forUpdate;
	protected $_sharedLock;
	protected $_bindParams;
	protected $_bindTypes;
	protected $_distinct;
	protected $_hiddenParamNumber = 0;

	public function __construct($params = null, $dependencyInjector = null)
	{

		if (typeof($params) == "array")
		{
			if (function() { if(isset($params[0])) {$conditions = $params[0]; return $conditions; } else { return false; } }())
			{
				$this->_conditions = $conditions;

			}

			if (typeof($conditions) == "array")
			{
				$mergedConditions = [];

				$mergedParams = [];

				$mergedTypes = [];

				foreach ($conditions as $singleConditionArray) {
					if (typeof($singleConditionArray) == "array")
					{
						$singleCondition = $singleConditionArray[0]
						$singleParams = $singleConditionArray[1]
						$singleTypes = $singleConditionArray[2]
						if (typeof($singleCondition) == "string")
						{
							$mergedConditions = $singleCondition;

						}

						if (typeof($singleParams) == "array")
						{
							$mergedParams = $mergedParams + $singleParams;

						}

						if (typeof($singleTypes) == "array")
						{
							$mergedTypes = $mergedTypes + $singleTypes;

						}

					}
				}

				$this->_conditions = implode(" AND ", $mergedConditions);

				if (typeof($mergedParams) == "array")
				{
					$this->_bindParams = $mergedParams;

				}

				if (typeof($mergedTypes) == "array")
				{
					$this->_bindTypes = $mergedTypes;

				}

			}

			if (function() { if(isset($params["bind"])) {$bind = $params["bind"]; return $bind; } else { return false; } }())
			{
				$this->_bindParams = $bind;

			}

			if (function() { if(isset($params["bindTypes"])) {$bindTypes = $params["bindTypes"]; return $bindTypes; } else { return false; } }())
			{
				$this->_bindTypes = $bindTypes;

			}

			if (function() { if(isset($params["distinct"])) {$distinct = $params["distinct"]; return $distinct; } else { return false; } }())
			{
				$this->_distinct = $distinct;

			}

			if (function() { if(isset($params["models"])) {$fromClause = $params["models"]; return $fromClause; } else { return false; } }())
			{
				$this->_models = $fromClause;

			}

			if (function() { if(isset($params["columns"])) {$columns = $params["columns"]; return $columns; } else { return false; } }())
			{
				$this->_columns = $columns;

			}

			if (function() { if(isset($params["joins"])) {$joinsClause = $params["joins"]; return $joinsClause; } else { return false; } }())
			{
				$this->_joins = $joinsClause;

			}

			if (function() { if(isset($params["group"])) {$groupClause = $params["group"]; return $groupClause; } else { return false; } }())
			{
				$this->_group = $groupClause;

			}

			if (function() { if(isset($params["having"])) {$havingClause = $params["having"]; return $havingClause; } else { return false; } }())
			{
				$this->_having = $havingClause;

			}

			if (function() { if(isset($params["order"])) {$orderClause = $params["order"]; return $orderClause; } else { return false; } }())
			{
				$this->_order = $orderClause;

			}

			if (function() { if(isset($params["limit"])) {$limitClause = $params["limit"]; return $limitClause; } else { return false; } }())
			{
				if (typeof($limitClause) == "array")
				{
					if (function() { if(isset($limitClause[0])) {$limit = $limitClause[0]; return $limit; } else { return false; } }())
					{
						if (is_int($limit))
						{
							$this->_limit = $limit;

						}

						if (function() { if(isset($limitClause[1])) {$offset = $limitClause[1]; return $offset; } else { return false; } }())
						{
							if (is_int($offset))
							{
								$this->_offset = $offset;

							}

						}

					}

				}

			}

			if (function() { if(isset($params["offset"])) {$offsetClause = $params["offset"]; return $offsetClause; } else { return false; } }())
			{
				$this->_offset = $offsetClause;

			}

			if (function() { if(isset($params["for_update"])) {$forUpdate = $params["for_update"]; return $forUpdate; } else { return false; } }())
			{
				$this->_forUpdate = $forUpdate;

			}

			if (function() { if(isset($params["shared_lock"])) {$sharedLock = $params["shared_lock"]; return $sharedLock; } else { return false; } }())
			{
				$this->_sharedLock = $sharedLock;

			}

		}

		if (typeof($dependencyInjector) == "object")
		{
			$this->_dependencyInjector = $dependencyInjector;

		}

	}

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

		return $this;
	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	public function distinct($distinct)
	{
		$this->_distinct = $distinct;

		return $this;
	}

	public function getDistinct()
	{
		return $this->_distinct;
	}

	public function columns($columns)
	{
		$this->_columns = $columns;

		return $this;
	}

	public function getColumns()
	{
		return $this->_columns;
	}

	public function from($models)
	{
		$this->_models = $models;

		return $this;
	}

	public function addFrom($model, $alias = null, $with = null)
	{

		if (typeof($with) <> "null")
		{
			trigger_error("The third parameter 'with' is deprecated and will be removed in future releases.", E_DEPRECATED);

		}

		$models = $this->_models;

		if (typeof($models) <> "array")
		{
			if (typeof($models) <> "null")
			{
				$currentModel = $models;
				$models = [$currentModel];

			}

		}

		if (typeof($alias) == "string")
		{
			$models[$alias] = $model;

		}

		$this->_models = $models;

		return $this;
	}

	public function getFrom()
	{
		return $this->_models;
	}

	public function join($model, $conditions = null, $alias = null, $type = null)
	{
		$this->_joins[] = [$model, $conditions, $alias, $type];

		return $this;
	}

	public function innerJoin($model, $conditions = null, $alias = null)
	{
		$this->_joins[] = [$model, $conditions, $alias, "INNER"];

		return $this;
	}

	public function leftJoin($model, $conditions = null, $alias = null)
	{
		$this->_joins[] = [$model, $conditions, $alias, "LEFT"];

		return $this;
	}

	public function rightJoin($model, $conditions = null, $alias = null)
	{
		$this->_joins[] = [$model, $conditions, $alias, "RIGHT"];

		return $this;
	}

	public function getJoins()
	{
		return $this->_joins;
	}

	public function where($conditions, $bindParams = null, $bindTypes = null)
	{

		$this->_conditions = $conditions;

		if (typeof($bindParams) == "array")
		{
			$currentBindParams = $this->_bindParams;

			if (typeof($currentBindParams) == "array")
			{
				$this->_bindParams = $currentBindParams + $bindParams;

			}

		}

		if (typeof($bindTypes) == "array")
		{
			$currentBindTypes = $this->_bindTypes;

			if (typeof($currentBindTypes) == "array")
			{
				$this->_bindTypes = $currentBindTypes + $bindTypes;

			}

		}

		return $this;
	}

	public function andWhere($conditions, $bindParams = null, $bindTypes = null)
	{

		$currentConditions = $this->_conditions;

		if ($currentConditions)
		{
			$conditions = "(" . $currentConditions . ") AND (" . $conditions . ")";

		}

		return $this->where($conditions, $bindParams, $bindTypes);
	}

	public function orWhere($conditions, $bindParams = null, $bindTypes = null)
	{

		$currentConditions = $this->_conditions;

		if ($currentConditions)
		{
			$conditions = "(" . $currentConditions . ") OR (" . $conditions . ")";

		}

		return $this->where($conditions, $bindParams, $bindTypes);
	}

	public function betweenWhere($expr, $minimum, $maximum, $operator = BuilderInterface::OPERATOR_AND)
	{
		return $this->_conditionBetween("Where", $operator, $expr, $minimum, $maximum);
	}

	public function notBetweenWhere($expr, $minimum, $maximum, $operator = BuilderInterface::OPERATOR_AND)
	{
		return $this->_conditionNotBetween("Where", $operator, $expr, $minimum, $maximum);
	}

	public function inWhere($expr, $values, $operator = BuilderInterface::OPERATOR_AND)
	{
		return $this->_conditionIn("Where", $operator, $expr, $values);
	}

	public function notInWhere($expr, $values, $operator = BuilderInterface::OPERATOR_AND)
	{
		return $this->_conditionNotIn("Where", $operator, $expr, $values);
	}

	public function getWhere()
	{
		return $this->_conditions;
	}

	public function orderBy($orderBy)
	{
		$this->_order = $orderBy;

		return $this;
	}

	public function getOrderBy()
	{
		return $this->_order;
	}

	public function having($conditions, $bindParams = null, $bindTypes = null)
	{

		$this->_having = $conditions;

		if (typeof($bindParams) == "array")
		{
			$currentBindParams = $this->_bindParams;

			if (typeof($currentBindParams) == "array")
			{
				$this->_bindParams = $currentBindParams + $bindParams;

			}

		}

		if (typeof($bindTypes) == "array")
		{
			$currentBindTypes = $this->_bindTypes;

			if (typeof($currentBindTypes) == "array")
			{
				$this->_bindTypes = $currentBindTypes + $bindTypes;

			}

		}

		return $this;
	}

	public function andHaving($conditions, $bindParams = null, $bindTypes = null)
	{

		$currentConditions = $this->_having;

		if ($currentConditions)
		{
			$conditions = "(" . $currentConditions . ") AND (" . $conditions . ")";

		}

		return $this->having($conditions, $bindParams, $bindTypes);
	}

	public function orHaving($conditions, $bindParams = null, $bindTypes = null)
	{

		$currentConditions = $this->_having;

		if ($currentConditions)
		{
			$conditions = "(" . $currentConditions . ") OR (" . $conditions . ")";

		}

		return $this->having($conditions, $bindParams, $bindTypes);
	}

	public function betweenHaving($expr, $minimum, $maximum, $operator = BuilderInterface::OPERATOR_AND)
	{
		return $this->_conditionBetween("Having", $operator, $expr, $minimum, $maximum);
	}

	public function notBetweenHaving($expr, $minimum, $maximum, $operator = BuilderInterface::OPERATOR_AND)
	{
		return $this->_conditionNotBetween("Having", $operator, $expr, $minimum, $maximum);
	}

	public function inHaving($expr, $values, $operator = BuilderInterface::OPERATOR_AND)
	{
		return $this->_conditionIn("Having", $operator, $expr, $values);
	}

	public function notInHaving($expr, $values, $operator = BuilderInterface::OPERATOR_AND)
	{
		return $this->_conditionNotIn("Having", $operator, $expr, $values);
	}

	public function getHaving()
	{
		return $this->_having;
	}

	public function forUpdate($forUpdate)
	{
		$this->_forUpdate = $forUpdate;

		return $this;
	}

	public function limit($limit, $offset = null)
	{
		$limit = abs($limit);

		if ($limit == 0)
		{
			return $this;
		}

		$this->_limit = $limit;

		if (is_numeric($offset))
		{
			$this->_offset = abs((int) $offset);

		}

		return $this;
	}

	public function getLimit()
	{
		return $this->_limit;
	}

	public function offset($offset)
	{
		$this->_offset = $offset;

		return $this;
	}

	public function getOffset()
	{
		return $this->_offset;
	}

	public function groupBy($group)
	{
		$this->_group = $group;

		return $this;
	}

	public function getGroupBy()
	{
		return $this->_group;
	}

	public final function getPhql()
	{


		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = Di::getDefault();
			$this->_dependencyInjector = $dependencyInjector;

		}

		$models = $this->_models;

		if (typeof($models) == "array")
		{
			if (!(count($models)))
			{
				throw new Exception("At least one model is required to build the query");
			}

		}

		$conditions = $this->_conditions;

		if (is_numeric($conditions))
		{
			if (typeof($models) == "array")
			{
				if (count($models) > 1)
				{
					throw new Exception("Cannot build the query. Invalid condition");
				}

				$model = $models[0];

			}

			$metaData = $dependencyInjector->getShared("modelsMetadata");
			$modelInstance = new $model(null, $dependencyInjector);

			$noPrimary = true;
			$primaryKeys = $metaData->getPrimaryKeyAttributes($modelInstance);

			if (count($primaryKeys))
			{
				if (function() { if(isset($primaryKeys[0])) {$firstPrimaryKey = $primaryKeys[0]; return $firstPrimaryKey; } else { return false; } }())
				{
					if (globals_get("orm.column_renaming"))
					{
						$columnMap = $metaData->getColumnMap($modelInstance);

					}

					if (typeof($columnMap) == "array")
					{
						if (!(function() { if(isset($columnMap[$firstPrimaryKey])) {$attributeField = $columnMap[$firstPrimaryKey]; return $attributeField; } else { return false; } }()))
						{
							throw new Exception("Column '" . $firstPrimaryKey . "' isn't part of the column map");
						}

					}

					$conditions = $this->autoescape($model) . "." . $this->autoescape($attributeField) . " = " . $conditions;
					$noPrimary = false;

				}

			}

			if ($noPrimary === true)
			{
				throw new Exception("Source related to this model does not have a primary key defined");
			}

		}

		$distinct = $this->_distinct;

		if (typeof($distinct) <> "null" && typeof($distinct) == "bool")
		{
			if ($distinct)
			{
				$phql = "SELECT DISTINCT ";

			}

		}

		$columns = $this->_columns;

		if (typeof($columns) !== "null")
		{
			if (typeof($columns) == "array")
			{
				$selectedColumns = [];

				foreach ($columns as $columnAlias => $column) {
					if (typeof($columnAlias) == "integer")
					{
						$selectedColumns = $column;

					}
				}

				$phql .= join(", ", $selectedColumns);

			}

		}

		if (typeof($models) == "array")
		{
			$selectedModels = [];

			foreach ($models as $modelAlias => $model) {
				if (typeof($modelAlias) == "string")
				{
					$selectedModel = $this->autoescape($model) . " AS " . $this->autoescape($modelAlias);

				}
				$selectedModels = $selectedModel;
			}

			$phql .= " FROM " . join(", ", $selectedModels);

		}

		$joins = $this->_joins;

		if (typeof($joins) == "array")
		{
			foreach ($joins as $join) {
				$joinModel = $join[0];
				$joinConditions = $join[1];
				$joinAlias = $join[2];
				$joinType = $join[3];
				if ($joinType)
				{
					$phql .= " " . $joinType . " JOIN " . $this->autoescape($joinModel);

				}
				if ($joinAlias)
				{
					$phql .= " AS " . $this->autoescape($joinAlias);

				}
				if ($joinConditions)
				{
					$phql .= " ON " . $joinConditions;

				}
			}

		}

		if (typeof($conditions) == "string")
		{
			if (!(empty($conditions)))
			{
				$phql .= " WHERE " . $conditions;

			}

		}

		$group = $this->_group;

		if ($group !== null)
		{
			if (typeof($group) == "string")
			{
				if (memstr($group, ","))
				{
					$group = str_replace(" ", "", $group);

				}

				$group = explode(",", $group);

			}

			$groupItems = [];

			foreach ($group as $groupItem) {
				$groupItems = $this->autoescape($groupItem);
			}

			$phql .= " GROUP BY " . join(", ", $groupItems);

		}

		$having = $this->_having;

		if ($having !== null)
		{
			if (!(empty($having)))
			{
				$phql .= " HAVING " . $having;

			}

		}

		$order = $this->_order;

		if ($order !== null)
		{
			if (typeof($order) == "array")
			{
				$orderItems = [];

				foreach ($order as $orderItem) {
					if (typeof($orderItem) == "integer")
					{
						$orderItems = $orderItem;

						continue;

					}
					if (memstr($orderItem, " ") !== 0)
					{

						$itemExplode = explode(" ", $orderItem);

						$orderItems = $this->autoescape($itemExplode[0]) . " " . $itemExplode[1];

						continue;

					}
					$orderItems = $this->autoescape($orderItem);
				}

				$phql .= " ORDER BY " . join(", ", $orderItems);

			}

		}

		$limit = $this->_limit;

		if ($limit !== null)
		{
			$number = null;

			if (typeof($limit) == "array")
			{
				$number = $limit["number"];

				if (function() { if(isset($limit["offset"])) {$offset = $limit["offset"]; return $offset; } else { return false; } }())
				{
					if (!(is_numeric($offset)))
					{
						$offset = 0;

					}

				}

			}

			if (is_numeric($number))
			{
				$phql .= " LIMIT :APL0:";
				$this["APL0"] = intval($number, 10);
				$this["APL0"] = Column::BIND_PARAM_INT;

				if (is_numeric($offset))
				{
					$phql .= " OFFSET :APL1:";
					$this["APL1"] = intval($offset, 10);
					$this["APL1"] = Column::BIND_PARAM_INT;

				}

			}

		}

		$forUpdate = $this->_forUpdate;

		if (typeof($forUpdate) === "boolean")
		{
			if ($forUpdate)
			{
				$phql .= " FOR UPDATE";

			}

		}

		return $phql;
	}

	public function getQuery()
	{

		$phql = $this->getPhql();

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection object is required to access ORM services");
		}

		$query = $dependencyInjector->get("Phalcon\\Mvc\\Model\\Query", [$phql, $dependencyInjector]);

		$bindParams = $this->_bindParams;

		if (typeof($bindParams) == "array")
		{
			$query->setBindParams($bindParams);

		}

		$bindTypes = $this->_bindTypes;

		if (typeof($bindTypes) == "array")
		{
			$query->setBindTypes($bindTypes);

		}

		if (typeof($this->_sharedLock) === "boolean")
		{
			$query->setSharedLock($this->_sharedLock);

		}

		return $query;
	}

	final public function autoescape($identifier)
	{
		if (memstr($identifier, "[") || memstr($identifier, ".") || is_numeric($identifier))
		{
			return $identifier;
		}

		return "[" . $identifier . "]";
	}

	protected function _conditionBetween($clause, $operator, $expr, $minimum, $maximum)
	{

		if ($operator !== Builder::OPERATOR_AND && $operator !== Builder::OPERATOR_OR)
		{
			throw new Exception(sprintf("Operator % is not available.", $operator));
		}

		$operatorMethod = $operator . $clause;

		$hiddenParam = $this->_hiddenParamNumber;
		$nextHiddenParam = $hiddenParam + 1;

		$minimumKey = "AP" . $hiddenParam;
		$maximumKey = "AP" . $nextHiddenParam;

		$this->operatorMethod($expr . " BETWEEN :" . $minimumKey . ": AND :" . $maximumKey . ":", [$minimumKey => $minimum, $maximumKey => $maximum]);

		$nextHiddenParam++;
		$this->_hiddenParamNumber = $nextHiddenParam;

		return $this;
	}

	protected function _conditionNotBetween($clause, $operator, $expr, $minimum, $maximum)
	{

		if ($operator !== Builder::OPERATOR_AND && $operator !== Builder::OPERATOR_OR)
		{
			throw new Exception(sprintf("Operator % is not available.", $operator));
		}

		$operatorMethod = $operator . $clause;

		$hiddenParam = $this->_hiddenParamNumber;
		$nextHiddenParam = $hiddenParam + 1;

		$minimumKey = "AP" . $hiddenParam;
		$maximumKey = "AP" . $nextHiddenParam;

		$this->operatorMethod($expr . " NOT BETWEEN :" . $minimumKey . ": AND :" . $maximumKey . ":", [$minimumKey => $minimum, $maximumKey => $maximum]);

		$nextHiddenParam++;
		$this->_hiddenParamNumber = $nextHiddenParam;

		return $this;
	}

	protected function _conditionIn($clause, $operator, $expr, $values)
	{


		if ($operator !== Builder::OPERATOR_AND && $operator !== Builder::OPERATOR_OR)
		{
			throw new Exception(sprintf("Operator % is not available.", $operator));
		}

		$operatorMethod = $operator . $clause;

		if (!(count($values)))
		{
			$this->operatorMethod($expr . " != " . $expr);

			return $this;
		}

		$hiddenParam = (int) $this->_hiddenParamNumber;

		$bindParams = [];
		$bindKeys = [];

		foreach ($values as $value) {
			$key = "AP" . $hiddenParam;
			$queryKey = ":" . $key . ":";
			$bindKeys = $queryKey;
			$bindParams[$key] = $value;
			$hiddenParam++;
		}

		$this->operatorMethod($expr . " IN (" . join(", ", $bindKeys) . ")", $bindParams);

		$this->_hiddenParamNumber = $hiddenParam;

		return $this;
	}

	protected function _conditionNotIn($clause, $operator, $expr, $values)
	{


		if ($operator !== Builder::OPERATOR_AND && $operator !== Builder::OPERATOR_OR)
		{
			throw new Exception(sprintf("Operator % is not available.", $operator));
		}

		$operatorMethod = $operator . $clause;

		if (!(count($values)))
		{
			$this->operatorMethod($expr . " != " . $expr);

			return $this;
		}

		$hiddenParam = (int) $this->_hiddenParamNumber;

		$bindParams = [];
		$bindKeys = [];

		foreach ($values as $value) {
			$key = "AP" . $hiddenParam;
			$queryKey = ":" . $key . ":";
			$bindKeys = $queryKey;
			$bindParams[$key] = $value;
			$hiddenParam++;
		}

		$this->operatorMethod($expr . " NOT IN (" . join(", ", $bindKeys) . ")", $bindParams);

		$this->_hiddenParamNumber = $hiddenParam;

		return $this;
	}


}