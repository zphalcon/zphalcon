<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Db\Column;
use Phalcon\Db\RawValue;
use Phalcon\Db\ResultInterface;
use Phalcon\Db\AdapterInterface;
use Phalcon\DiInterface;
use Phalcon\Mvc\Model\Row;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\ManagerInterface;
use Phalcon\Mvc\Model\QueryInterface;
use Phalcon\Cache\BackendInterface;
use Phalcon\Mvc\Model\Query\Status;
use Phalcon\Mvc\Model\Resultset\Complex;
use Phalcon\Mvc\Model\Query\StatusInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\Resultset\Simple;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Model\RelationInterface;
use Phalcon\Mvc\Model\TransactionInterface;
use Phalcon\Db\DialectInterface;

class Query implements QueryInterface, InjectionAwareInterface
{
	const TYPE_SELECT = 309;
	const TYPE_INSERT = 306;
	const TYPE_UPDATE = 300;
	const TYPE_DELETE = 303;

	protected $_dependencyInjector;
	protected $_manager;
	protected $_metaData;
	protected $_type;
	protected $_phql;
	protected $_ast;
	protected $_intermediate;
	protected $_models;
	protected $_sqlAliases;
	protected $_sqlAliasesModels;
	protected $_sqlModelsAliases;
	protected $_sqlAliasesModelsInstances;
	protected $_sqlColumnAliases;
	protected $_modelsInstances;
	protected $_cache;
	protected $_cacheOptions;
	protected $_uniqueRow;
	protected $_bindParams;
	protected $_bindTypes;
	protected $_enableImplicitJoins;
	protected $_sharedLock;
	protected $_transaction;
	static protected $_irPhqlCache;

	public function __construct($phql = null, $dependencyInjector = null, $options = null)
	{

		if (typeof($phql) <> "null")
		{
			$this->_phql = $phql;

		}

		if (typeof($dependencyInjector) == "object")
		{
			$this->setDI($dependencyInjector);

		}

		if (typeof($options) == "array" && function() { if(isset($options["enable_implicit_joins"])) {$enableImplicitJoins = $options["enable_implicit_joins"]; return $enableImplicitJoins; } else { return false; } }())
		{
			$this->_enableImplicitJoins = $enableImplicitJoins == true;

		}

	}

