<?php
namespace Phalcon\Db;

abstract 
class Dialect implements DialectInterface
{
	protected $_escapeChar;
	protected $_customFunctions;

	public function registerCustomFunction($name, $customFunction)
	{
		$this[$name] = $customFunction;

		return $this;
	}

	public function getCustomFunctions()
	{
		return $this->_customFunctions;
	}

	public final function escapeSchema($str, $escapeChar = null)
	{
		if (!(globals_get("db.escape_identifiers")))
		{
			return $str;
		}

		if ($escapeChar == "")
		{
			$escapeChar = (string) $this->_escapeChar;

		}

		return $escapeChar . trim($str, $escapeChar) . $escapeChar;
	}

	public final function escape($str, $escapeChar = null)
	{

		if (!(globals_get("db.escape_identifiers")))
		{
			return $str;
		}

		if ($escapeChar == "")
		{
			$escapeChar = (string) $this->_escapeChar;

		}

		if (!(memstr($str, ".")))
		{
			if ($escapeChar <> "" && $str <> "*")
			{
				return $escapeChar . str_replace($escapeChar, $escapeChar . $escapeChar, $str) . $escapeChar;
			}

			return $str;
		}

		$parts = (array) explode(".", trim($str, $escapeChar));

		$newParts = $parts;

		foreach ($parts as $key => $part) {
			if ($escapeChar == "" || $part == "" || $part == "*")
			{
				continue;

			}
			$newParts[$key] = $escapeChar . str_replace($escapeChar, $escapeChar . $escapeChar, $part) . $escapeChar;
		}

		return implode(".", $newParts);
	}

	public function limit($sqlQuery, $number)
	{
		if (typeof($number) == "array")
		{
			$sqlQuery .= " LIMIT " . $number[0];

			if (isset($number[1]) && strlen($number[1]))
			{
				$sqlQuery .= " OFFSET " . $number[1];

			}

			return $sqlQuery;
		}

		return $sqlQuery . " LIMIT " . $number;
	}

	public function forUpdate($sqlQuery)
	{
		return $sqlQuery . " FOR UPDATE";
	}

	public final function getColumnList($columnList, $escapeChar = null, $bindCounts = null)
	{

		$columns = [];

		foreach ($columnList as $column) {
			$columns = $this->getSqlColumn($column, $escapeChar, $bindCounts);
		}

		return join(", ", $columns);
	}

	public final function getSqlColumn($column, $escapeChar = null, $bindCounts = null)
	{

		if (typeof($column) <> "array")
		{
			return $this->prepareQualified($column, null, $escapeChar);
		}

		if (!(isset($column["type"])))
		{
			$columnField = $column[0];

			if (typeof($columnField) == "array")
			{
				$columnExpression = ["type" => "scalar", "value" => $columnField];

			}

			if (function() { if(isset($column[1])) {$columnDomain = $column[1]; return $columnDomain; } else { return false; } }() && $columnDomain <> "")
			{
				$columnExpression["domain"] = $columnDomain;

			}

			if (function() { if(isset($column[2])) {$columnAlias = $column[2]; return $columnAlias; } else { return false; } }() && $columnAlias)
			{
				$columnExpression["sqlAlias"] = $columnAlias;

			}

		}

		$column = $this->getSqlExpression($columnExpression, $escapeChar, $bindCounts);

		if (function() { if(isset($columnExpression["sqlAlias"])) {$columnAlias = $columnExpression["sqlAlias"]; return $columnAlias; } else { return false; } }() || function() { if(isset($columnExpression["alias"])) {$columnAlias = $columnExpression["alias"]; return $columnAlias; } else { return false; } }())
		{
			return $this->prepareColumnAlias($column, $columnAlias, $escapeChar);
		}

		return $this->prepareColumnAlias($column, null, $escapeChar);
	}

