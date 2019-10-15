<?php
namespace Phalcon\Mvc;

use Phalcon\Di;
use Phalcon\Db\Column;
use Phalcon\Db\RawValue;
use Phalcon\DiInterface;
use Phalcon\Mvc\Model\Message;
use Phalcon\Mvc\Model\ResultInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Model\ManagerInterface;
use Phalcon\Mvc\Model\MetaDataInterface;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Db\AdapterInterface;
use Phalcon\Db\DialectInterface;
use Phalcon\Mvc\Model\CriteriaInterface;
use Phalcon\Mvc\Model\TransactionInterface;
use Phalcon\Mvc\Model\Resultset;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\Model\Query\Builder;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Mvc\Model\RelationInterface;
use Phalcon\Mvc\Model\BehaviorInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\MessageInterface;
use Phalcon\Mvc\Model\Message;
use Phalcon\ValidationInterface;
use Phalcon\Mvc\Model\ValidationFailed;
use Phalcon\Events\ManagerInterface as EventsManagerInterface;
abstract 
class Model implements EntityInterface, ModelInterface, ResultInterface, InjectionAwareInterface, \Serializable, \JsonSerializable
{
	const TRANSACTION_INDEX = "transaction";
	const OP_NONE = 0;
	const OP_CREATE = 1;
	const OP_UPDATE = 2;
	const OP_DELETE = 3;
	const DIRTY_STATE_PERSISTENT = 0;
	const DIRTY_STATE_TRANSIENT = 1;
	const DIRTY_STATE_DETACHED = 2;

	protected $_dependencyInjector;
	protected $_modelsManager;
	protected $_modelsMetaData;
	protected $_errorMessages;
	protected $_operationMade = 0;
	protected $_dirtyState = 1;
	protected $_transaction;
	protected $_uniqueKey;
	protected $_uniqueParams;
	protected $_uniqueTypes;
	protected $_skipped;
	protected $_related;
	protected $_snapshot;
	protected $_oldSnapshot;