	public function setDI($dependencyInjector)
	{

		$manager = $dependencyInjector->getShared("modelsManager");

		if (typeof($manager) <> "object")
		{
			throw new Exception("Injected service 'modelsManager' is invalid");
		}

		$metaData = $dependencyInjector->getShared("modelsMetadata");

		if (typeof($metaData) <> "object")
		{
			throw new Exception("Injected service 'modelsMetaData' is invalid");
		}

		$this->_manager = $manager;
		$this->_metaData = $metaData;

		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	public function setUniqueRow($uniqueRow)
	{
		$this->_uniqueRow = $uniqueRow;

		return $this;
	}

	public function getUniqueRow()
	{
		return $this->_uniqueRow;
	}

	protected final function _getQualified($expr)
	{


		$columnName = $expr["name"];

		$sqlColumnAliases = $this->_sqlColumnAliases;

		if (isset($sqlColumnAliases[$columnName]) && !(isset($expr["domain"])) || empty($expr["domain"]))
		{
			return ["type" => "qualified", "name" => $columnName];
		}

		$metaData = $this->_metaData;

		if (function() { if(isset($expr["domain"])) {$columnDomain = $expr["domain"]; return $columnDomain; } else { return false; } }())
		{
			$sqlAliases = $this->_sqlAliases;

			if (!(function() { if(isset($sqlAliases[$columnDomain])) {$source = $sqlAliases[$columnDomain]; return $source; } else { return false; } }()))
			{
				throw new Exception("Unknown model or alias '" . $columnDomain . "' (11), when preparing: " . $this->_phql);
			}

			if (globals_get("orm.column_renaming"))
			{
				$sqlAliasesModelsInstances = $this->_sqlAliasesModelsInstances;

				if (!(function() { if(isset($sqlAliasesModelsInstances[$columnDomain])) {$model = $sqlAliasesModelsInstances[$columnDomain]; return $model; } else { return false; } }()))
				{
					throw new Exception("There is no model related to model or alias '" . $columnDomain . "', when executing: " . $this->_phql);
				}

				$columnMap = $metaData->getReverseColumnMap($model);

			}

			if (typeof($columnMap) == "array")
			{
				if (!(function() { if(isset($columnMap[$columnName])) {$realColumnName = $columnMap[$columnName]; return $realColumnName; } else { return false; } }()))
				{
					throw new Exception("Column '" . $columnName . "' doesn't belong to the model or alias '" . $columnDomain . "', when executing: " . $this->_phql);
				}

			}

		}

		return ["type" => "qualified", "domain" => $source, "name" => $realColumnName, "balias" => $columnName];
	}

	protected final function _getCallArgument($argument)
	{
		if ($argument["type"] == PHQL_T_STARALL)
		{
			return ["type" => "all"];
		}

		return $this->_getExpression($argument);
	}

	protected final function _getCaseExpression($expr)
	{

		$whenClauses = [];

		foreach ($expr["right"] as $whenExpr) {
			if (isset($whenExpr["right"]))
			{
				$whenClauses = ["type" => "when", "expr" => $this->_getExpression($whenExpr["left"]), "then" => $this->_getExpression($whenExpr["right"])];

			}
		}

		return ["type" => "case", "expr" => $this->_getExpression($expr["left"]), "when-clauses" => $whenClauses];
	}

	protected final function _getFunctionCall($expr)
	{

		if (function() { if(isset($expr["arguments"])) {$arguments = $expr["arguments"]; return $arguments; } else { return false; } }())
		{
			if (isset($expr["distinct"]))
			{
				$distinct = 1;

			}

			if (isset($arguments[0]))
			{
				$functionArgs = [];

				foreach ($arguments as $argument) {
					$functionArgs = $this->_getCallArgument($argument);
				}

			}

			if ($distinct)
			{
				return ["type" => "functionCall", "name" => $expr["name"], "arguments" => $functionArgs, "distinct" => $distinct];
			}

		}

		return ["type" => "functionCall", "name" => $expr["name"]];
	}

	protected final function _getExpression($expr, $quoting = true)
	{

		if (function() { if(isset($expr["type"])) {$exprType = $expr["type"]; return $exprType; } else { return false; } }())
		{
			$tempNotQuoting = true;

			if ($exprType <> PHQL_T_CASE)
			{
				if (function() { if(isset($expr["left"])) {$exprLeft = $expr["left"]; return $exprLeft; } else { return false; } }())
				{
					$left = $this->_getExpression($exprLeft, $tempNotQuoting);

				}

				if (function() { if(isset($expr["right"])) {$exprRight = $expr["right"]; return $exprRight; } else { return false; } }())
				{
					$right = $this->_getExpression($exprRight, $tempNotQuoting);

				}

			}

			switch ($exprType) {
				case PHQL_T_LESS:
					$exprReturn = ["type" => "binary-op", "op" => "<", "left" => $left, "right" => $right];
					break;

				case PHQL_T_EQUALS:
					$exprReturn = ["type" => "binary-op", "op" => "=", "left" => $left, "right" => $right];
					break;

				case PHQL_T_GREATER:
					$exprReturn = ["type" => "binary-op", "op" => ">", "left" => $left, "right" => $right];
					break;

				case PHQL_T_NOTEQUALS:
					$exprReturn = ["type" => "binary-op", "op" => "<>", "left" => $left, "right" => $right];
					break;

				case PHQL_T_LESSEQUAL:
					$exprReturn = ["type" => "binary-op", "op" => "<=", "left" => $left, "right" => $right];
					break;

				case PHQL_T_GREATEREQUAL:
					$exprReturn = ["type" => "binary-op", "op" => ">=", "left" => $left, "right" => $right];
					break;

				case PHQL_T_AND:
					$exprReturn = ["type" => "binary-op", "op" => "AND", "left" => $left, "right" => $right];
					break;

				case PHQL_T_OR:
					$exprReturn = ["type" => "binary-op", "op" => "OR", "left" => $left, "right" => $right];
					break;

				case PHQL_T_QUALIFIED:
					$exprReturn = $this->_getQualified($expr);
					break;

				case PHQL_T_ADD:
					$exprReturn = ["type" => "binary-op", "op" => "+", "left" => $left, "right" => $right];
					break;

				case PHQL_T_SUB:
					$exprReturn = ["type" => "binary-op", "op" => "-", "left" => $left, "right" => $right];
					break;

				case PHQL_T_MUL:
					$exprReturn = ["type" => "binary-op", "op" => "*", "left" => $left, "right" => $right];
					break;

				case PHQL_T_DIV:
					$exprReturn = ["type" => "binary-op", "op" => "/", "left" => $left, "right" => $right];
					break;

				case PHQL_T_MOD:
					$exprReturn = ["type" => "binary-op", "op" => "%", "left" => $left, "right" => $right];
					break;

				case PHQL_T_BITWISE_AND:
					$exprReturn = ["type" => "binary-op", "op" => "&", "left" => $left, "right" => $right];
					break;

				case PHQL_T_BITWISE_OR:
					$exprReturn = ["type" => "binary-op", "op" => "|", "left" => $left, "right" => $right];
					break;

				case PHQL_T_ENCLOSED:

				case PHQL_T_SUBQUERY:
					$exprReturn = ["type" => "parentheses", "left" => $left];
					break;

				case PHQL_T_MINUS:
					$exprReturn = ["type" => "unary-op", "op" => "-", "right" => $right];
					break;

				case PHQL_T_INTEGER:

				case PHQL_T_DOUBLE:

				case PHQL_T_HINTEGER:
					$exprReturn = ["type" => "literal", "value" => $expr["value"]];
					break;

				case PHQL_T_TRUE:
					$exprReturn = ["type" => "literal", "value" => "TRUE"];
					break;

				case PHQL_T_FALSE:
					$exprReturn = ["type" => "literal", "value" => "FALSE"];
					break;

				case PHQL_T_STRING:
					$value = $expr["value"];
					if ($quoting === true)
					{
						if (memstr($value, "'"))
						{
							$escapedValue = phalcon_orm_singlequotes($value);

						}

						$exprValue = "'" . $escapedValue . "'";

					}
					$exprReturn = ["type" => "literal", "value" => $exprValue];
					break;

				case PHQL_T_NPLACEHOLDER:
					$exprReturn = ["type" => "placeholder", "value" => str_replace("?", ":", $expr["value"])];
					break;

				case PHQL_T_SPLACEHOLDER:
					$exprReturn = ["type" => "placeholder", "value" => ":" . $expr["value"]];
					break;

				case PHQL_T_BPLACEHOLDER:
					$value = $expr["value"];
					if (memstr($value, ":"))
					{
						$valueParts = explode(":", $value);
						$name = $valueParts[0];
						$bindType = $valueParts[1];

						switch ($bindType) {
							case "str":
								$this[$name] = Column::BIND_PARAM_STR;
								$exprReturn = ["type" => "placeholder", "value" => ":" . $name];
								break;

							case "int":
								$this[$name] = Column::BIND_PARAM_INT;
								$exprReturn = ["type" => "placeholder", "value" => ":" . $name];
								break;

							case "double":
								$this[$name] = Column::BIND_PARAM_DECIMAL;
								$exprReturn = ["type" => "placeholder", "value" => ":" . $name];
								break;

							case "bool":
								$this[$name] = Column::BIND_PARAM_BOOL;
								$exprReturn = ["type" => "placeholder", "value" => ":" . $name];
								break;

							case "blob":
								$this[$name] = Column::BIND_PARAM_BLOB;
								$exprReturn = ["type" => "placeholder", "value" => ":" . $name];
								break;

							case "null":
								$this[$name] = Column::BIND_PARAM_NULL;
								$exprReturn = ["type" => "placeholder", "value" => ":" . $name];
								break;

							case "array":

							case "array-str":

							case "array-int":
								if (!(function() { if(isset($this->_bindParams[$name])) {$bind = $this->_bindParams[$name]; return $bind; } else { return false; } }()))
								{
									throw new Exception("Bind value is required for array type placeholder: " . $name);
								}
								if (typeof($bind) <> "array")
								{
									throw new Exception("Bind type requires an array in placeholder: " . $name);
								}
								if (count($bind) < 1)
								{
									throw new Exception("At least one value must be bound in placeholder: " . $name);
								}
								$exprReturn = ["type" => "placeholder", "value" => ":" . $name, "rawValue" => $name, "times" => count($bind)];
								break;

							default:
								throw new Exception("Unknown bind type: " . $bindType);

						}

					}
					break;

				case PHQL_T_NULL:
					$exprReturn = ["type" => "literal", "value" => "NULL"];
					break;

				case PHQL_T_LIKE:
					$exprReturn = ["type" => "binary-op", "op" => "LIKE", "left" => $left, "right" => $right];
					break;

				case PHQL_T_NLIKE:
					$exprReturn = ["type" => "binary-op", "op" => "NOT LIKE", "left" => $left, "right" => $right];
					break;

				case PHQL_T_ILIKE:
					$exprReturn = ["type" => "binary-op", "op" => "ILIKE", "left" => $left, "right" => $right];
					break;

				case PHQL_T_NILIKE:
					$exprReturn = ["type" => "binary-op", "op" => "NOT ILIKE", "left" => $left, "right" => $right];
					break;

				case PHQL_T_NOT:
					$exprReturn = ["type" => "unary-op", "op" => "NOT ", "right" => $right];
					break;

				case PHQL_T_ISNULL:
					$exprReturn = ["type" => "unary-op", "op" => " IS NULL", "left" => $left];
					break;

				case PHQL_T_ISNOTNULL:
					$exprReturn = ["type" => "unary-op", "op" => " IS NOT NULL", "left" => $left];
					break;

				case PHQL_T_IN:
					$exprReturn = ["type" => "binary-op", "op" => "IN", "left" => $left, "right" => $right];
					break;

				case PHQL_T_NOTIN:
					$exprReturn = ["type" => "binary-op", "op" => "NOT IN", "left" => $left, "right" => $right];
					break;

				case PHQL_T_EXISTS:
					$exprReturn = ["type" => "unary-op", "op" => "EXISTS", "right" => $right];
					break;

				case PHQL_T_DISTINCT:
					$exprReturn = ["type" => "unary-op", "op" => "DISTINCT ", "right" => $right];
					break;

				case PHQL_T_BETWEEN:
					$exprReturn = ["type" => "binary-op", "op" => "BETWEEN", "left" => $left, "right" => $right];
					break;

				case PHQL_T_AGAINST:
					$exprReturn = ["type" => "binary-op", "op" => "AGAINST", "left" => $left, "right" => $right];
					break;

				case PHQL_T_CAST:
					$exprReturn = ["type" => "cast", "left" => $left, "right" => $right];
					break;

				case PHQL_T_CONVERT:
					$exprReturn = ["type" => "convert", "left" => $left, "right" => $right];
					break;

				case PHQL_T_RAW_QUALIFIED:
					$exprReturn = ["type" => "literal", "value" => $expr["name"]];
					break;

				case PHQL_T_FCALL:
					$exprReturn = $this->_getFunctionCall($expr);
					break;

				case PHQL_T_CASE:
					$exprReturn = $this->_getCaseExpression($expr);
					break;

				case PHQL_T_SELECT:
					$exprReturn = ["type" => "select", "value" => $this->_prepareSelect($expr, true)];
					break;

				default:
					throw new Exception("Unknown expression type " . $exprType);

			}

			return $exprReturn;
		}

		if (isset($expr["domain"]))
		{
			return $this->_getQualified($expr);
		}

		if (isset($expr[0]))
		{
			$listItems = [];

			foreach ($expr as $exprListItem) {
				$listItems = $this->_getExpression($exprListItem);
			}

			return ["type" => "list", $listItems];
		}

		throw new Exception("Unknown expression");
	}

	protected final function _getSelectColumn($column)
	{

		if (!(function() { if(isset($column["type"])) {$columnType = $column["type"]; return $columnType; } else { return false; } }()))
		{
			throw new Exception("Corrupted SELECT AST");
		}

		$sqlColumns = [];

		$eager = $column["eager"]
		if ($columnType == PHQL_T_STARALL)
		{
			foreach ($this->_models as $modelName => $source) {
				$sqlColumn = ["type" => "object", "model" => $modelName, "column" => $source, "balias" => lcfirst($modelName)];
				if ($eager !== null)
				{
					$sqlColumn["eager"] = $eager;
					$sqlColumn["eagerType"] = $column["eagerType"];

				}
				$sqlColumns = $sqlColumn;
			}

			return $sqlColumns;
		}

		if (!(isset($column["column"])))
		{
			throw new Exception("Corrupted SELECT AST");
		}

		if ($columnType == PHQL_T_DOMAINALL)
		{
			$sqlAliases = $this->_sqlAliases;

			$columnDomain = $column["column"];

			if (!(function() { if(isset($sqlAliases[$columnDomain])) {$source = $sqlAliases[$columnDomain]; return $source; } else { return false; } }()))
			{
				throw new Exception("Unknown model or alias '" . $columnDomain . "' (2), when preparing: " . $this->_phql);
			}

			$sqlColumnAlias = $source;

			$preparedAlias = $column["balias"]
			$sqlAliasesModels = $this->_sqlAliasesModels;
			$modelName = $sqlAliasesModels[$columnDomain];

			if (typeof($preparedAlias) <> "string")
			{
				if ($columnDomain == $modelName)
				{
					$preparedAlias = lcfirst($modelName);

				}

			}

			$sqlColumn = ["type" => "object", "model" => $modelName, "column" => $sqlColumnAlias, "balias" => $preparedAlias];

			if ($eager !== null)
			{
				$sqlColumn["eager"] = $eager;
				$sqlColumn["eagerType"] = $column["eagerType"];

			}

			$sqlColumns = $sqlColumn;

			return $sqlColumns;
		}

		if ($columnType == PHQL_T_EXPR)
		{
			$sqlColumn = ["type" => "scalar"];
			$columnData = $column["column"];
			$sqlExprColumn = $this->_getExpression($columnData);

			if (function() { if(isset($sqlExprColumn["balias"])) {$balias = $sqlExprColumn["balias"]; return $balias; } else { return false; } }())
			{
				$sqlColumn["balias"] = $balias;
				$sqlColumn["sqlAlias"] = $balias;

			}

			if ($eager !== null)
			{
				$sqlColumn["eager"] = $eager;
				$sqlColumn["eagerType"] = $column["eagerType"];

			}

			$sqlColumn["column"] = $sqlExprColumn;
			$sqlColumns = $sqlColumn;

			return $sqlColumns;
		}

		throw new Exception("Unknown type of column " . $columnType);
	}

	protected final function _getTable($manager, $qualifiedName)
	{

		if (!(function() { if(isset($qualifiedName["name"])) {$modelName = $qualifiedName["name"]; return $modelName; } else { return false; } }()))
		{
			throw new Exception("Corrupted SELECT AST");
		}

		$model = $manager->load($modelName);
		$source = $model->getSource();
		$schema = $model->getSchema();

		if ($schema)
		{
			return [$schema, $source];
		}

		return $source;
	}

	protected final function _getJoin($manager, $join)
	{

		if (function() { if(isset($join["qualified"])) {$qualified = $join["qualified"]; return $qualified; } else { return false; } }())
		{
			if ($qualified["type"] == PHQL_T_QUALIFIED)
			{
				$modelName = $qualified["name"];

				if (memstr($modelName, ":"))
				{
					$nsAlias = explode(":", $modelName);

					$realModelName = $manager->getNamespaceAlias($nsAlias[0]) . "\\" . $nsAlias[1];

				}

				$model = $manager->load($realModelName, true);
				$source = $model->getSource();
				$schema = $model->getSchema();

				return ["schema" => $schema, "source" => $source, "modelName" => $realModelName, "model" => $model];
			}

		}

		throw new Exception("Corrupted SELECT AST");
	}

	protected final function _getJoinType($join)
	{

		if (!(function() { if(isset($join["type"])) {$type = $join["type"]; return $type; } else { return false; } }()))
		{
			throw new Exception("Corrupted SELECT AST");
		}

		switch ($type) {
			case PHQL_T_INNERJOIN:
				return "INNER";
			case PHQL_T_LEFTJOIN:
				return "LEFT";
			case PHQL_T_RIGHTJOIN:
				return "RIGHT";
			case PHQL_T_CROSSJOIN:
				return "CROSS";
			case PHQL_T_FULLJOIN:
				return "FULL OUTER";

		}

		throw new Exception("Unknown join type " . $type . ", when preparing: " . $this->_phql);
	}

	protected final function _getSingleJoin($joinType, $joinSource, $modelAlias, $joinAlias, $relation)
	{

		$fields = $relation->getFields();

		$referencedFields = $relation->getReferencedFields();

		if (typeof($fields) <> "array")
		{
			$sqlJoinConditions = [["type" => "binary-op", "op" => "=", "left" => $this->_getQualified(["type" => PHQL_T_QUALIFIED, "domain" => $modelAlias, "name" => $fields]), "right" => $this->_getQualified(["type" => "qualified", "domain" => $joinAlias, "name" => $referencedFields])]];

		}

		return ["type" => $joinType, "source" => $joinSource, "conditions" => $sqlJoinConditions];
	}

	protected final function _getMultiJoin($joinType, $joinSource, $modelAlias, $joinAlias, $relation)
	{

		$sqlJoins = [];

		$fields = $relation->getFields();

		$referencedFields = $relation->getReferencedFields();

		$intermediateModelName = $relation->getIntermediateModel();

		$manager = $this->_manager;

		$intermediateModel = $manager->load($intermediateModelName);

		$intermediateSource = $intermediateModel->getSource();

		$intermediateSchema = $intermediateModel->getSchema();

		$this[$intermediateModelName] = $intermediateSource;

		$this[$intermediateModelName] = $intermediateModel;

		$intermediateFields = $relation->getIntermediateFields();

		$intermediateReferencedFields = $relation->getIntermediateReferencedFields();

		$referencedModelName = $relation->getReferencedModel();

		if (typeof($fields) == "array")
		{
			foreach ($fields as $field => $position) {
				if (!(isset($referencedFields[$position])))
				{
					throw new Exception("The number of fields must be equal to the number of referenced fields in join " . $modelAlias . "-" . $joinAlias . ", when preparing: " . $this->_phql);
				}
				$intermediateField = $intermediateFields[$position];
				$sqlEqualsJoinCondition = ["type" => "binary-op", "op" => "=", "left" => $this->_getQualified(["type" => PHQL_T_QUALIFIED, "domain" => $modelAlias, "name" => $field]), "right" => $this->_getQualified(["type" => "qualified", "domain" => $joinAlias, "name" => $referencedFields])];
			}

		}

		return $sqlJoins;
	}

	protected final function _getJoins($select)
	{

		$models = $this->_models;
		$sqlAliases = $this->_sqlAliases;
		$sqlAliasesModels = $this->_sqlAliasesModels;
		$sqlModelsAliases = $this->_sqlModelsAliases;
		$sqlAliasesModelsInstances = $this->_sqlAliasesModelsInstances;
		$modelsInstances = $this->_modelsInstances;
		$fromModels = $models;

		$sqlJoins = [];
		$joinModels = [];
		$joinSources = [];
		$joinTypes = [];
		$joinPreCondition = [];
		$joinPrepared = [];

		$manager = $this->_manager;

		$tables = $select["tables"];

		if (!(isset($tables[0])))
		{
			$selectTables = [$tables];

		}

		$joins = $select["joins"];

		if (!(isset($joins[0])))
		{
			$selectJoins = [$joins];

		}

		foreach ($selectJoins as $joinItem) {
			$joinData = $this->_getJoin($manager, $joinItem);
			$source = $joinData["source"];
			$schema = $joinData["schema"];
			$model = $joinData["model"];
			$realModelName = $joinData["modelName"];
			$completeSource = [$source, $schema];
			$joinType = $this->_getJoinType($joinItem);
			if (function() { if(isset($joinItem["alias"])) {$aliasExpr = $joinItem["alias"]; return $aliasExpr; } else { return false; } }())
			{
				$alias = $aliasExpr["name"];

				if (isset($joinModels[$alias]))
				{
					throw new Exception("Cannot use '" . $alias . "' as join alias because it was already used, when preparing: " . $this->_phql);
				}

				$completeSource = $alias;

				$joinTypes[$alias] = $joinType;

				$sqlAliases[$alias] = $alias;

				$joinModels[$alias] = $realModelName;

				$sqlModelsAliases[$realModelName] = $alias;

				$sqlAliasesModels[$alias] = $realModelName;

				$sqlAliasesModelsInstances[$alias] = $model;

				$models[$realModelName] = $alias;

				$joinSources[$alias] = $completeSource;

				$joinPrepared[$alias] = $joinItem;

			}
			$modelsInstances[$realModelName] = $model;
		}

		$this->_models = $models;
		$this->_sqlAliases = $sqlAliases;
		$this->_sqlAliasesModels = $sqlAliasesModels;
		$this->_sqlModelsAliases = $sqlModelsAliases;
		$this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;
		$this->_modelsInstances = $modelsInstances;

		foreach ($joinPrepared as $joinAliasName => $joinItem) {
			if (function() { if(isset($joinItem["conditions"])) {$joinExpr = $joinItem["conditions"]; return $joinExpr; } else { return false; } }())
			{
				$joinPreCondition[$joinAliasName] = $this->_getExpression($joinExpr);

			}
		}

		if (!($this->_enableImplicitJoins))
		{
			foreach ($joinPrepared as $joinAliasName => $_) {
				$joinType = $joinTypes[$joinAliasName];
				$joinSource = $joinSources[$joinAliasName];
				$preCondition = $joinPreCondition[$joinAliasName];
				$sqlJoins = ["type" => $joinType, "source" => $joinSource, "conditions" => [$preCondition]];
			}

			return $sqlJoins;
		}

		$fromModels = [];

		foreach ($selectTables as $tableItem) {
			$fromModels[$tableItem["qualifiedName"]["name"]] = true;
		}

		foreach ($fromModels as $fromModelName => $_) {
			foreach ($joinModels as $joinAlias => $joinModel) {
				$joinSource = $joinSources[$joinAlias];
				$joinType = $joinTypes[$joinAlias];
				if (!(function() { if(isset($joinPreCondition[$joinAlias])) {$preCondition = $joinPreCondition[$joinAlias]; return $preCondition; } else { return false; } }()))
				{
					$modelNameAlias = $sqlAliasesModels[$joinAlias];

					$relation = $manager->getRelationByAlias($fromModelName, $modelNameAlias);

					if ($relation === false)
					{
						$relations = $manager->getRelationsBetween($fromModelName, $modelNameAlias);

						if (typeof($relations) == "array")
						{
							if (count($relations) <> 1)
							{
								throw new Exception("There is more than one relation between models '" . $fromModelName . "' and '" . $joinModel . "', the join must be done using an alias, when preparing: " . $this->_phql);
							}

							$relation = $relations[0];

						}

					}

					if (typeof($relation) == "object")
					{
						$modelAlias = $sqlModelsAliases[$fromModelName];

						if (!($relation->isThrough()))
						{
							$sqlJoin = $this->_getSingleJoin($joinType, $joinSource, $modelAlias, $joinAlias, $relation);

						}

						if (isset($sqlJoin[0]))
						{
							foreach ($sqlJoin as $sqlJoinItem) {
								$sqlJoins = $sqlJoinItem;
							}

						}

					}

				}
			}
		}

		return $sqlJoins;
	}

	protected final function _getOrderClause($order)
	{

		if (!(isset($order[0])))
		{
			$orderColumns = [$order];

		}

		$orderParts = [];

		foreach ($orderColumns as $orderItem) {
			$orderPartExpr = $this->_getExpression($orderItem["column"]);
			if (function() { if(isset($orderItem["sort"])) {$orderSort = $orderItem["sort"]; return $orderSort; } else { return false; } }())
			{
				if ($orderSort == PHQL_T_ASC)
				{
					$orderPartSort = [$orderPartExpr, "ASC"];

				}

			}
			$orderParts = $orderPartSort;
		}

		return $orderParts;
	}

	protected final function _getGroupClause($group)
	{

		if (isset($group[0]))
		{
			$groupParts = [];

			foreach ($group as $groupItem) {
				$groupParts = $this->_getExpression($groupItem);
			}

		}

		return $groupParts;
	}

	protected final function _getLimitClause($limitClause)
	{


		if (function() { if(isset($limitClause["number"])) {$number = $limitClause["number"]; return $number; } else { return false; } }())
		{
			$limit["number"] = $this->_getExpression($number);

		}

		if (function() { if(isset($limitClause["offset"])) {$offset = $limitClause["offset"]; return $offset; } else { return false; } }())
		{
			$limit["offset"] = $this->_getExpression($offset);

		}

		return $limit;
	}

	protected final function _prepareSelect($ast = null, $merge = null)
	{


		if (empty($ast))
		{
			$ast = $this->_ast;

		}

		if (typeof($merge) == "null")
		{
			$merge = false;

		}

		if (!(function() { if(isset($ast["select"])) {$select = $ast["select"]; return $select; } else { return false; } }()))
		{
			$select = $ast;

		}

		if (!(function() { if(isset($select["tables"])) {$tables = $select["tables"]; return $tables; } else { return false; } }()))
		{
			throw new Exception("Corrupted SELECT AST");
		}

		if (!(function() { if(isset($select["columns"])) {$columns = $select["columns"]; return $columns; } else { return false; } }()))
		{
			throw new Exception("Corrupted SELECT AST");
		}

		$sqlModels = [];

		$sqlTables = [];

		$sqlColumns = [];

		$sqlAliases = [];

		$sqlAliasesModels = [];

		$sqlModelsAliases = [];

		$sqlAliasesModelsInstances = [];

		$models = [];
		$modelsInstances = [];

		if (!(isset($tables[0])))
		{
			$selectedModels = [$tables];

		}

		if (!(isset($columns[0])))
		{
			$selectColumns = [$columns];

		}

		$manager = $this->_manager;
		$metaData = $this->_metaData;

		if (typeof($manager) <> "object")
		{
			throw new Exception("A models-manager is required to execute the query");
		}

		if (typeof($metaData) <> "object")
		{
			throw new Exception("A meta-data is required to execute the query");
		}

		$number = 0;
		$automaticJoins = [];

		foreach ($selectedModels as $selectedModel) {
			$qualifiedName = $selectedModel["qualifiedName"];
			$modelName = $qualifiedName["name"];
			if (memstr($modelName, ":"))
			{
				$nsAlias = explode(":", $modelName);

				$realModelName = $manager->getNamespaceAlias($nsAlias[0]) . "\\" . $nsAlias[1];

			}
			$model = $manager->load($realModelName, true);
			$schema = $model->getSchema();
			$source = $model->getSource();
			if ($schema)
			{
				$completeSource = [$source, $schema];

			}
			if (function() { if(isset($selectedModel["alias"])) {$alias = $selectedModel["alias"]; return $alias; } else { return false; } }())
			{
				if (isset($sqlAliases[$alias]))
				{
					throw new Exception("Alias '" . $alias . "' is used more than once, when preparing: " . $this->_phql);
				}

				$sqlAliases[$alias] = $alias;
				$sqlAliasesModels[$alias] = $realModelName;
				$sqlModelsAliases[$realModelName] = $alias;
				$sqlAliasesModelsInstances[$alias] = $model;

				if (typeof($completeSource) == "array")
				{
					$completeSource = $alias;

				}

				$models[$realModelName] = $alias;

			}
			if (function() { if(isset($selectedModel["with"])) {$with = $selectedModel["with"]; return $with; } else { return false; } }())
			{
				if (!(isset($with[0])))
				{
					$withs = [$with];

				}

				foreach ($withs as $withItem) {
					$joinAlias = "AA" . $number;
					$relationModel = $withItem["name"];
					$relation = $manager->getRelationByAlias($realModelName, $relationModel);
					if (typeof($relation) == "object")
					{
						$bestAlias = $relation->getOption("alias");
						$relationModel = $relation->getReferencedModel();
						$eagerType = $relation->getType();

					}
					$selectColumns = ["type" => PHQL_T_DOMAINALL, "column" => $joinAlias, "eager" => $alias, "eagerType" => $eagerType, "balias" => $bestAlias];
					$automaticJoins = ["type" => PHQL_T_INNERJOIN, "qualified" => ["type" => PHQL_T_QUALIFIED, "name" => $relationModel], "alias" => ["type" => PHQL_T_QUALIFIED, "name" => $joinAlias]];
					$number++;
				}

			}
			$sqlModels = $realModelName;
			$sqlTables = $completeSource;
			$modelsInstances[$realModelName] = $model;
		}

		if (!($merge))
		{
			$this->_models = $models;
			$this->_modelsInstances = $modelsInstances;
			$this->_sqlAliases = $sqlAliases;
			$this->_sqlAliasesModels = $sqlAliasesModels;
			$this->_sqlModelsAliases = $sqlModelsAliases;
			$this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;

		}

		$joins = $select["joins"]
		if (count($joins))
		{
			if (count($automaticJoins))
			{
				if (isset($joins[0]))
				{
					$select["joins"] = array_merge($joins, $automaticJoins);

				}

			}

			$sqlJoins = $this->_getJoins($select);

		}

		$position = 0;
		$sqlColumnAliases = [];

		foreach ($selectColumns as $column) {
			foreach ($this->_getSelectColumn($column) as $sqlColumn) {
				if (function() { if(isset($column["alias"])) {$alias = $column["alias"]; return $alias; } else { return false; } }())
				{
					$sqlColumn["balias"] = $alias;
					$sqlColumn["sqlAlias"] = $alias;
					$sqlColumns[$alias] = $sqlColumn;
					$sqlColumnAliases[$alias] = true;

				}
				$position++;
			}
		}

		$this->_sqlColumnAliases = $sqlColumnAliases;

		$sqlSelect = ["models" => $sqlModels, "tables" => $sqlTables, "columns" => $sqlColumns];

		if (function() { if(isset($select["distinct"])) {$distinct = $select["distinct"]; return $distinct; } else { return false; } }())
		{
			$sqlSelect["distinct"] = $distinct;

		}

		if (count($sqlJoins))
		{
			$sqlSelect["joins"] = $sqlJoins;

		}

		if (function() { if(isset($ast["where"])) {$where = $ast["where"]; return $where; } else { return false; } }())
		{
			$sqlSelect["where"] = $this->_getExpression($where);

		}

		if (function() { if(isset($ast["groupBy"])) {$groupBy = $ast["groupBy"]; return $groupBy; } else { return false; } }())
		{
			$sqlSelect["group"] = $this->_getGroupClause($groupBy);

		}

		if (function() { if(isset($ast["having"])) {$having = $ast["having"]; return $having; } else { return false; } }())
		{
			$sqlSelect["having"] = $this->_getExpression($having);

		}

		if (function() { if(isset($ast["orderBy"])) {$order = $ast["orderBy"]; return $order; } else { return false; } }())
		{
			$sqlSelect["order"] = $this->_getOrderClause($order);

		}

		if (function() { if(isset($ast["limit"])) {$limit = $ast["limit"]; return $limit; } else { return false; } }())
		{
			$sqlSelect["limit"] = $this->_getLimitClause($limit);

		}

		if (isset($ast["forUpdate"]))
		{
			$sqlSelect["forUpdate"] = true;

		}

		if ($merge)
		{
			$this->_models = $tempModels;
			$this->_modelsInstances = $tempModelsInstances;
			$this->_sqlAliases = $tempSqlAliases;
			$this->_sqlAliasesModels = $tempSqlAliasesModels;
			$this->_sqlModelsAliases = $tempSqlModelsAliases;
			$this->_sqlAliasesModelsInstances = $tempSqlAliasesModelsInstances;

		}

		return $sqlSelect;
	}

	protected final function _prepareInsert()
	{


		$ast = $this->_ast;

		if (!(isset($ast["qualifiedName"])))
		{
			throw new Exception("Corrupted INSERT AST");
		}

		if (!(isset($ast["values"])))
		{
			throw new Exception("Corrupted INSERT AST");
		}

		$qualifiedName = $ast["qualifiedName"];

		if (!(isset($qualifiedName["name"])))
		{
			throw new Exception("Corrupted INSERT AST");
		}

		$manager = $this->_manager;
		$modelName = $qualifiedName["name"];

		if (memstr($modelName, ":"))
		{
			$nsAlias = explode(":", $modelName);

			$realModelName = $manager->getNamespaceAlias($nsAlias[0]) . "\\" . $nsAlias[1];

		}

		$model = $manager->load($realModelName, true);
		$source = $model->getSource();
		$schema = $model->getSchema();

		if ($schema)
		{
			$source = [$schema, $source];

		}

		$notQuoting = false;
		$exprValues = [];

		foreach ($ast["values"] as $exprValue) {
			$exprValues = ["type" => $exprValue["type"], "value" => $this->_getExpression($exprValue, $notQuoting)];
		}

		$sqlInsert = ["model" => $modelName, "table" => $source];

		$metaData = $this->_metaData;

		if (function() { if(isset($ast["fields"])) {$fields = $ast["fields"]; return $fields; } else { return false; } }())
		{
			$sqlFields = [];

			foreach ($fields as $field) {
				$name = $field["name"];
				if (!($metaData->hasAttribute($model, $name)))
				{
					throw new Exception("The model '" . $modelName . "' doesn't have the attribute '" . $name . "', when preparing: " . $this->_phql);
				}
				$sqlFields = $name;
			}

			$sqlInsert["fields"] = $sqlFields;

		}

		$sqlInsert["values"] = $exprValues;

		return $sqlInsert;
	}

	protected final function _prepareUpdate()
	{


		$ast = $this->_ast;

		if (!(function() { if(isset($ast["update"])) {$update = $ast["update"]; return $update; } else { return false; } }()))
		{
			throw new Exception("Corrupted UPDATE AST");
		}

		if (!(function() { if(isset($update["tables"])) {$tables = $update["tables"]; return $tables; } else { return false; } }()))
		{
			throw new Exception("Corrupted UPDATE AST");
		}

		if (!(function() { if(isset($update["values"])) {$values = $update["values"]; return $values; } else { return false; } }()))
		{
			throw new Exception("Corrupted UPDATE AST");
		}

		$models = [];
		$modelsInstances = [];

		$sqlTables = [];
		$sqlModels = [];
		$sqlAliases = [];
		$sqlAliasesModelsInstances = [];

		if (!(isset($tables[0])))
		{
			$updateTables = [$tables];

		}

		$manager = $this->_manager;

		foreach ($updateTables as $table) {
			$qualifiedName = $table["qualifiedName"];
			$modelName = $qualifiedName["name"];
			if (memstr($modelName, ":"))
			{
				$nsAlias = explode(":", $modelName);

				$realModelName = $manager->getNamespaceAlias($nsAlias[0]) . "\\" . $nsAlias[1];

			}
			$model = $manager->load($realModelName, true);
			$source = $model->getSource();
			$schema = $model->getSchema();
			if ($schema)
			{
				$completeSource = [$source, $schema];

			}
			if (function() { if(isset($table["alias"])) {$alias = $table["alias"]; return $alias; } else { return false; } }())
			{
				$sqlAliases[$alias] = $alias;
				$completeSource = $alias;
				$sqlTables = $completeSource;
				$sqlAliasesModelsInstances[$alias] = $model;
				$models[$alias] = $realModelName;

			}
			$sqlModels = $realModelName;
			$modelsInstances[$realModelName] = $model;
		}

		$this->_models = $models;
		$this->_modelsInstances = $modelsInstances;
		$this->_sqlAliases = $sqlAliases;
		$this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;

		$sqlFields = [];
		$sqlValues = [];

		if (!(isset($values[0])))
		{
			$updateValues = [$values];

		}

		$notQuoting = false;

		foreach ($updateValues as $updateValue) {
			$sqlFields = $this->_getExpression($updateValue["column"], $notQuoting);
			$exprColumn = $updateValue["expr"];
			$sqlValues = ["type" => $exprColumn["type"], "value" => $this->_getExpression($exprColumn, $notQuoting)];
		}

		$sqlUpdate = ["tables" => $sqlTables, "models" => $sqlModels, "fields" => $sqlFields, "values" => $sqlValues];

		if (function() { if(isset($ast["where"])) {$where = $ast["where"]; return $where; } else { return false; } }())
		{
			$sqlUpdate["where"] = $this->_getExpression($where, true);

		}

		if (function() { if(isset($ast["limit"])) {$limit = $ast["limit"]; return $limit; } else { return false; } }())
		{
			$sqlUpdate["limit"] = $this->_getLimitClause($limit);

		}

		return $sqlUpdate;
	}

	protected final function _prepareDelete()
	{

		$ast = $this->_ast;

		if (!(function() { if(isset($ast["delete"])) {$delete = $ast["delete"]; return $delete; } else { return false; } }()))
		{
			throw new Exception("Corrupted DELETE AST");
		}

		if (!(function() { if(isset($delete["tables"])) {$tables = $delete["tables"]; return $tables; } else { return false; } }()))
		{
			throw new Exception("Corrupted DELETE AST");
		}

		$models = [];
		$modelsInstances = [];

		$sqlTables = [];
		$sqlModels = [];
		$sqlAliases = [];
		$sqlAliasesModelsInstances = [];

		if (!(isset($tables[0])))
		{
			$deleteTables = [$tables];

		}

		$manager = $this->_manager;

		foreach ($deleteTables as $table) {
			$qualifiedName = $table["qualifiedName"];
			$modelName = $qualifiedName["name"];
			if (memstr($modelName, ":"))
			{
				$nsAlias = explode(":", $modelName);

				$realModelName = $manager->getNamespaceAlias($nsAlias[0]) . "\\" . $nsAlias[1];

			}
			$model = $manager->load($realModelName, true);
			$source = $model->getSource();
			$schema = $model->getSchema();
			if ($schema)
			{
				$completeSource = [$source, $schema];

			}
			if (function() { if(isset($table["alias"])) {$alias = $table["alias"]; return $alias; } else { return false; } }())
			{
				$sqlAliases[$alias] = $alias;
				$completeSource = $alias;
				$sqlTables = $completeSource;
				$sqlAliasesModelsInstances[$alias] = $model;
				$models[$alias] = $realModelName;

			}
			$sqlModels = $realModelName;
			$modelsInstances[$realModelName] = $model;
		}

		$this->_models = $models;
		$this->_modelsInstances = $modelsInstances;
		$this->_sqlAliases = $sqlAliases;
		$this->_sqlAliasesModelsInstances = $sqlAliasesModelsInstances;

		$sqlDelete = [];
		$sqlDelete["tables"] = $sqlTables;
		$sqlDelete["models"] = $sqlModels;

		if (function() { if(isset($ast["where"])) {$where = $ast["where"]; return $where; } else { return false; } }())
		{
			$sqlDelete["where"] = $this->_getExpression($where, true);

		}

		if (function() { if(isset($ast["limit"])) {$limit = $ast["limit"]; return $limit; } else { return false; } }())
		{
			$sqlDelete["limit"] = $this->_getLimitClause($limit);

		}

		return $sqlDelete;
	}

	public function parse()
	{

		$intermediate = $this->_intermediate;

		if (typeof($intermediate) == "array")
		{
			return $intermediate;
		}

		$phql = $this->_phql;
		$ast = phql_parse_phql($phql);

		$irPhql = null;
		$uniqueId = null;

		if (typeof($ast) == "array")
		{
			if (function() { if(isset($ast["id"])) {$uniqueId = $ast["id"]; return $uniqueId; } else { return false; } }())
			{
				if (function() { if(isset(self::_irPhqlCache[$uniqueId])) {$irPhql = self::_irPhqlCache[$uniqueId]; return $irPhql; } else { return false; } }())
				{
					if (typeof($irPhql) == "array")
					{
						$this->_type = $ast["type"];

						return $irPhql;
					}

				}

			}

			if (function() { if(isset($ast["type"])) {$type = $ast["type"]; return $type; } else { return false; } }())
			{
				$this->_ast = $ast;
				$this->_type = $type;

				switch ($type) {
					case PHQL_T_SELECT:
						$irPhql = $this->_prepareSelect();
						break;

					case PHQL_T_INSERT:
						$irPhql = $this->_prepareInsert();
						break;

					case PHQL_T_UPDATE:
						$irPhql = $this->_prepareUpdate();
						break;

					case PHQL_T_DELETE:
						$irPhql = $this->_prepareDelete();
						break;

					default:
						throw new Exception("Unknown statement " . $type . ", when preparing: " . $phql);

				}

			}

		}

		if (typeof($irPhql) <> "array")
		{
			throw new Exception("Corrupted AST");
		}

		if (typeof($uniqueId) == "int")
		{
			self::$uniqueId = $irPhql;

		}

		$this->_intermediate = $irPhql;

		return $irPhql;
	}

	public function getCache()
	{
		return $this->_cache;
	}

	protected final function _executeSelect($intermediate, $bindParams, $bindTypes, $simulate = false)
	{



		$manager = $this->_manager;

		$connectionTypes = [];

		$models = $intermediate["models"];

		foreach ($models as $modelName) {
			if (!(function() { if(isset($this->_modelsInstances[$modelName])) {$model = $this->_modelsInstances[$modelName]; return $model; } else { return false; } }()))
			{
				$model = $manager->load($modelName, true);
				$this[$modelName] = $model;

			}
			$connection = $this->getReadConnection($model, $intermediate, $bindParams, $bindTypes);
			if (typeof($connection) == "object")
			{
				$connectionTypes[$connection->getType()] = true;

				if (count($connectionTypes) == 2)
				{
					throw new Exception("Cannot use models of different database systems in the same query");
				}

			}
		}

		$columns = $intermediate["columns"];

		$haveObjects = false;
		$haveScalars = false;
		$isComplex = false;

		$numberObjects = 0;

		$columns1 = $columns;

		foreach ($columns as $column) {
			if (typeof($column) <> "array")
			{
				throw new Exception("Invalid column definition");
			}
			if ($column["type"] == "scalar")
			{
				if (!(isset($column["balias"])))
				{
					$isComplex = true;

				}

				$haveScalars = true;

			}
		}

		if ($isComplex === false)
		{
			if ($haveObjects === true)
			{
				if ($haveScalars === true)
				{
					$isComplex = true;

				}

			}

		}

		$instance = null;
		$selectColumns = [];
		$simpleColumnMap = [];
		$metaData = $this->_metaData;

		foreach ($columns as $aliasCopy => $column) {
			$sqlColumn = $column["column"];
			if ($column["type"] == "object")
			{
				$modelName = $column["model"];

				if (!(function() { if(isset($this->_modelsInstances[$modelName])) {$instance = $this->_modelsInstances[$modelName]; return $instance; } else { return false; } }()))
				{
					$instance = $manager->load($modelName);
					$this[$modelName] = $instance;

				}

				$attributes = $metaData->getAttributes($instance);

				if ($isComplex === true)
				{
					if (globals_get("orm.column_renaming"))
					{
						$columnMap = $metaData->getColumnMap($instance);

					}

					foreach ($attributes as $attribute) {
						$selectColumns = [$attribute, $sqlColumn, "_" . $sqlColumn . "_" . $attribute];
					}

					$columns1[$aliasCopy] = $instance;
					$columns1[$aliasCopy] = $attributes;
					$columns1[$aliasCopy] = $columnMap;

					$isKeepingSnapshots = (bool) $manager->isKeepingSnapshots($instance);

					if ($isKeepingSnapshots)
					{
						$columns1[$aliasCopy] = $isKeepingSnapshots;

					}

				}

			}
			if ($isComplex === false && $isSimpleStd === true)
			{
				if (function() { if(isset($column["sqlAlias"])) {$sqlAlias = $column["sqlAlias"]; return $sqlAlias; } else { return false; } }())
				{
					$simpleColumnMap[$sqlAlias] = $aliasCopy;

				}

			}
		}

		$bindCounts = [];
		$intermediate["columns"] = $selectColumns;

		if (typeof($bindParams) == "array")
		{
			$processed = [];

			foreach ($bindParams as $wildcard => $value) {
				if (typeof($wildcard) == "integer")
				{
					$wildcardValue = ":" . $wildcard;

				}
				$processed[$wildcardValue] = $value;
				if (typeof($value) == "array")
				{
					$bindCounts[$wildcardValue] = count($value);

				}
			}

		}

		if (typeof($bindTypes) == "array")
		{
			$processedTypes = [];

			foreach ($bindTypes as $typeWildcard => $value) {
				if (typeof($typeWildcard) == "integer")
				{
					$processedTypes[":" . $typeWildcard] = $value;

				}
			}

		}

		if (count($bindCounts))
		{
			$intermediate["bindCounts"] = $bindCounts;

		}

		$dialect = $connection->getDialect();
		$sqlSelect = $dialect->select($intermediate);

		if ($this->_sharedLock)
		{
			$sqlSelect = $dialect->sharedLock($sqlSelect);

		}

		if ($simulate)
		{
			return ["sql" => $sqlSelect, "bind" => $processed, "bindTypes" => $processedTypes];
		}

		$result = $connection->query($sqlSelect, $processed, $processedTypes);

		if ($result instanceof $ResultInterface && $result->numRows())
		{
			$resultData = $result;

		}

		$cache = $this->_cache;

		if ($isComplex === false)
		{
			if ($isSimpleStd === true)
			{
				$resultObject = new Row();

				$isKeepingSnapshots = false;

			}

			if ($resultObject instanceof $ModelInterface && method_exists($resultObject, "getResultsetClass"))
			{
				$resultsetClassName = $resultObject->getResultsetClass();

				if ($resultsetClassName)
				{
					if (!(class_exists($resultsetClassName)))
					{
						throw new Exception("Resultset class \"" . $resultsetClassName . "\" not found");
					}

					if (!(is_subclass_of($resultsetClassName, "Phalcon\\Mvc\\Model\\ResultsetInterface")))
					{
						throw new Exception("Resultset class \"" . $resultsetClassName . "\" must be an implementation of Phalcon\\Mvc\\Model\\ResultsetInterface");
					}

					return new $resultsetClassName($simpleColumnMap, $resultObject, $resultData, $cache, $isKeepingSnapshots);
				}

			}

			return new Simple($simpleColumnMap, $resultObject, $resultData, $cache, $isKeepingSnapshots);
		}

		return new Complex($columns1, $resultData, $cache);
	}

	protected final function _executeInsert($intermediate, $bindParams, $bindTypes)
	{


		$modelName = $intermediate["model"];

		$manager = $this->_manager;

		if (!(function() { if(isset($this->_modelsInstances[$modelName])) {$model = $this->_modelsInstances[$modelName]; return $model; } else { return false; } }()))
		{
			$model = $manager->load($modelName, true);

		}

		$connection = $this->getWriteConnection($model, $intermediate, $bindParams, $bindTypes);

		$metaData = $this->_metaData;
		$attributes = $metaData->getAttributes($model);

		$automaticFields = false;

		if (!(function() { if(isset($intermediate["fields"])) {$fields = $intermediate["fields"]; return $fields; } else { return false; } }()))
		{
			$automaticFields = true;
			$fields = $attributes;

			if (globals_get("orm.column_renaming"))
			{
				$columnMap = $metaData->getColumnMap($model);

			}

		}

		$values = $intermediate["values"];

		if (count($fields) <> count($values))
		{
			throw new Exception("The column count does not match the values count");
		}

		$dialect = $connection->getDialect();

		$insertValues = [];

		foreach ($values as $number => $value) {
			$exprValue = $value["value"];
			switch ($value["type"]) {
				case PHQL_T_STRING:

				case PHQL_T_INTEGER:

				case PHQL_T_DOUBLE:
					$insertValue = $dialect->getSqlExpression($exprValue);
					break;

				case PHQL_T_NULL:
					$insertValue = null;
					break;

				case PHQL_T_NPLACEHOLDER:

				case PHQL_T_SPLACEHOLDER:

				case PHQL_T_BPLACEHOLDER:
					if (typeof($bindParams) <> "array")
					{
						throw new Exception("Bound parameter cannot be replaced because placeholders is not an array");
					}
					$wildcard = str_replace(":", "", $dialect->getSqlExpression($exprValue));
					if (!(function() { if(isset($bindParams[$wildcard])) {$insertValue = $bindParams[$wildcard]; return $insertValue; } else { return false; } }()))
					{
						throw new Exception("Bound parameter '" . $wildcard . "' cannot be replaced because it isn't in the placeholders list");
					}
					break;

				default:
					$insertValue = new RawValue($dialect->getSqlExpression($exprValue));
					break;


			}
			$fieldName = $fields[$number];
			if ($automaticFields === true)
			{
				if (typeof($columnMap) == "array")
				{
					if (!(function() { if(isset($columnMap[$fieldName])) {$attributeName = $columnMap[$fieldName]; return $attributeName; } else { return false; } }()))
					{
						throw new Exception("Column '" . $fieldName . "' isn't part of the column map");
					}

				}

			}
			$insertValues[$attributeName] = $insertValue;
		}

		$insertModel = clone $manager->load($modelName);

		return new Status($insertModel->create($insertValues), $insertModel);
	}

	protected final function _executeUpdate($intermediate, $bindParams, $bindTypes)
	{

		$models = $intermediate["models"];

		if (isset($models[1]))
		{
			throw new Exception("Updating several models at the same time is still not supported");
		}

		$modelName = $models[0];

		if (!(function() { if(isset($this->_modelsInstances[$modelName])) {$model = $this->_modelsInstances[$modelName]; return $model; } else { return false; } }()))
		{
			$model = $this->_manager->load($modelName);

		}

		$connection = $this->getWriteConnection($model, $intermediate, $bindParams, $bindTypes);

		$dialect = $connection->getDialect();

		$fields = $intermediate["fields"];
		$values = $intermediate["values"];

		$updateValues = [];

		$selectBindParams = $bindParams;
		$selectBindTypes = $bindTypes;

		foreach ($fields as $number => $field) {
			$value = $values[$number];
			$exprValue = $value["value"];
			if (isset($field["balias"]))
			{
				$fieldName = $field["balias"];

			}
			switch ($value["type"]) {
				case PHQL_T_STRING:

				case PHQL_T_INTEGER:

				case PHQL_T_DOUBLE:
					$updateValue = $dialect->getSqlExpression($exprValue);
					break;

				case PHQL_T_NULL:
					$updateValue = null;
					break;

				case PHQL_T_NPLACEHOLDER:

				case PHQL_T_SPLACEHOLDER:

				case PHQL_T_BPLACEHOLDER:
					if (typeof($bindParams) <> "array")
					{
						throw new Exception("Bound parameter cannot be replaced because placeholders is not an array");
					}
					$wildcard = str_replace(":", "", $dialect->getSqlExpression($exprValue));
					if (function() { if(isset($bindParams[$wildcard])) {$updateValue = $bindParams[$wildcard]; return $updateValue; } else { return false; } }())
					{
						unset($selectBindParams[$wildcard]);

						unset($selectBindTypes[$wildcard]);

					}
					break;

				case PHQL_T_BPLACEHOLDER:
					throw new Exception("Not supported");
				default:
					$updateValue = new RawValue($dialect->getSqlExpression($exprValue));
					break;


			}
			$updateValues[$fieldName] = $updateValue;
		}

		$records = $this->_getRelatedRecords($model, $intermediate, $selectBindParams, $selectBindTypes);

		if (!(count($records)))
		{
			return new Status(true);
		}

		$connection = $this->getWriteConnection($model, $intermediate, $bindParams, $bindTypes);

		$connection->begin();

		$records->rewind();

		while ($records->valid()) {
			$record = $records->current();
			if (!($record->update($updateValues)))
			{
				$connection->rollback();

				return new Status(false, $record);
			}
			$records->next();
		}

		$connection->commit();

		return new Status(true);
	}

	protected final function _executeDelete($intermediate, $bindParams, $bindTypes)
	{

		$models = $intermediate["models"];

		if (isset($models[1]))
		{
			throw new Exception("Delete from several models at the same time is still not supported");
		}

		$modelName = $models[0];

		if (!(function() { if(isset($this->_modelsInstances[$modelName])) {$model = $this->_modelsInstances[$modelName]; return $model; } else { return false; } }()))
		{
			$model = $this->_manager->load($modelName);

		}

		$records = $this->_getRelatedRecords($model, $intermediate, $bindParams, $bindTypes);

		if (!(count($records)))
		{
			return new Status(true);
		}

		$connection = $this->getWriteConnection($model, $intermediate, $bindParams, $bindTypes);

		$connection->begin();

		$records->rewind();

		while ($records->valid()) {
			$record = $records->current();
			if (!($record->delete()))
			{
				$connection->rollback();

				return new Status(false, $record);
			}
			$records->next();
		}

		$connection->commit();

		return new Status(true);
	}

	protected final function _getRelatedRecords($model, $intermediate, $bindParams, $bindTypes)
	{

		$selectIr = ["columns" => [["type" => "object", "model" => get_class($model), "column" => $model->getSource()]], "models" => $intermediate["models"], "tables" => $intermediate["tables"]];

		if (function() { if(isset($intermediate["where"])) {$whereConditions = $intermediate["where"]; return $whereConditions; } else { return false; } }())
		{
			$selectIr["where"] = $whereConditions;

		}

		if (function() { if(isset($intermediate["limit"])) {$limitConditions = $intermediate["limit"]; return $limitConditions; } else { return false; } }())
		{
			$selectIr["limit"] = $limitConditions;

		}

		$query = new self();

		$query->setDI($this->_dependencyInjector);

		$query->setType(PHQL_T_SELECT);

		$query->setIntermediate($selectIr);

		return $query->execute($bindParams, $bindTypes);
	}

	public function execute($bindParams = null, $bindTypes = null)
	{

		$uniqueRow = $this->_uniqueRow;

		$cacheOptions = $this->_cacheOptions;

		if (typeof($cacheOptions) <> "null")
		{
			if (typeof($cacheOptions) <> "array")
			{
				throw new Exception("Invalid caching options");
			}

			if (!(function() { if(isset($cacheOptions["key"])) {$key = $cacheOptions["key"]; return $key; } else { return false; } }()))
			{
				throw new Exception("A cache key must be provided to identify the cached resultset in the cache backend");
			}

			if (!(function() { if(isset($cacheOptions["lifetime"])) {$lifetime = $cacheOptions["lifetime"]; return $lifetime; } else { return false; } }()))
			{
				$lifetime = 3600;

			}

			if (!(function() { if(isset($cacheOptions["service"])) {$cacheService = $cacheOptions["service"]; return $cacheService; } else { return false; } }()))
			{
				$cacheService = "modelsCache";

			}

			$cache = $this->_dependencyInjector->getShared($cacheService);

			if (typeof($cache) <> "object")
			{
				throw new Exception("Cache service must be an object");
			}

			$result = $cache->get($key, $lifetime);

			if ($result !== null)
			{
				if (typeof($result) <> "object")
				{
					throw new Exception("Cache didn't return a valid resultset");
				}

				$result->setIsFresh(false);

				if ($uniqueRow)
				{
					$preparedResult = $result->getFirst();

				}

				return $preparedResult;
			}

			$this->_cache = $cache;

		}

		$defaultBindParams = $this->_bindParams;

		if (typeof($defaultBindParams) == "array")
		{
			if (typeof($bindParams) == "array")
			{
				$mergedParams = $defaultBindParams + $bindParams;

			}

		}

		$this->_bindParams = $mergedParams;

		$intermediate = $this->parse();

		$defaultBindTypes = $this->_bindTypes;

		if (typeof($defaultBindTypes) == "array")
		{
			if (typeof($bindTypes) == "array")
			{
				$mergedTypes = $defaultBindTypes + $bindTypes;

			}

		}

		if (typeof($mergedParams) <> "null" && typeof($mergedParams) <> "array")
		{
			throw new Exception("Bound parameters must be an array");
		}

		if (typeof($mergedTypes) <> "null" && typeof($mergedTypes) <> "array")
		{
			throw new Exception("Bound parameter types must be an array");
		}

		$type = $this->_type;

		switch ($type) {
			case PHQL_T_SELECT:
				$result = $this->_executeSelect($intermediate, $mergedParams, $mergedTypes);
				break;

			case PHQL_T_INSERT:
				$result = $this->_executeInsert($intermediate, $mergedParams, $mergedTypes);
				break;

			case PHQL_T_UPDATE:
				$result = $this->_executeUpdate($intermediate, $mergedParams, $mergedTypes);
				break;

			case PHQL_T_DELETE:
				$result = $this->_executeDelete($intermediate, $mergedParams, $mergedTypes);
				break;

			default:
				throw new Exception("Unknown statement " . $type);

		}

		if ($cacheOptions !== null)
		{
			if ($type <> PHQL_T_SELECT)
			{
				throw new Exception("Only PHQL statements that return resultsets can be cached");
			}

			$cache->save($key, $result, $lifetime);

		}

		if ($uniqueRow)
		{
			$preparedResult = $result->getFirst();

		}

		return $preparedResult;
	}

	public function getSingleResult($bindParams = null, $bindTypes = null)
	{
		if ($this->_uniqueRow)
		{
			return $this->execute($bindParams, $bindTypes);
		}

		return $this->execute($bindParams, $bindTypes)->getFirst();
	}

	public function setType($type)
	{
		$this->_type = $type;

		return $this;
	}

	public function getType()
	{
		return $this->_type;
	}

	public function setBindParams($bindParams, $merge = false)
	{

		if ($merge)
		{
			$currentBindParams = $this->_bindParams;

			if (typeof($currentBindParams) == "array")
			{
				$this->_bindParams = $currentBindParams + $bindParams;

			}

		}

		return $this;
	}

	public function getBindParams()
	{
		return $this->_bindParams;
	}

	public function setBindTypes($bindTypes, $merge = false)
	{

		if ($merge)
		{
			$currentBindTypes = $this->_bindTypes;

			if (typeof($currentBindTypes) == "array")
			{
				$this->_bindTypes = $currentBindTypes + $bindTypes;

			}

		}

		return $this;
	}

	public function setSharedLock($sharedLock = false)
	{
		$this->_sharedLock = $sharedLock;

		return $this;
	}

	public function getBindTypes()
	{
		return $this->_bindTypes;
	}

	public function setIntermediate($intermediate)
	{
		$this->_intermediate = $intermediate;

		return $this;
	}

	public function getIntermediate()
	{
		return $this->_intermediate;
	}

	public function cache($cacheOptions)
	{
		$this->_cacheOptions = $cacheOptions;

		return $this;
	}

	public function getCacheOptions()
	{
		return $this->_cacheOptions;
	}

	public function getSql()
	{

		$intermediate = $this->parse();

		if ($this->_type == PHQL_T_SELECT)
		{
			return $this->_executeSelect($intermediate, $this->_bindParams, $this->_bindTypes, true);
		}

		throw new Exception("This type of statement generates multiple SQL statements");
	}

	public static function clean()
	{
		self::_irPhqlCache = [];

	}

	protected function getReadConnection($model, $intermediate = null, $bindParams = null, $bindTypes = null)
	{

		$transaction = $this->_transaction;

		if (typeof($transaction) == "object" && $transaction instanceof $TransactionInterface)
		{
			return $transaction->getConnection();
		}

		if (method_exists($model, "selectReadConnection"))
		{
			$connection = $model->selectReadConnection($intermediate, $bindParams, $bindTypes);

			if (typeof($connection) <> "object")
			{
				throw new Exception("selectReadConnection did not return a connection");
			}

			return $connection;
		}

		return $model->getReadConnection();
	}

	protected function getWriteConnection($model, $intermediate = null, $bindParams = null, $bindTypes = null)
	{

		$transaction = $this->_transaction;

		if (typeof($transaction) == "object" && $transaction instanceof $TransactionInterface)
		{
			return $transaction->getConnection();
		}

		if (method_exists($model, "selectWriteConnection"))
		{
			$connection = $model->selectWriteConnection($intermediate, $bindParams, $bindTypes);

			if (typeof($connection) <> "object")
			{
				throw new Exception("selectWriteConnection did not return a connection");
			}

			return $connection;
		}

		return $model->getWriteConnection();
	}

	public function setTransaction($transaction)
	{
		$this->_transaction = $transaction;

		return $this;
	}


}