	public function getSqlExpression($expression, $escapeChar = null, $bindCounts = null)
	{


		if (!(function() { if(isset($expression["type"])) {$type = $expression["type"]; return $type; } else { return false; } }()))
		{
			throw new Exception("Invalid SQL expression");
		}

		switch ($type) {
			case "scalar":
				return $this->getSqlExpressionScalar($expression, $escapeChar, $bindCounts);			case "object":
				return $this->getSqlExpressionObject($expression, $escapeChar, $bindCounts);			case "qualified":
				return $this->getSqlExpressionQualified($expression, $escapeChar);			case "literal":
				return $expression["value"];			case "placeholder":
				if (function() { if(isset($expression["times"])) {$times = $expression["times"]; return $times; } else { return false; } }())
				{
					$placeholders = [];
					$rawValue = $expression["rawValue"];
					$value = $expression["value"];

					if (function() { if(isset($bindCounts[$rawValue])) {$postTimes = $bindCounts[$rawValue]; return $postTimes; } else { return false; } }())
					{
						$times = $postTimes;

					}

					foreach (range(1, $times) as $i) {
						$placeholders = $value . $i - 1;
					}

					return join(", ", $placeholders);
				}
				return $expression["value"];			case "binary-op":
				return $this->getSqlExpressionBinaryOperations($expression, $escapeChar, $bindCounts);			case "unary-op":
				return $this->getSqlExpressionUnaryOperations($expression, $escapeChar, $bindCounts);			case "parentheses":
				return "(" . $this->getSqlExpression($expression["left"], $escapeChar, $bindCounts) . ")";			case "functionCall":
				return $this->getSqlExpressionFunctionCall($expression, $escapeChar, $bindCounts);			case "list":
				return $this->getSqlExpressionList($expression, $escapeChar, $bindCounts);			case "all":
				return $this->getSqlExpressionAll($expression, $escapeChar);			case "select":
				return "(" . $this->select($expression["value"]) . ")";			case "cast":
				return $this->getSqlExpressionCastValue($expression, $escapeChar, $bindCounts);			case "convert":
				return $this->getSqlExpressionConvertValue($expression, $escapeChar, $bindCounts);			case "case":
				return $this->getSqlExpressionCase($expression, $escapeChar, $bindCounts);
		}

		throw new Exception("Invalid SQL expression type '" . $type . "'");
	}

	public final function getSqlTable($table, $escapeChar = null)
	{

		if (typeof($table) == "array")
		{
			$tableName = $table[0];

			$schemaName = $table[1]
			$aliasName = $table[2]
			return $this->prepareTable($tableName, $schemaName, $aliasName, $escapeChar);
		}

		return $this->escape($table, $escapeChar);
	}

	public function select($definition)
	{

		if (!(function() { if(isset($definition["tables"])) {$tables = $definition["tables"]; return $tables; } else { return false; } }()))
		{
			throw new Exception("The index 'tables' is required in the definition array");
		}

		if (!(function() { if(isset($definition["columns"])) {$columns = $definition["columns"]; return $columns; } else { return false; } }()))
		{
			throw new Exception("The index 'columns' is required in the definition array");
		}

		if (function() { if(isset($definition["distinct"])) {$distinct = $definition["distinct"]; return $distinct; } else { return false; } }())
		{
			if ($distinct)
			{
				$sql = "SELECT DISTINCT";

			}

		}

		$bindCounts = $definition["bindCounts"]
		$escapeChar = $this->_escapeChar;

		$sql .= " " . $this->getColumnList($columns, $escapeChar, $bindCounts);

		$sql .= " " . $this->getSqlExpressionFrom($tables, $escapeChar);

		if (function() { if(isset($definition["joins"])) {$joins = $definition["joins"]; return $joins; } else { return false; } }() && $joins)
		{
			$sql .= " " . $this->getSqlExpressionJoins($definition["joins"], $escapeChar, $bindCounts);

		}

		if (function() { if(isset($definition["where"])) {$where = $definition["where"]; return $where; } else { return false; } }() && $where)
		{
			$sql .= " " . $this->getSqlExpressionWhere($where, $escapeChar, $bindCounts);

		}

		if (function() { if(isset($definition["group"])) {$groupBy = $definition["group"]; return $groupBy; } else { return false; } }() && $groupBy)
		{
			$sql .= " " . $this->getSqlExpressionGroupBy($groupBy, $escapeChar);

		}

		if (function() { if(isset($definition["having"])) {$having = $definition["having"]; return $having; } else { return false; } }() && $having)
		{
			$sql .= " " . $this->getSqlExpressionHaving($having, $escapeChar, $bindCounts);

		}

		if (function() { if(isset($definition["order"])) {$orderBy = $definition["order"]; return $orderBy; } else { return false; } }() && $orderBy)
		{
			$sql .= " " . $this->getSqlExpressionOrderBy($orderBy, $escapeChar, $bindCounts);

		}

		if (function() { if(isset($definition["limit"])) {$limit = $definition["limit"]; return $limit; } else { return false; } }() && $limit)
		{
			$sql = $this->getSqlExpressionLimit(["sql" => $sql, "value" => $limit], $escapeChar, $bindCounts);

		}

		if (function() { if(isset($definition["forUpdate"])) {$forUpdate = $definition["forUpdate"]; return $forUpdate; } else { return false; } }() && $forUpdate)
		{
			$sql .= " FOR UPDATE";

		}

		return $sql;
	}