	public final function __construct($data = null, $dependencyInjector = null, $modelsManager = null)
	{
		$this->_oldSnapshot = [];

		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = Di::getDefault();

		}

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injector container is required to obtain the services related to the ORM");
		}

		$this->_dependencyInjector = $dependencyInjector;

		if (typeof($modelsManager) <> "object")
		{
			$modelsManager = $dependencyInjector->getShared("modelsManager");

			if (typeof($modelsManager) <> "object")
			{
				throw new Exception("The injected service 'modelsManager' is not valid");
			}

		}

		$this->_modelsManager = $modelsManager;

		$modelsManager->initialize($this);

		if (method_exists($this, "onConstruct"))
		{
			$this->onConstruct($data);

		}

		if (typeof($data) == "array")
		{
			$this->assign($data);

		}

	}

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	protected function setEventsManager($eventsManager)
	{
		$this->_modelsManager->setCustomEventsManager($this, $eventsManager);

	}

	protected function getEventsManager()
	{
		return $this->_modelsManager->getCustomEventsManager($this);
	}

	public function getModelsMetaData()
	{

		$metaData = $this->_modelsMetaData;

		if (typeof($metaData) <> "object")
		{
			$dependencyInjector = $this->_dependencyInjector;

			$metaData = $dependencyInjector->getShared("modelsMetadata");

			if (typeof($metaData) <> "object")
			{
				throw new Exception("The injected service 'modelsMetadata' is not valid");
			}

			$this->_modelsMetaData = $metaData;

		}

		return $metaData;
	}

	public function getModelsManager()
	{
		return $this->_modelsManager;
	}

	public function setTransaction($transaction)
	{
		$this->_transaction = $transaction;

		return $this;
	}

	protected function setSource($source)
	{
		$this->_modelsManager->setModelSource($this, $source);

		return $this;
	}

	public function getSource()
	{
		return $this->_modelsManager->getModelSource($this);
	}

	protected function setSchema($schema)
	{
		return $this->_modelsManager->setModelSchema($this, $schema);
	}

	public function getSchema()
	{
		return $this->_modelsManager->getModelSchema($this);
	}

	public function setConnectionService($connectionService)
	{
		$this->_modelsManager->setConnectionService($this, $connectionService);

		return $this;
	}

	public function setReadConnectionService($connectionService)
	{
		$this->_modelsManager->setReadConnectionService($this, $connectionService);

		return $this;
	}

	public function setWriteConnectionService($connectionService)
	{
		return $this->_modelsManager->setWriteConnectionService($this, $connectionService);
	}

	public function getReadConnectionService()
	{
		return $this->_modelsManager->getReadConnectionService($this);
	}

	public function getWriteConnectionService()
	{
		return $this->_modelsManager->getWriteConnectionService($this);
	}

	public function setDirtyState($dirtyState)
	{
		$this->_dirtyState = $dirtyState;

		return $this;
	}

	public function getDirtyState()
	{
		return $this->_dirtyState;
	}

	public function getReadConnection()
	{

		$transaction = $this->_transaction;

		if (typeof($transaction) == "object")
		{
			return $transaction->getConnection();
		}

		return $this->_modelsManager->getReadConnection($this);
	}

	public function getWriteConnection()
	{

		$transaction = $this->_transaction;

		if (typeof($transaction) == "object")
		{
			return $transaction->getConnection();
		}

		return $this->_modelsManager->getWriteConnection($this);
	}

	public function assign($data, $dataColumnMap = null, $whiteList = null)
	{

		$disableAssignSetters = globals_get("orm.disable_assign_setters");

		if (typeof($dataColumnMap) == "array")
		{
			$dataMapped = [];

			foreach ($data as $key => $value) {
				if (function() { if(isset($dataColumnMap[$key])) {$keyMapped = $dataColumnMap[$key]; return $keyMapped; } else { return false; } }())
				{
					$dataMapped[$keyMapped] = $value;

				}
			}

		}

		if (count($dataMapped) == 0)
		{
			return $this;
		}

		$metaData = $this->getModelsMetaData();

		if (globals_get("orm.column_renaming"))
		{
			$columnMap = $metaData->getColumnMap($this);

		}

		foreach ($metaData->getAttributes($this) as $attribute) {
			if (typeof($columnMap) == "array")
			{
				if (!(function() { if(isset($columnMap[$attribute])) {$attributeField = $columnMap[$attribute]; return $attributeField; } else { return false; } }()))
				{
					if (!(globals_get("orm.ignore_unknown_columns")))
					{
						throw new Exception("Column '" . $attribute . "' doesn\'t make part of the column map");
					}

				}

			}
			if (function() { if(isset($dataMapped[$attributeField])) {$value = $dataMapped[$attributeField]; return $value; } else { return false; } }())
			{
				if (typeof($whiteList) == "array")
				{
					if (!(in_array($attributeField, $whiteList)))
					{
						continue;

					}

				}

				if ($disableAssignSetters || !($this->_possibleSetter($attributeField, $value)))
				{
					$this->{$attributeField} = $value;

				}

			}
		}

		return $this;
	}

	public static function cloneResultMap($base, $data, $columnMap, $dirtyState = 0, $keepSnapshots = null)
	{

		$instance = clone $base;

		$instance->setDirtyState($dirtyState);

		foreach ($data as $key => $value) {
			if (typeof($key) == "string")
			{
				if (typeof($columnMap) <> "array")
				{
					$instance->{$key} = $value;

					continue;

				}

				if (!(function() { if(isset($columnMap[$key])) {$attribute = $columnMap[$key]; return $attribute; } else { return false; } }()))
				{
					if (!(globals_get("orm.ignore_unknown_columns")))
					{
						throw new Exception("Column '" . $key . "' doesn't make part of the column map");
					}

				}

				if (typeof($attribute) <> "array")
				{
					$instance->{$attribute} = $value;

					continue;

				}

				if ($value <> "" && $value !== null)
				{
					switch ($attribute[1]) {
						case Column::TYPE_INTEGER:
							$castValue = intval($value, 10);
							break;

						case Column::TYPE_DOUBLE:

						case Column::TYPE_DECIMAL:

						case Column::TYPE_FLOAT:
							$castValue = doubleval($value);
							break;

						case Column::TYPE_BOOLEAN:
							$castValue = (bool) $value;
							break;

						default:
							$castValue = $value;
							break;


					}

				}

				$attributeName = $attribute[0];
				$instance->{$attributeName} = $castValue;

			}
		}

		if ($keepSnapshots)
		{
			$instance->setSnapshotData($data, $columnMap);

			$instance->setOldSnapshotData($data, $columnMap);

		}

		if (method_exists($instance, "fireEvent"))
		{
			$instance->fireEvent("afterFetch");

		}

		return $instance;
	}

	public static function cloneResultMapHydrate($data, $columnMap, $hydrationMode)
	{

		if (typeof($columnMap) <> "array")
		{
			if ($hydrationMode == Resultset::HYDRATE_ARRAYS)
			{
				return $data;
			}

		}

		if ($hydrationMode == Resultset::HYDRATE_ARRAYS)
		{
			$hydrateArray = [];

		}

		foreach ($data as $key => $value) {
			if (typeof($key) <> "string")
			{
				continue;

			}
			if (typeof($columnMap) == "array")
			{
				if (!(function() { if(isset($columnMap[$key])) {$attribute = $columnMap[$key]; return $attribute; } else { return false; } }()))
				{
					if (!(globals_get("orm.ignore_unknown_columns")))
					{
						throw new Exception("Column '" . $key . "' doesn't make part of the column map");
					}

				}

				if (typeof($attribute) == "array")
				{
					$attributeName = $attribute[0];

				}

				if ($hydrationMode == Resultset::HYDRATE_ARRAYS)
				{
					$hydrateArray[$attributeName] = $value;

				}

			}
		}

		if ($hydrationMode == Resultset::HYDRATE_ARRAYS)
		{
			return $hydrateArray;
		}

		return $hydrateObject;
	}

	public static function cloneResult($base, $data, $dirtyState = 0)
	{

		$instance = clone $base;

		$instance->setDirtyState($dirtyState);

		foreach ($data as $key => $value) {
			if (typeof($key) <> "string")
			{
				throw new Exception("Invalid key in array data provided to dumpResult()");
			}
			$instance->{$key} = $value;
		}

		$instance->fireEvent("afterFetch");

		return $instance;
	}

	public static function find($parameters = null)
	{

		if (typeof($parameters) <> "array")
		{
			$params = [];

			if ($parameters !== null)
			{
				$params = $parameters;

			}

		}

		$query = static::getPreparedQuery($params);

		$resultset = $query->execute();

		if (typeof($resultset) == "object")
		{
			if (function() { if(isset($params["hydration"])) {$hydration = $params["hydration"]; return $hydration; } else { return false; } }())
			{
				$resultset->setHydrateMode($hydration);

			}

		}

		return $resultset;
	}

	public static function findFirst($parameters = null)
	{

		if (typeof($parameters) <> "array")
		{
			$params = [];

			if ($parameters !== null)
			{
				$params = $parameters;

			}

		}

		$query = static::getPreparedQuery($params, 1);

		$query->setUniqueRow(true);

		return $query->execute();
	}

	private static function getPreparedQuery($params, $limit = null)
	{

		$dependencyInjector = Di::getDefault();

		$manager = $dependencyInjector->getShared("modelsManager");

		$builder = $manager->createBuilder($params);

		$builder->from(get_called_class());

		if ($limit <> null)
		{
			$builder->limit($limit);

		}

		$query = $builder->getQuery();

		if (function() { if(isset($params["bind"])) {$bindParams = $params["bind"]; return $bindParams; } else { return false; } }())
		{
			if (typeof($bindParams) == "array")
			{
				$query->setBindParams($bindParams, true);

			}

			if (function() { if(isset($params["bindTypes"])) {$bindTypes = $params["bindTypes"]; return $bindTypes; } else { return false; } }())
			{
				if (typeof($bindTypes) == "array")
				{
					$query->setBindTypes($bindTypes, true);

				}

			}

		}

		if (function() { if(isset($params[self::TRANSACTION_INDEX])) {$transaction = $params[self::TRANSACTION_INDEX]; return $transaction; } else { return false; } }())
		{
			if ($transaction instanceof $TransactionInterface)
			{
				$query->setTransaction($transaction);

			}

		}

		if (function() { if(isset($params["cache"])) {$cache = $params["cache"]; return $cache; } else { return false; } }())
		{
			$query->cache($cache);

		}

		return $query;
	}

	public static function query($dependencyInjector = null)
	{

		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = Di::getDefault();

		}

		if ($dependencyInjector instanceof $DiInterface)
		{
			$criteria = $dependencyInjector->get("Phalcon\\Mvc\\Model\\Criteria");

		}

		$criteria->setModelName(get_called_class());

		return $criteria;
	}

	protected function _exists($metaData, $connection, $table = null)
	{


		$uniqueParams = null;
		$uniqueTypes = null;

		$uniqueKey = $this->_uniqueKey;

		if ($uniqueKey === null)
		{
			$primaryKeys = $metaData->getPrimaryKeyAttributes($this);
			$bindDataTypes = $metaData->getBindTypes($this);

			$numberPrimary = count($primaryKeys);

			if (!($numberPrimary))
			{
				return false;
			}

			if (globals_get("orm.column_renaming"))
			{
				$columnMap = $metaData->getColumnMap($this);

			}

			$numberEmpty = 0;
			$wherePk = [];
			$uniqueParams = [];
			$uniqueTypes = [];

			foreach ($primaryKeys as $field) {
				if (typeof($columnMap) == "array")
				{
					if (!(function() { if(isset($columnMap[$field])) {$attributeField = $columnMap[$field]; return $attributeField; } else { return false; } }()))
					{
						throw new Exception("Column '" . $field . "' isn't part of the column map");
					}

				}
				$value = null;
				if (function() { if(isset($this->$attributeField)) {$value = $this->$attributeField; return $value; } else { return false; } }())
				{
					if ($value === null || $value === "")
					{
						$numberEmpty++;

					}

					$uniqueParams = $value;

				}
				if (!(function() { if(isset($bindDataTypes[$field])) {$type = $bindDataTypes[$field]; return $type; } else { return false; } }()))
				{
					throw new Exception("Column '" . $field . "' isn't part of the table columns");
				}
				$uniqueTypes = $type;
				$wherePk = $connection->escapeIdentifier($field) . " = ?";
			}

			if ($numberPrimary == $numberEmpty)
			{
				return false;
			}

			$joinWhere = join(" AND ", $wherePk);

			$this->_uniqueKey = $joinWhere;
			$this->_uniqueParams = $uniqueParams;
			$this->_uniqueTypes = $uniqueTypes;
			$uniqueKey = $joinWhere;

		}

		if (!($this->_dirtyState))
		{
			return true;
		}

		if ($uniqueKey === null)
		{
			$uniqueKey = $this->_uniqueKey;

		}

		if ($uniqueParams === null)
		{
			$uniqueParams = $this->_uniqueParams;

		}

		if ($uniqueTypes === null)
		{
			$uniqueTypes = $this->_uniqueTypes;

		}

		$schema = $this->getSchema();
		$source = $this->getSource();

		if ($schema)
		{
			$table = [$schema, $source];

		}

		$num = $connection->fetchOne("SELECT COUNT(*) \"rowcount\" FROM " . $connection->escapeIdentifier($table) . " WHERE " . $uniqueKey, null, $uniqueParams, $uniqueTypes);

		if ($num["rowcount"])
		{
			$this->_dirtyState = self::DIRTY_STATE_PERSISTENT;

			return true;
		}

		return false;
	}

	protected static function _groupResult($functionName, $alias, $parameters)
	{

		$dependencyInjector = Di::getDefault();

		$manager = $dependencyInjector->getShared("modelsManager");

		if (typeof($parameters) <> "array")
		{
			$params = [];

			if ($parameters !== null)
			{
				$params = $parameters;

			}

		}

		if (!(function() { if(isset($params["column"])) {$groupColumn = $params["column"]; return $groupColumn; } else { return false; } }()))
		{
			$groupColumn = "*";

		}

		if (function() { if(isset($params["distinct"])) {$distinctColumn = $params["distinct"]; return $distinctColumn; } else { return false; } }())
		{
			$columns = $functionName . "(DISTINCT " . $distinctColumn . ") AS " . $alias;

		}

		$builder = $manager->createBuilder($params);

		$builder->columns($columns);

		$builder->from(get_called_class());

		$query = $builder->getQuery();

		$bindParams = null;
		$bindTypes = null;

		if (function() { if(isset($params["bind"])) {$bindParams = $params["bind"]; return $bindParams; } else { return false; } }())
		{
			$bindTypes = $params["bindTypes"]
		}

		if (function() { if(isset($params["cache"])) {$cache = $params["cache"]; return $cache; } else { return false; } }())
		{
			$query->cache($cache);

		}

		$resultset = $query->execute($bindParams, $bindTypes);

		if (isset($params["group"]))
		{
			return $resultset;
		}

		$firstRow = $resultset->getFirst();

		return $firstRow->$alias;
	}

	public static function count($parameters = null)
	{

		$result = self::_groupResult("COUNT", "rowcount", $parameters);

		if (typeof($result) == "string")
		{
			return (int) $result;
		}

		return $result;
	}

	public static function sum($parameters = null)
	{
		return self::_groupResult("SUM", "sumatory", $parameters);
	}

	public static function maximum($parameters = null)
	{
		return self::_groupResult("MAX", "maximum", $parameters);
	}

	public static function minimum($parameters = null)
	{
		return self::_groupResult("MIN", "minimum", $parameters);
	}

	public static function average($parameters = null)
	{
		return self::_groupResult("AVG", "average", $parameters);
	}

	public function fireEvent($eventName)
	{
		if (method_exists($this, $eventName))
		{
			$this->eventName();

		}

		return $this->_modelsManager->notifyEvent($eventName, $this);
	}

	public function fireEventCancel($eventName)
	{
		if (method_exists($this, $eventName))
		{
			if ($this->eventName() === false)
			{
				return false;
			}

		}

		if ($this->_modelsManager->notifyEvent($eventName, $this) === false)
		{
			return false;
		}

		return true;
	}

	protected function _cancelOperation()
	{
		if ($this->_operationMade == self::OP_DELETE)
		{
			$this->fireEvent("notDeleted");

		}

	}

	public function appendMessage($message)
	{
		$this->_errorMessages[] = $message;

		return $this;
	}

	protected function validate($validator)
	{

		$messages = $validator->validate(null, $this);

		if (typeof($messages) == "boolean")
		{
			return $messages;
		}

		foreach (iterator($messages) as $message) {
			$this->appendMessage(new Message($message->getMessage(), $message->getField(), $message->getType(), null, $message->getCode()));
		}

		return !(count($messages));
	}

	public function validationHasFailed()
	{

		$errorMessages = $this->_errorMessages;

		if (typeof($errorMessages) == "array")
		{
			return count($errorMessages) > 0;
		}

		return false;
	}

	public function getMessages($filter = null)
	{

		if (typeof($filter) == "string" && !(empty($filter)))
		{
			$filtered = [];

			foreach ($this->_errorMessages as $message) {
				if ($message->getField() == $filter)
				{
					$filtered = $message;

				}
			}

			return $filtered;
		}

		return $this->_errorMessages;
	}

	protected final function _checkForeignKeysRestrict()
	{



		$manager = $this->_modelsManager;

		$belongsTo = $manager->getBelongsTo($this);

		$error = false;

		foreach ($belongsTo as $relation) {
			$validateWithNulls = false;
			$foreignKey = $relation->getForeignKey();
			if ($foreignKey === false)
			{
				continue;

			}
			$action = Relation::ACTION_RESTRICT;
			if (typeof($foreignKey) == "array")
			{
				if (isset($foreignKey["action"]))
				{
					$action = (int) $foreignKey["action"];

				}

			}
			if ($action <> Relation::ACTION_RESTRICT)
			{
				continue;

			}
			$referencedModel = $manager->load($relation->getReferencedModel());
			$conditions = [];
			$bindParams = [];
			$numberNull = 0;
			$fields = $relation->getFields();
			$referencedFields = $relation->getReferencedFields();
			if (typeof($fields) == "array")
			{
				foreach ($fields as $position => $field) {
					$value = $this->$field					$conditions = "[" . $referencedFields[$position] . "] = ?" . $position;
					$bindParams = $value;
					if (typeof($value) == "null")
					{
						$numberNull++;

					}
				}

				$validateWithNulls = $numberNull == count($fields);

			}
			if (function() { if(isset($foreignKey["conditions"])) {$extraConditions = $foreignKey["conditions"]; return $extraConditions; } else { return false; } }())
			{
				$conditions = $extraConditions;

			}
			if ($validateWithNulls)
			{
				if (function() { if(isset($foreignKey["allowNulls"])) {$allowNulls = $foreignKey["allowNulls"]; return $allowNulls; } else { return false; } }())
				{
					$validateWithNulls = (bool) $allowNulls;

				}

			}
			if (!($validateWithNulls) && !($referencedModel->count([join(" AND ", $conditions), "bind" => $bindParams])))
			{
				if (!(function() { if(isset($foreignKey["message"])) {$message = $foreignKey["message"]; return $message; } else { return false; } }()))
				{
					if (typeof($fields) == "array")
					{
						$message = "Value of fields \"" . join(", ", $fields) . "\" does not exist on referenced table";

					}

				}

				$this->appendMessage(new Message($message, $fields, "ConstraintViolation"));

				$error = true;

				break;

			}
		}

		if ($error === true)
		{
			if (globals_get("orm.events"))
			{
				$this->fireEvent("onValidationFails");

				$this->_cancelOperation();

			}

			return false;
		}

		return true;
	}

	protected final function _checkForeignKeysReverseCascade()
	{


		$manager = $this->_modelsManager;

		$relations = $manager->getHasOneAndHasMany($this);

		foreach ($relations as $relation) {
			$foreignKey = $relation->getForeignKey();
			if ($foreignKey === false)
			{
				continue;

			}
			$action = Relation::NO_ACTION;
			if (typeof($foreignKey) == "array")
			{
				if (isset($foreignKey["action"]))
				{
					$action = (int) $foreignKey["action"];

				}

			}
			if ($action <> Relation::ACTION_CASCADE)
			{
				continue;

			}
			$referencedModel = $manager->load($relation->getReferencedModel());
			$fields = $relation->getFields();
			$referencedFields = $relation->getReferencedFields();
			$conditions = [];
			$bindParams = [];
			if (typeof($fields) == "array")
			{
				foreach ($fields as $position => $field) {
					$value = $this->$field					$conditions = "[" . $referencedFields[$position] . "] = ?" . $position;
					$bindParams = $value;
				}

			}
			if (function() { if(isset($foreignKey["conditions"])) {$extraConditions = $foreignKey["conditions"]; return $extraConditions; } else { return false; } }())
			{
				$conditions = $extraConditions;

			}
			$resultset = $referencedModel->find([join(" AND ", $conditions), "bind" => $bindParams]);
			if ($resultset->delete() === false)
			{
				return false;
			}
		}

		return true;
	}

	protected final function _checkForeignKeysReverseRestrict()
	{



		$manager = $this->_modelsManager;

		$relations = $manager->getHasOneAndHasMany($this);

		$error = false;

		foreach ($relations as $relation) {
			$foreignKey = $relation->getForeignKey();
			if ($foreignKey === false)
			{
				continue;

			}
			$action = Relation::ACTION_RESTRICT;
			if (typeof($foreignKey) == "array")
			{
				if (isset($foreignKey["action"]))
				{
					$action = (int) $foreignKey["action"];

				}

			}
			if ($action <> Relation::ACTION_RESTRICT)
			{
				continue;

			}
			$relationClass = $relation->getReferencedModel();
			$referencedModel = $manager->load($relationClass);
			$fields = $relation->getFields();
			$referencedFields = $relation->getReferencedFields();
			$conditions = [];
			$bindParams = [];
			if (typeof($fields) == "array")
			{
				foreach ($fields as $position => $field) {
					$value = $this->$field					$conditions = "[" . $referencedFields[$position] . "] = ?" . $position;
					$bindParams = $value;
				}

			}
			if (function() { if(isset($foreignKey["conditions"])) {$extraConditions = $foreignKey["conditions"]; return $extraConditions; } else { return false; } }())
			{
				$conditions = $extraConditions;

			}
			if ($referencedModel->count([join(" AND ", $conditions), "bind" => $bindParams]))
			{
				if (!(function() { if(isset($foreignKey["message"])) {$message = $foreignKey["message"]; return $message; } else { return false; } }()))
				{
					$message = "Record is referenced by model " . $relationClass;

				}

				$this->appendMessage(new Message($message, $fields, "ConstraintViolation"));

				$error = true;

				break;

			}
		}

		if ($error === true)
		{
			if (globals_get("orm.events"))
			{
				$this->fireEvent("onValidationFails");

				$this->_cancelOperation();

			}

			return false;
		}

		return true;
	}

	protected function _preSave($metaData, $exists, $identityField)
	{


		if (globals_get("orm.events"))
		{
			if ($this->fireEventCancel("beforeValidation") === false)
			{
				return false;
			}

			if (!($exists))
			{
				if ($this->fireEventCancel("beforeValidationOnCreate") === false)
				{
					return false;
				}

			}

		}

		if (globals_get("orm.virtual_foreign_keys"))
		{
			if ($this->_checkForeignKeysRestrict() === false)
			{
				return false;
			}

		}

		if (globals_get("orm.not_null_validations"))
		{
			$notNull = $metaData->getNotNullAttributes($this);

			if (typeof($notNull) == "array")
			{
				$dataTypeNumeric = $metaData->getDataTypesNumeric($this);

				if (globals_get("orm.column_renaming"))
				{
					$columnMap = $metaData->getColumnMap($this);

				}

				if ($exists)
				{
					$automaticAttributes = $metaData->getAutomaticUpdateAttributes($this);

				}

				$defaultValues = $metaData->getDefaultValues($this);

				$emptyStringValues = $metaData->getEmptyStringAttributes($this);

				$error = false;

				foreach ($notNull as $field) {
					if (!(isset($automaticAttributes[$field])))
					{
						$isNull = false;

						if (typeof($columnMap) == "array")
						{
							if (!(function() { if(isset($columnMap[$field])) {$attributeField = $columnMap[$field]; return $attributeField; } else { return false; } }()))
							{
								throw new Exception("Column '" . $field . "' isn't part of the column map");
							}

						}

						if (function() { if(isset($this->$attributeField)) {$value = $this->$attributeField; return $value; } else { return false; } }())
						{
							if (typeof($value) <> "object")
							{
								if (!(isset($dataTypeNumeric[$field])))
								{
									if (isset($emptyStringValues[$field]))
									{
										if ($value === null)
										{
											$isNull = true;

										}

									}

								}

							}

						}

						if ($isNull === true)
						{
							if (!($exists))
							{
								if ($field == $identityField)
								{
									continue;

								}

								if (isset($defaultValues[$field]))
								{
									continue;

								}

							}

							$this->_errorMessages[] = new Message($attributeField . " is required", $attributeField, "PresenceOf");
							$error = true;

						}

					}
				}

				if ($error === true)
				{
					if (globals_get("orm.events"))
					{
						$this->fireEvent("onValidationFails");

						$this->_cancelOperation();

					}

					return false;
				}

			}

		}

		if ($this->fireEventCancel("validation") === false)
		{
			if (globals_get("orm.events"))
			{
				$this->fireEvent("onValidationFails");

			}

			return false;
		}

		if (globals_get("orm.events"))
		{
			if (!($exists))
			{
				if ($this->fireEventCancel("afterValidationOnCreate") === false)
				{
					return false;
				}

			}

			if ($this->fireEventCancel("afterValidation") === false)
			{
				return false;
			}

			if ($this->fireEventCancel("beforeSave") === false)
			{
				return false;
			}

			$this->_skipped = false;

			if ($exists)
			{
				if ($this->fireEventCancel("beforeUpdate") === false)
				{
					return false;
				}

			}

			if ($this->_skipped === true)
			{
				return true;
			}

		}

		return true;
	}

	protected function _postSave($success, $exists)
	{
		if ($success === true)
		{
			if ($exists)
			{
				$this->fireEvent("afterUpdate");

			}

		}

		return $success;
	}

	protected function _doLowInsert($metaData, $connection, $table, $identityField)
	{


		$bindSkip = Column::BIND_SKIP;

		$manager = $this->_modelsManager;

		$fields = [];
		$values = [];
		$snapshot = [];
		$bindTypes = [];

		$attributes = $metaData->getAttributes($this);
		$bindDataTypes = $metaData->getBindTypes($this);
		$automaticAttributes = $metaData->getAutomaticCreateAttributes($this);
		$defaultValues = $metaData->getDefaultValues($this);

		if (globals_get("orm.column_renaming"))
		{
			$columnMap = $metaData->getColumnMap($this);

		}

		foreach ($attributes as $field) {
			if (!(isset($automaticAttributes[$field])))
			{
				if (typeof($columnMap) == "array")
				{
					if (!(function() { if(isset($columnMap[$field])) {$attributeField = $columnMap[$field]; return $attributeField; } else { return false; } }()))
					{
						throw new Exception("Column '" . $field . "' isn't part of the column map");
					}

				}

				if ($field <> $identityField)
				{
					if (function() { if(isset($this->$attributeField)) {$value = $this->$attributeField; return $value; } else { return false; } }())
					{
						if ($value === null && isset($defaultValues[$field]))
						{
							$snapshot[$attributeField] = null;

							$value = $connection->getDefaultValue();

						}

						if (!(function() { if(isset($bindDataTypes[$field])) {$bindType = $bindDataTypes[$field]; return $bindType; } else { return false; } }()))
						{
							throw new Exception("Column '" . $field . "' have not defined a bind data type");
						}

						$fields = $field;
						$values = $value;
						$bindTypes = $bindType;

					}

				}

			}
		}

		if ($identityField !== false)
		{
			$defaultValue = $connection->getDefaultIdValue();

			$useExplicitIdentity = (bool) $connection->useExplicitIdValue();

			if ($useExplicitIdentity)
			{
				$fields = $identityField;

			}

			if (typeof($columnMap) == "array")
			{
				if (!(function() { if(isset($columnMap[$identityField])) {$attributeField = $columnMap[$identityField]; return $attributeField; } else { return false; } }()))
				{
					throw new Exception("Identity column '" . $identityField . "' isn't part of the column map");
				}

			}

			if (function() { if(isset($this->$attributeField)) {$value = $this->$attributeField; return $value; } else { return false; } }())
			{
				if ($value === null || $value === "")
				{
					if ($useExplicitIdentity)
					{
						$values = $defaultValue;
						$bindTypes = $bindSkip;

					}

				}

			}

		}

		$success = $connection->insert($table, $values, $fields, $bindTypes);

		if ($success && $identityField !== false)
		{
			$sequenceName = null;

			if ($connection->supportSequences() === true)
			{
				if (method_exists($this, "getSequenceName"))
				{
					$sequenceName = $this->getSequenceName();

				}

			}

			$lastInsertedId = $connection->lastInsertId($sequenceName);

			$this->{$attributeField} = $lastInsertedId;

			$snapshot[$attributeField] = $lastInsertedId;

			$this->_uniqueParams = null;

		}

		if ($success && $manager->isKeepingSnapshots($this) && globals_get("orm.update_snapshot_on_save"))
		{
			$this->_snapshot = $snapshot;

		}

		return $success;
	}

	protected function _doLowUpdate($metaData, $connection, $table)
	{


		$bindSkip = Column::BIND_SKIP;
		$fields = [];
		$values = [];
		$bindTypes = [];
		$newSnapshot = [];
		$manager = $this->_modelsManager;

		$useDynamicUpdate = (bool) $manager->isUsingDynamicUpdate($this);

		$snapshot = $this->_snapshot;

		if ($useDynamicUpdate)
		{
			if (typeof($snapshot) <> "array")
			{
				$useDynamicUpdate = false;

			}

		}

		$dataTypes = $metaData->getDataTypes($this);
		$bindDataTypes = $metaData->getBindTypes($this);
		$nonPrimary = $metaData->getNonPrimaryKeyAttributes($this);
		$automaticAttributes = $metaData->getAutomaticUpdateAttributes($this);

		if (globals_get("orm.column_renaming"))
		{
			$columnMap = $metaData->getColumnMap($this);

		}

		foreach ($nonPrimary as $field) {
			if (!(isset($automaticAttributes[$field])))
			{
				if (!(function() { if(isset($bindDataTypes[$field])) {$bindType = $bindDataTypes[$field]; return $bindType; } else { return false; } }()))
				{
					throw new Exception("Column '" . $field . "' have not defined a bind data type");
				}

				if (typeof($columnMap) == "array")
				{
					if (!(function() { if(isset($columnMap[$field])) {$attributeField = $columnMap[$field]; return $attributeField; } else { return false; } }()))
					{
						throw new Exception("Column '" . $field . "' isn't part of the column map");
					}

				}

				if (function() { if(isset($this->$attributeField)) {$value = $this->$attributeField; return $value; } else { return false; } }())
				{
					if (!($useDynamicUpdate))
					{
						$fields = $field;
						$values = $value;

						$bindTypes = $bindType;

					}

					$newSnapshot[$attributeField] = $value;

				}

			}
		}

		if (!(count($fields)))
		{
			if ($useDynamicUpdate)
			{
				$this->_oldSnapshot = $snapshot;

			}

			return true;
		}

		$uniqueKey = $this->_uniqueKey;
		$uniqueParams = $this->_uniqueParams;
		$uniqueTypes = $this->_uniqueTypes;

		if (typeof($uniqueParams) <> "array")
		{
			$primaryKeys = $metaData->getPrimaryKeyAttributes($this);

			if (!(count($primaryKeys)))
			{
				throw new Exception("A primary key must be defined in the model in order to perform the operation");
			}

			$uniqueParams = [];

			foreach ($primaryKeys as $field) {
				if (typeof($columnMap) == "array")
				{
					if (!(function() { if(isset($columnMap[$field])) {$attributeField = $columnMap[$field]; return $attributeField; } else { return false; } }()))
					{
						throw new Exception("Column '" . $field . "' isn't part of the column map");
					}

				}
				if (function() { if(isset($this->$attributeField)) {$value = $this->$attributeField; return $value; } else { return false; } }())
				{
					$newSnapshot[$attributeField] = $value;

					$uniqueParams = $value;

				}
			}

		}

		$success = $connection->update($table, $fields, $values, ["conditions" => $uniqueKey, "bind" => $uniqueParams, "bindTypes" => $uniqueTypes], $bindTypes);

		if ($success && $manager->isKeepingSnapshots($this) && globals_get("orm.update_snapshot_on_save"))
		{
			if (typeof($snapshot) == "array")
			{
				$this->_oldSnapshot = $snapshot;

				$this->_snapshot = array_merge($snapshot, $newSnapshot);

			}

		}

		return $success;
	}

	protected function _preSaveRelatedRecords($connection, $related)
	{

		$nesting = false;

		$connection->begin($nesting);

		$className = get_class($this);
		$manager = $this->getModelsManager();

		foreach ($related as $name => $record) {
			$relation = $manager->getRelationByAlias($className, $name);
			if (typeof($relation) == "object")
			{
				$type = $relation->getType();

				if ($type == Relation::BELONGS_TO)
				{
					if (typeof($record) <> "object")
					{
						$connection->rollback($nesting);

						throw new Exception("Only objects can be stored as part of belongs-to relations");
					}

					$columns = $relation->getFields();
					$referencedModel = $relation->getReferencedModel();
					$referencedFields = $relation->getReferencedFields();

					if (typeof($columns) == "array")
					{
						$connection->rollback($nesting);

						throw new Exception("Not implemented");
					}

					if (!($record->save()))
					{
						foreach ($record->getMessages() as $message) {
							if (typeof($message) == "object")
							{
								$message->setModel($record);

							}
							$this->appendMessage($message);
						}

						$connection->rollback($nesting);

						return false;
					}

					$this->{$columns} = $record->readAttribute($referencedFields);

				}

			}
		}

		return true;
	}

	protected function _postSaveRelatedRecords($connection, $related)
	{


		$nesting = false;
		$className = get_class($this);
		$manager = $this->getModelsManager();

		foreach ($related as $name => $record) {
			$relation = $manager->getRelationByAlias($className, $name);
			if (typeof($relation) == "object")
			{
				if ($relation->getType() == Relation::BELONGS_TO)
				{
					continue;

				}

				if (typeof($record) <> "object" && typeof($record) <> "array")
				{
					$connection->rollback($nesting);

					throw new Exception("Only objects/arrays can be stored as part of has-many/has-one/has-many-to-many relations");
				}

				$columns = $relation->getFields();
				$referencedModel = $relation->getReferencedModel();
				$referencedFields = $relation->getReferencedFields();

				if (typeof($columns) == "array")
				{
					$connection->rollback($nesting);

					throw new Exception("Not implemented");
				}

				if (typeof($record) == "object")
				{
					$relatedRecords = [$record];

				}

				if (!(function() { if(isset($this->$columns)) {$value = $this->$columns; return $value; } else { return false; } }()))
				{
					$connection->rollback($nesting);

					throw new Exception("The column '" . $columns . "' needs to be present in the model");
				}

				$isThrough = (bool) $relation->isThrough();

				if ($isThrough)
				{
					$intermediateModelName = $relation->getIntermediateModel();
					$intermediateFields = $relation->getIntermediateFields();
					$intermediateReferencedFields = $relation->getIntermediateReferencedFields();

				}

				foreach ($relatedRecords as $recordAfter) {
					if (!($isThrough))
					{
						$recordAfter->writeAttribute($referencedFields, $value);

					}
					if (!($recordAfter->save()))
					{
						foreach ($recordAfter->getMessages() as $message) {
							if (typeof($message) == "object")
							{
								$message->setModel($record);

							}
							$this->appendMessage($message);
						}

						$connection->rollback($nesting);

						return false;
					}
					if ($isThrough)
					{
						$intermediateModel = $manager->load($intermediateModelName, true);

						$intermediateModel->writeAttribute($intermediateFields, $value);

						$intermediateValue = $recordAfter->readAttribute($referencedFields);

						$intermediateModel->writeAttribute($intermediateReferencedFields, $intermediateValue);

						if (!($intermediateModel->save()))
						{
							foreach ($intermediateModel->getMessages() as $message) {
								if (typeof($message) == "object")
								{
									$message->setModel($record);

								}
								$this->appendMessage($message);
							}

							$connection->rollback($nesting);

							return false;
						}

					}
				}

			}
		}

		$connection->commit($nesting);

		return true;
	}

	public function save($data = null, $whiteList = null)
	{

		$metaData = $this->getModelsMetaData();

		if (typeof($data) == "array" && count($data) > 0)
		{
			$this->assign($data, null, $whiteList);

		}

		$writeConnection = $this->getWriteConnection();

		$this->fireEvent("prepareSave");

		$related = $this->_related;

		if (typeof($related) == "array")
		{
			if ($this->_preSaveRelatedRecords($writeConnection, $related) === false)
			{
				return false;
			}

		}

		$schema = $this->getSchema();
		$source = $this->getSource();

		if ($schema)
		{
			$table = [$schema, $source];

		}

		$readConnection = $this->getReadConnection();

		$exists = $this->_exists($metaData, $readConnection, $table);

		if ($exists)
		{
			$this->_operationMade = self::OP_UPDATE;

		}

		$this->_errorMessages = [];

		$identityField = $metaData->getIdentityField($this);

		if ($this->_preSave($metaData, $exists, $identityField) === false)
		{
			if (typeof($related) == "array")
			{
				$writeConnection->rollback(false);

			}

			if (globals_get("orm.exception_on_failed_save"))
			{
				throw new ValidationFailed($this, $this->getMessages());
			}

			return false;
		}

		if ($exists)
		{
			$success = $this->_doLowUpdate($metaData, $writeConnection, $table);

		}

		if ($success)
		{
			$this->_dirtyState = self::DIRTY_STATE_PERSISTENT;

		}

		if (typeof($related) == "array")
		{
			if ($success === false)
			{
				$writeConnection->rollback(false);

			}

		}

		if (globals_get("orm.events"))
		{
			$success = $this->_postSave($success, $exists);

		}

		if ($success === false)
		{
			$this->_cancelOperation();

		}

		return $success;
	}

	public function create($data = null, $whiteList = null)
	{

		$metaData = $this->getModelsMetaData();

		if ($this->_exists($metaData, $this->getReadConnection()))
		{
			$this->_errorMessages = [new Message("Record cannot be created because it already exists", null, "InvalidCreateAttempt")];

			return false;
		}

		return $this->save($data, $whiteList);
	}

	public function update($data = null, $whiteList = null)
	{

		if ($this->_dirtyState)
		{
			$metaData = $this->getModelsMetaData();

			if (!($this->_exists($metaData, $this->getReadConnection())))
			{
				$this->_errorMessages = [new Message("Record cannot be updated because it does not exist", null, "InvalidUpdateAttempt")];

				return false;
			}

		}

		return $this->save($data, $whiteList);
	}

	public function delete()
	{

		$metaData = $this->getModelsMetaData();
		$writeConnection = $this->getWriteConnection();

		$this->_operationMade = self::OP_DELETE;
		$this->_errorMessages = [];

		if (globals_get("orm.virtual_foreign_keys"))
		{
			if ($this->_checkForeignKeysReverseRestrict() === false)
			{
				return false;
			}

		}

		$values = [];
		$bindTypes = [];
		$conditions = [];

		$primaryKeys = $metaData->getPrimaryKeyAttributes($this);
		$bindDataTypes = $metaData->getBindTypes($this);

		if (globals_get("orm.column_renaming"))
		{
			$columnMap = $metaData->getColumnMap($this);

		}

		if (!(count($primaryKeys)))
		{
			throw new Exception("A primary key must be defined in the model in order to perform the operation");
		}

		foreach ($primaryKeys as $primaryKey) {
			if (!(function() { if(isset($bindDataTypes[$primaryKey])) {$bindType = $bindDataTypes[$primaryKey]; return $bindType; } else { return false; } }()))
			{
				throw new Exception("Column '" . $primaryKey . "' have not defined a bind data type");
			}
			if (typeof($columnMap) == "array")
			{
				if (!(function() { if(isset($columnMap[$primaryKey])) {$attributeField = $columnMap[$primaryKey]; return $attributeField; } else { return false; } }()))
				{
					throw new Exception("Column '" . $primaryKey . "' isn't part of the column map");
				}

			}
			if (!(function() { if(isset($this->$attributeField)) {$value = $this->$attributeField; return $value; } else { return false; } }()))
			{
				throw new Exception("Cannot delete the record because the primary key attribute: '" . $attributeField . "' wasn't set");
			}
			$values = $value;
			$conditions = $writeConnection->escapeIdentifier($primaryKey) . " = ?";
			$bindTypes = $bindType;
		}

		if (globals_get("orm.events"))
		{
			$this->_skipped = false;

			if ($this->fireEventCancel("beforeDelete") === false)
			{
				return false;
			}

		}

		$schema = $this->getSchema();
		$source = $this->getSource();

		if ($schema)
		{
			$table = [$schema, $source];

		}

		$success = $writeConnection->delete($table, join(" AND ", $conditions), $values, $bindTypes);

		if (globals_get("orm.virtual_foreign_keys"))
		{
			if ($this->_checkForeignKeysReverseCascade() === false)
			{
				return false;
			}

		}

		if (globals_get("orm.events"))
		{
			if ($success)
			{
				$this->fireEvent("afterDelete");

			}

		}

		$this->_dirtyState = self::DIRTY_STATE_DETACHED;

		return $success;
	}

	public function getOperationMade()
	{
		return $this->_operationMade;
	}

	public function refresh()
	{

		if ($this->_dirtyState <> self::DIRTY_STATE_PERSISTENT)
		{
			throw new Exception("The record cannot be refreshed because it does not exist or is deleted");
		}

		$metaData = $this->getModelsMetaData();
		$readConnection = $this->getReadConnection();
		$manager = $this->_modelsManager;

		$schema = $this->getSchema();
		$source = $this->getSource();

		if ($schema)
		{
			$table = [$schema, $source];

		}

		$uniqueKey = $this->_uniqueKey;

		if (!($uniqueKey))
		{
			if (!($this->_exists($metaData, $readConnection, $table)))
			{
				throw new Exception("The record cannot be refreshed because it does not exist or is deleted");
			}

			$uniqueKey = $this->_uniqueKey;

		}

		$uniqueParams = $this->_uniqueParams;

		if (typeof($uniqueParams) <> "array")
		{
			throw new Exception("The record cannot be refreshed because it does not exist or is deleted");
		}

		$fields = [];

		foreach ($metaData->getAttributes($this) as $attribute) {
			$fields = [$attribute];
		}

		$dialect = $readConnection->getDialect();
		$tables = $dialect->select(["columns" => $fields, "tables" => $readConnection->escapeIdentifier($table), "where" => $uniqueKey]);
		$row = $readConnection->fetchOne($tables, \Phalcon\Db::FETCH_ASSOC, $uniqueParams, $this->_uniqueTypes);

		if (typeof($row) == "array")
		{
			$columnMap = $metaData->getColumnMap($this);

			$this->assign($row, $columnMap);

			if ($manager->isKeepingSnapshots($this))
			{
				$this->setSnapshotData($row, $columnMap);

				$this->setOldSnapshotData($row, $columnMap);

			}

		}

		$this->fireEvent("afterFetch");

		return $this;
	}

	public function skipOperation($skip)
	{
		$this->_skipped = $skip;

	}

	public function readAttribute($attribute)
	{
		if (!(isset($this->$attribute)))
		{
			return null;
		}

		return $this->$attribute;
	}

	public function writeAttribute($attribute, $value)
	{
		$this->{$attribute} = $value;

	}

	protected function skipAttributes($attributes)
	{
		$this->skipAttributesOnCreate($attributes);

		$this->skipAttributesOnUpdate($attributes);

	}

	protected function skipAttributesOnCreate($attributes)
	{

		$keysAttributes = [];

		foreach ($attributes as $attribute) {
			$keysAttributes[$attribute] = null;
		}

		$this->getModelsMetaData()->setAutomaticCreateAttributes($this, $keysAttributes);

	}

	protected function skipAttributesOnUpdate($attributes)
	{

		$keysAttributes = [];

		foreach ($attributes as $attribute) {
			$keysAttributes[$attribute] = null;
		}

		$this->getModelsMetaData()->setAutomaticUpdateAttributes($this, $keysAttributes);

	}

	protected function allowEmptyStringValues($attributes)
	{

		$keysAttributes = [];

		foreach ($attributes as $attribute) {
			$keysAttributes[$attribute] = true;
		}

		$this->getModelsMetaData()->setEmptyStringAttributes($this, $keysAttributes);

	}

	protected function hasOne($fields, $referenceModel, $referencedFields, $options = null)
	{
		return $this->_modelsManager->addHasOne($this, $fields, $referenceModel, $referencedFields, $options);
	}

	protected function belongsTo($fields, $referenceModel, $referencedFields, $options = null)
	{
		return $this->_modelsManager->addBelongsTo($this, $fields, $referenceModel, $referencedFields, $options);
	}

	protected function hasMany($fields, $referenceModel, $referencedFields, $options = null)
	{
		return $this->_modelsManager->addHasMany($this, $fields, $referenceModel, $referencedFields, $options);
	}

	protected function hasManyToMany($fields, $intermediateModel, $intermediateFields, $intermediateReferencedFields, $referenceModel, $referencedFields, $options = null)
	{
		return $this->_modelsManager->addHasManyToMany($this, $fields, $intermediateModel, $intermediateFields, $intermediateReferencedFields, $referenceModel, $referencedFields, $options);
	}

	public function addBehavior($behavior)
	{
		$this->_modelsManager->addBehavior($this, $behavior);

	}

	protected function keepSnapshots($keepSnapshot)
	{
		$this->_modelsManager->keepSnapshots($this, $keepSnapshot);

	}

	public function setSnapshotData($data, $columnMap = null)
	{

		if (typeof($columnMap) == "array")
		{
			$snapshot = [];

			foreach ($data as $key => $value) {
				if (typeof($key) <> "string")
				{
					continue;

				}
				if (!(function() { if(isset($columnMap[$key])) {$attribute = $columnMap[$key]; return $attribute; } else { return false; } }()))
				{
					if (!(globals_get("orm.ignore_unknown_columns")))
					{
						throw new Exception("Column '" . $key . "' doesn't make part of the column map");
					}

				}
				if (typeof($attribute) == "array")
				{
					if (!(function() { if(isset($attribute[0])) {$attribute = $attribute[0]; return $attribute; } else { return false; } }()))
					{
						if (!(globals_get("orm.ignore_unknown_columns")))
						{
							throw new Exception("Column '" . $key . "' doesn't make part of the column map");
						}

					}

				}
				$snapshot[$attribute] = $value;
			}

		}

		if (typeof($this->_snapshot) == "array")
		{
			$this->_oldSnapshot = $this->_snapshot;

		}

		$this->_snapshot = $snapshot;

	}

	public function setOldSnapshotData($data, $columnMap = null)
	{

		if (typeof($columnMap) == "array")
		{
			$snapshot = [];

			foreach ($data as $key => $value) {
				if (typeof($key) <> "string")
				{
					continue;

				}
				if (!(function() { if(isset($columnMap[$key])) {$attribute = $columnMap[$key]; return $attribute; } else { return false; } }()))
				{
					if (!(globals_get("orm.ignore_unknown_columns")))
					{
						throw new Exception("Column '" . $key . "' doesn't make part of the column map");
					}

				}
				if (typeof($attribute) == "array")
				{
					if (!(function() { if(isset($attribute[0])) {$attribute = $attribute[0]; return $attribute; } else { return false; } }()))
					{
						if (!(globals_get("orm.ignore_unknown_columns")))
						{
							throw new Exception("Column '" . $key . "' doesn't make part of the column map");
						}

					}

				}
				$snapshot[$attribute] = $value;
			}

		}

		$this->_oldSnapshot = $snapshot;

	}

	public function hasSnapshotData()
	{

		$snapshot = $this->_snapshot;

		return typeof($snapshot) == "array";
	}

	public function getSnapshotData()
	{
		return $this->_snapshot;
	}

	public function getOldSnapshotData()
	{
		return $this->_oldSnapshot;
	}

	public function hasChanged($fieldName = null, $allFields = false)
	{

		$changedFields = $this->getChangedFields();

		if (typeof($fieldName) == "string")
		{
			return in_array($fieldName, $changedFields);
		}

		return count($changedFields) > 0;
	}

	public function hasUpdated($fieldName = null, $allFields = false)
	{

		$updatedFields = $this->getUpdatedFields();

		if (typeof($fieldName) == "string")
		{
			return in_array($fieldName, $updatedFields);
		}

		return count($updatedFields) > 0;
	}

	public function getChangedFields()
	{

		$snapshot = $this->_snapshot;

		if (typeof($snapshot) <> "array")
		{
			throw new Exception("The record doesn't have a valid data snapshot");
		}

		$metaData = $this->getModelsMetaData();

		$columnMap = $metaData->getReverseColumnMap($this);

		if (typeof($columnMap) <> "array")
		{
			$allAttributes = $metaData->getDataTypes($this);

		}

		$changed = [];

		foreach ($allAttributes as $name => $_) {
			if (!(isset($snapshot[$name])))
			{
				$changed = $name;

				continue;

			}
			if (!(function() { if(isset($this->$name)) {$value = $this->$name; return $value; } else { return false; } }()))
			{
				$changed = $name;

				continue;

			}
			if ($value !== $snapshot[$name])
			{
				$changed = $name;

				continue;

			}
		}

		return $changed;
	}

	public function getUpdatedFields()
	{

		$snapshot = $this->_snapshot;

		$oldSnapshot = $this->_oldSnapshot;

		if (!(globals_get("orm.update_snapshot_on_save")))
		{
			throw new Exception("Update snapshot on save must be enabled for this method to work properly");
		}

		if (typeof($snapshot) <> "array")
		{
			throw new Exception("The record doesn't have a valid data snapshot");
		}

		if ($this->_dirtyState <> self::DIRTY_STATE_PERSISTENT)
		{
			throw new Exception("Change checking cannot be performed because the object has not been persisted or is deleted");
		}

		$updated = [];

		foreach ($snapshot as $name => $value) {
			if (!(isset($oldSnapshot[$name])))
			{
				$updated = $name;

				continue;

			}
			if ($value !== $oldSnapshot[$name])
			{
				$updated = $name;

				continue;

			}
		}

		return $updated;
	}

	protected function useDynamicUpdate($dynamicUpdate)
	{
		$this->_modelsManager->useDynamicUpdate($this, $dynamicUpdate);

	}

	public function getRelated($alias, $arguments = null)
	{

		$className = get_class($this);
		$manager = $this->_modelsManager;
		$relation = $manager->getRelationByAlias($className, $alias);

		if (typeof($relation) <> "object")
		{
			throw new Exception("There is no defined relations for the model '" . $className . "' using alias '" . $alias . "'");
		}

		return $manager->getRelationRecords($relation, null, $this, $arguments);
	}

	protected function _getRelatedRecords($modelName, $method, $arguments)
	{

		$manager = $this->_modelsManager;

		$relation = false;
		$queryMethod = null;

		if (starts_with($method, "get"))
		{
			$relation = $manager->getRelationByAlias($modelName, substr($method, 3));

		}

		if (typeof($relation) <> "object")
		{
			return null;
		}

		$extraArgs = $arguments[0]
		return $manager->getRelationRecords($relation, $queryMethod, $this, $extraArgs);
	}

	protected final static function _invokeFinder($method, $arguments)
	{

		$extraMethod = null;

		if (starts_with($method, "findFirstBy"))
		{
			$type = "findFirst";
			$extraMethod = substr($method, 11);

		}

		$modelName = get_called_class();

		if (!($extraMethod))
		{
			return null;
		}

		if (!(function() { if(isset($arguments[0])) {$value = $arguments[0]; return $value; } else { return false; } }()))
		{
			throw new Exception("The static method '" . $method . "' requires one argument");
		}

		$model = new $modelName();
		$metaData = $model->getModelsMetaData();

		$attributes = $metaData->getReverseColumnMap($model);

		if (typeof($attributes) <> "array")
		{
			$attributes = $metaData->getDataTypes($model);

		}

		if (isset($attributes[$extraMethod]))
		{
			$field = $extraMethod;

		}

		return $modelName->type(["conditions" => "[" . $field . "] = ?0", "bind" => [$value]]);
	}

	public function __call($method, $arguments)
	{

		$records = self::_invokeFinder($method, $arguments);

		if ($records !== null)
		{
			return $records;
		}

		$modelName = get_class($this);

		$records = $this->_getRelatedRecords($modelName, $method, $arguments);

		if ($records !== null)
		{
			return $records;
		}

		$status = $this->_modelsManager->missingMethod($this, $method, $arguments);

		if ($status !== null)
		{
			return $status;
		}

		throw new Exception("The method '" . $method . "' doesn't exist on model '" . $modelName . "'");
	}

	public static function __callStatic($method, $arguments)
	{

		$records = self::_invokeFinder($method, $arguments);

		if ($records === null)
		{
			throw new Exception("The static method '" . $method . "' doesn't exist");
		}

		return $records;
	}

	public function __set($property, $value)
	{

		if (typeof($value) == "object")
		{
			if ($value instanceof $ModelInterface)
			{
				$dirtyState = $this->_dirtyState;

				if ($value->getDirtyState() <> $dirtyState)
				{
					$dirtyState = self::DIRTY_STATE_TRANSIENT;

				}

				$lowerProperty = strtolower($property);
				$this->{$lowerProperty} = $value;
				$this[$lowerProperty] = $value;
				$this->_dirtyState = $dirtyState;

				return $value;
			}

		}

		if (typeof($value) == "array")
		{
			$lowerProperty = strtolower($property);
			$modelName = get_class($this);
			$manager = $this->getModelsManager();

			$related = [];

			foreach ($value as $key => $item) {
				if (typeof($item) == "object")
				{
					if ($item instanceof $ModelInterface)
					{
						$related = $item;

					}

				}
			}

			if (count($related) > 0)
			{
				$this[$lowerProperty] = $related;
				$this->_dirtyState = self::DIRTY_STATE_TRANSIENT;

			}

			return $value;
		}

		if ($this->_possibleSetter($property, $value))
		{
			return $value;
		}

		if (property_exists($this, $property))
		{
			$manager = $this->getModelsManager();

			if (!($manager->isVisibleModelProperty($this, $property)))
			{
				throw new Exception("Property '" . $property . "' does not have a setter.");
			}

		}

		$this->{$property} = $value;

		return $value;
	}

	protected final function _possibleSetter($property, $value)
	{

		$possibleSetter = "set" . camelize($property);

		if (method_exists($this, $possibleSetter))
		{
			$this->possibleSetter($value);

			return true;
		}

		return false;
	}

	public function __get($property)
	{

		$modelName = get_class($this);
		$manager = $this->getModelsManager();
		$lowerProperty = strtolower($property);

		$relation = $manager->getRelationByAlias($modelName, $lowerProperty);

		if (typeof($relation) == "object")
		{
			if (isset($this->$lowerProperty) && typeof($this->$lowerProperty) == "object")
			{
				return $this->$lowerProperty;
			}

			$result = $manager->getRelationRecords($relation, null, $this, null);

			if (typeof($result) == "object")
			{
				$this->{$lowerProperty} = $result;

				if ($result instanceof $ModelInterface)
				{
					$this[$lowerProperty] = $result;

				}

			}

			return $result;
		}

		$method = "get" . camelize($property);

		if (method_exists($this, $method))
		{
			return $this->method();
		}

		trigger_error("Access to undefined property " . $modelName . "::" . $property);

		return null;
	}

	public function __isset($property)
	{

		$modelName = get_class($this);
		$manager = $this->getModelsManager();

		$relation = $manager->getRelationByAlias($modelName, $property);

		return typeof($relation) == "object";
	}

	public function serialize()
	{

		$attributes = $this->toArray();
		$manager = $this->getModelsManager();

		if ($manager->isKeepingSnapshots($this))
		{
			$snapshot = $this->_snapshot;

			if ($snapshot <> null && $attributes <> $snapshot)
			{
				return serialize(["_attributes" => $attributes, "_snapshot" => $snapshot]);
			}

		}

		return serialize($attributes);
	}

	public function unserialize($data)
	{

		$attributes = unserialize($data);

		if (typeof($attributes) == "array")
		{
			$dependencyInjector = Di::getDefault();

			if (typeof($dependencyInjector) <> "object")
			{
				throw new Exception("A dependency injector container is required to obtain the services related to the ORM");
			}

			$this->_dependencyInjector = $dependencyInjector;

			$manager = $dependencyInjector->getShared("modelsManager");

			if (typeof($manager) <> "object")
			{
				throw new Exception("The injected service 'modelsManager' is not valid");
			}

			$this->_modelsManager = $manager;

			$manager->initialize($this);

			if ($manager->isKeepingSnapshots($this))
			{
				if (function() { if(isset($attributes["_snapshot"])) {$snapshot = $attributes["_snapshot"]; return $snapshot; } else { return false; } }())
				{
					$this->_snapshot = $snapshot;

					$attributes = $attributes["_attributes"];

				}

			}

			foreach ($attributes as $key => $value) {
				$this->{$key} = $value;
			}

		}

	}

	public function dump()
	{
		return get_object_vars($this);
	}

	public function toArray($columns = null)
	{

		$data = [];
		$metaData = $this->getModelsMetaData();
		$columnMap = $metaData->getColumnMap($this);

		foreach ($metaData->getAttributes($this) as $attribute) {
			if (typeof($columnMap) == "array")
			{
				if (!(function() { if(isset($columnMap[$attribute])) {$attributeField = $columnMap[$attribute]; return $attributeField; } else { return false; } }()))
				{
					if (!(globals_get("orm.ignore_unknown_columns")))
					{
						throw new Exception("Column '" . $attribute . "' doesn't make part of the column map");
					}

				}

			}
			if (typeof($columns) == "array")
			{
				if (!(in_array($attributeField, $columns)))
				{
					continue;

				}

			}
			if (function() { if(isset($this->$attributeField)) {$value = $this->$attributeField; return $value; } else { return false; } }())
			{
				$data[$attributeField] = $value;

			}
		}

		return $data;
	}

	public function jsonSerialize()
	{
		return $this->toArray();
	}

	public static function setup($options)
	{

		if (function() { if(isset($options["events"])) {$disableEvents = $options["events"]; return $disableEvents; } else { return false; } }())
		{
			globals_set("orm.events", $disableEvents);

		}

		if (function() { if(isset($options["virtualForeignKeys"])) {$virtualForeignKeys = $options["virtualForeignKeys"]; return $virtualForeignKeys; } else { return false; } }())
		{
			globals_set("orm.virtual_foreign_keys", $virtualForeignKeys);

		}

		if (function() { if(isset($options["columnRenaming"])) {$columnRenaming = $options["columnRenaming"]; return $columnRenaming; } else { return false; } }())
		{
			globals_set("orm.column_renaming", $columnRenaming);

		}

		if (function() { if(isset($options["notNullValidations"])) {$notNullValidations = $options["notNullValidations"]; return $notNullValidations; } else { return false; } }())
		{
			globals_set("orm.not_null_validations", $notNullValidations);

		}

		if (function() { if(isset($options["exceptionOnFailedSave"])) {$exceptionOnFailedSave = $options["exceptionOnFailedSave"]; return $exceptionOnFailedSave; } else { return false; } }())
		{
			globals_set("orm.exception_on_failed_save", $exceptionOnFailedSave);

		}

		if (function() { if(isset($options["phqlLiterals"])) {$phqlLiterals = $options["phqlLiterals"]; return $phqlLiterals; } else { return false; } }())
		{
			globals_set("orm.enable_literals", $phqlLiterals);

		}

		if (function() { if(isset($options["lateStateBinding"])) {$lateStateBinding = $options["lateStateBinding"]; return $lateStateBinding; } else { return false; } }())
		{
			globals_set("orm.late_state_binding", $lateStateBinding);

		}

		if (function() { if(isset($options["castOnHydrate"])) {$castOnHydrate = $options["castOnHydrate"]; return $castOnHydrate; } else { return false; } }())
		{
			globals_set("orm.cast_on_hydrate", $castOnHydrate);

		}

		if (function() { if(isset($options["ignoreUnknownColumns"])) {$ignoreUnknownColumns = $options["ignoreUnknownColumns"]; return $ignoreUnknownColumns; } else { return false; } }())
		{
			globals_set("orm.ignore_unknown_columns", $ignoreUnknownColumns);

		}

		if (function() { if(isset($options["updateSnapshotOnSave"])) {$updateSnapshotOnSave = $options["updateSnapshotOnSave"]; return $updateSnapshotOnSave; } else { return false; } }())
		{
			globals_set("orm.update_snapshot_on_save", $updateSnapshotOnSave);

		}

		if (function() { if(isset($options["disableAssignSetters"])) {$disableAssignSetters = $options["disableAssignSetters"]; return $disableAssignSetters; } else { return false; } }())
		{
			globals_set("orm.disable_assign_setters", $disableAssignSetters);

		}

	}

	public function reset()
	{
		$this->_uniqueParams = null;

		$this->_snapshot = null;

	}


}