	public function supportsSavepoints()
	{
		return true;
	}

	public function supportsReleaseSavepoints()
	{
		return $this->supportsSavePoints();
	}

	public function createSavepoint($name)
	{
		return "SAVEPOINT " . $name;
	}

	public function releaseSavepoint($name)
	{
		return "RELEASE SAVEPOINT " . $name;
	}

	public function rollbackSavepoint($name)
	{
		return "ROLLBACK TO SAVEPOINT " . $name;
	}

	protected final function getSqlExpressionScalar($expression, $escapeChar = null, $bindCounts = null)
	{

		if (isset($expression["column"]))
		{
			return $this->getSqlColumn($expression["column"]);
		}

		if (!(function() { if(isset($expression["value"])) {$value = $expression["value"]; return $value; } else { return false; } }()))
		{
			throw new Exception("Invalid SQL expression");
		}

		if (typeof($value) == "array")
		{
			return $this->getSqlExpression($value, $escapeChar, $bindCounts);
		}

		return $value;
	}

	protected final function getSqlExpressionObject($expression, $escapeChar = null, $bindCounts = null)
	{

		$objectExpression = ["type" => "all"];

		if (function() { if(isset($expression["column"])) {$domain = $expression["column"]; return $domain; } else { return false; } }() || function() { if(isset($expression["domain"])) {$domain = $expression["domain"]; return $domain; } else { return false; } }() && $domain <> "")
		{
			$objectExpression["domain"] = $domain;

		}

		return $this->getSqlExpression($objectExpression, $escapeChar, $bindCounts);
	}

	protected final function getSqlExpressionQualified($expression, $escapeChar = null)
	{

		$column = $expression["name"];

		if (!(function() { if(isset($expression["domain"])) {$domain = $expression["domain"]; return $domain; } else { return false; } }()))
		{
			$domain = null;

		}

		return $this->prepareQualified($column, $domain, $escapeChar);
	}

	protected final function getSqlExpressionBinaryOperations($expression, $escapeChar = null, $bindCounts = null)
	{

		$left = $this->getSqlExpression($expression["left"], $escapeChar, $bindCounts);
		$right = $this->getSqlExpression($expression["right"], $escapeChar, $bindCounts);

		return $left . " " . $expression["op"] . " " . $right;
	}

	protected final function getSqlExpressionUnaryOperations($expression, $escapeChar = null, $bindCounts = null)
	{

		if (function() { if(isset($expression["left"])) {$left = $expression["left"]; return $left; } else { return false; } }())
		{
			return $this->getSqlExpression($left, $escapeChar, $bindCounts) . " " . $expression["op"];
		}

		if (function() { if(isset($expression["right"])) {$right = $expression["right"]; return $right; } else { return false; } }())
		{
			return $expression["op"] . " " . $this->getSqlExpression($right, $escapeChar, $bindCounts);
		}

		throw new Exception("Invalid SQL-unary expression");
	}

	protected final function getSqlExpressionFunctionCall($expression, $escapeChar = null, $bindCounts)
	{

		$name = $expression["name"];

		if (function() { if(isset($this->_customFunctions[$name])) {$customFunction = $this->_customFunctions[$name]; return $customFunction; } else { return false; } }())
		{
			return customFunction($this, $expression, $escapeChar);
		}

		if (function() { if(isset($expression["arguments"])) {$arguments = $expression["arguments"]; return $arguments; } else { return false; } }() && typeof($arguments) == "array")
		{
			$arguments = $this->getSqlExpression(["type" => "list", "parentheses" => false, "value" => $arguments], $escapeChar, $bindCounts);

			if (isset($expression["distinct"]) && $expression["distinct"])
			{
				return $name . "(DISTINCT " . $arguments . ")";
			}

			return $name . "(" . $arguments . ")";
		}

		return $name . "()";
	}

	protected final function getSqlExpressionList($expression, $escapeChar = null, $bindCounts = null)
	{

		$items = [];

		$separator = ", ";

		if (isset($expression["separator"]))
		{
			$separator = $expression["separator"];

		}

		if (function() { if(isset($expression[0])) {$values = $expression[0]; return $values; } else { return false; } }() || function() { if(isset($expression["value"])) {$values = $expression["value"]; return $values; } else { return false; } }() && typeof($values) == "array")
		{
			foreach ($values as $item) {
				$items = $this->getSqlExpression($item, $escapeChar, $bindCounts);
			}

			if (isset($expression["parentheses"]) && $expression["parentheses"] === false)
			{
				return join($separator, $items);
			}

			return "(" . join($separator, $items) . ")";
		}

		throw new Exception("Invalid SQL-list expression");
	}

	protected final function getSqlExpressionAll($expression, $escapeChar = null)
	{

		$domain = $expression["domain"]
		return $this->prepareQualified("*", $domain, $escapeChar);
	}

	protected final function getSqlExpressionCastValue($expression, $escapeChar = null, $bindCounts = null)
	{

		$left = $this->getSqlExpression($expression["left"], $escapeChar, $bindCounts);
		$right = $this->getSqlExpression($expression["right"], $escapeChar, $bindCounts);

		return "CAST(" . $left . " AS " . $right . ")";
	}

	protected final function getSqlExpressionConvertValue($expression, $escapeChar = null, $bindCounts = null)
	{

		$left = $this->getSqlExpression($expression["left"], $escapeChar, $bindCounts);
		$right = $this->getSqlExpression($expression["right"], $escapeChar, $bindCounts);

		return "CONVERT(" . $left . " USING " . $right . ")";
	}

	protected final function getSqlExpressionCase($expression, $escapeChar = null, $bindCounts = null)
	{

		$sql = "CASE " . $this->getSqlExpression($expression["expr"], $escapeChar, $bindCounts);

		foreach ($expression["when-clauses"] as $whenClause) {
			if ($whenClause["type"] == "when")
			{
				$sql .= " WHEN " . $this->getSqlExpression($whenClause["expr"], $escapeChar, $bindCounts) . " THEN " . $this->getSqlExpression($whenClause["then"], $escapeChar, $bindCounts);

			}
		}

		return $sql . " END";
	}

	protected final function getSqlExpressionFrom($expression, $escapeChar = null)
	{

		if (typeof($expression) == "array")
		{
			$tables = [];

			foreach ($expression as $table) {
				$tables = $this->getSqlTable($table, $escapeChar);
			}

			$tables = join(", ", $tables);

		}

		return "FROM " . $tables;
	}

	protected final function getSqlExpressionJoins($expression, $escapeChar = null, $bindCounts = null)
	{

		foreach ($expression as $join) {
			if (function() { if(isset($join["conditions"])) {$joinConditionsArray = $join["conditions"]; return $joinConditionsArray; } else { return false; } }() && !(empty($joinConditionsArray)))
			{
				if (!(isset($joinConditionsArray[0])))
				{
					$joinCondition = $this->getSqlExpression($joinConditionsArray, $escapeChar, $bindCounts);

				}

			}
			if (function() { if(isset($join["type"])) {$joinType = $join["type"]; return $joinType; } else { return false; } }() && $joinType)
			{
				$joinType .= " ";

			}
			$joinTable = $this->getSqlTable($join["source"], $escapeChar);
			$sql .= " " . $joinType . "JOIN " . $joinTable . " ON " . $joinCondition;
		}

		return $sql;
	}

	protected final function getSqlExpressionWhere($expression, $escapeChar = null, $bindCounts = null)
	{

		if (typeof($expression) == "array")
		{
			$whereSql = $this->getSqlExpression($expression, $escapeChar, $bindCounts);

		}

		return "WHERE " . $whereSql;
	}

	protected final function getSqlExpressionGroupBy($expression, $escapeChar = null, $bindCounts = null)
	{

		if (typeof($expression) == "array")
		{
			$fields = [];

			foreach ($expression as $field) {
				if (typeof($field) <> "array")
				{
					throw new Exception("Invalid SQL-GROUP-BY expression");
				}
				$fields = $this->getSqlExpression($field, $escapeChar, $bindCounts);
			}

			$fields = join(", ", $fields);

		}

		return "GROUP BY " . $fields;
	}

	protected final function getSqlExpressionHaving($expression, $escapeChar = null, $bindCounts = null)
	{
		return "HAVING " . $this->getSqlExpression($expression, $escapeChar, $bindCounts);
	}

	protected final function getSqlExpressionOrderBy($expression, $escapeChar = null, $bindCounts = null)
	{

		if (typeof($expression) == "array")
		{
			$fields = [];

			foreach ($expression as $field) {
				if (typeof($field) <> "array")
				{
					throw new Exception("Invalid SQL-ORDER-BY expression");
				}
				$fieldSql = $this->getSqlExpression($field[0], $escapeChar, $bindCounts);
				if (function() { if(isset($field[1])) {$type = $field[1]; return $type; } else { return false; } }() && $type <> "")
				{
					$fieldSql .= " " . $type;

				}
				$fields = $fieldSql;
			}

			$fields = join(", ", $fields);

		}

		return "ORDER BY " . $fields;
	}

	protected final function getSqlExpressionLimit($expression, $escapeChar = null, $bindCounts = null)
	{

		$value = $expression["value"];

		if (isset($expression["sql"]))
		{
			$sql = $expression["sql"];

		}

		if (typeof($value) == "array")
		{
			if (typeof($value["number"]) == "array")
			{
				$limit = $this->getSqlExpression($value["number"], $escapeChar, $bindCounts);

			}

			if (function() { if(isset($value["offset"])) {$offset = $value["offset"]; return $offset; } else { return false; } }() && typeof($offset) == "array")
			{
				$offset = $this->getSqlExpression($offset, $escapeChar, $bindCounts);

			}

		}

		return $this->limit($sql, [$limit, $offset]);
	}

	protected function prepareColumnAlias($qualified, $alias = null, $escapeChar = null)
	{
		if ($alias <> "")
		{
			return $qualified . " AS " . $this->escape($alias, $escapeChar);
		}

		return $qualified;
	}

	protected function prepareTable($table, $schema = null, $alias = null, $escapeChar = null)
	{
		$table = $this->escape($table, $escapeChar);

		if ($schema <> "")
		{
			$table = $this->escapeSchema($schema, $escapeChar) . "." . $table;

		}

		if ($alias <> "")
		{
			$table = $table . " AS " . $this->escape($alias, $escapeChar);

		}

		return $table;
	}

	protected function prepareQualified($column, $domain = null, $escapeChar = null)
	{
		if ($domain <> "")
		{
			return $this->escape($domain . "." . $column, $escapeChar);
		}

		return $this->escape($column, $escapeChar);
	}


}