<?php
namespace Phalcon\Mvc\Model;

use Phalcon\DiInterface;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Mvc\Model\RelationInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Db\AdapterInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\ManagerInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\Model\QueryInterface;
use Phalcon\Mvc\Model\Query\Builder;
use Phalcon\Mvc\Model\Query\BuilderInterface;
use Phalcon\Mvc\Model\BehaviorInterface;
use Phalcon\Events\ManagerInterface as EventsManagerInterface;

class Manager implements ManagerInterface, InjectionAwareInterface, EventsAwareInterface
{
	protected $_dependencyInjector;
	protected $_eventsManager;
	protected $_customEventsManager;
	protected $_readConnectionServices;
	protected $_writeConnectionServices;
	protected $_aliases;
	protected $_modelVisibility = [];
	protected $_hasMany;
	protected $_hasManySingle;
	protected $_hasOne;
	protected $_hasOneSingle;
	protected $_belongsTo;
	protected $_belongsToSingle;
	protected $_hasManyToMany;
	protected $_hasManyToManySingle;
	protected $_initialized;
	protected $_prefix = "";
	protected $_sources;
	protected $_schemas;
	protected $_behaviors;
	protected $_lastInitialized;
	protected $_lastQuery;
	protected $_reusable;
	protected $_keepSnapshots;
	protected $_dynamicUpdate;
	protected $_namespaceAliases;

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	public function setEventsManager($eventsManager)
	{
		$this->_eventsManager = $eventsManager;

		return $this;
	}

	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	public function setCustomEventsManager($model, $eventsManager)
	{
		$this[get_class_lower($model)] = $eventsManager;

	}

	public function getCustomEventsManager($model)
	{

		if (!(function() { if(isset($this->_customEventsManager[get_class_lower($model)])) {$eventsManager = $this->_customEventsManager[get_class_lower($model)]; return $eventsManager; } else { return false; } }()))
		{
			return false;
		}

		return $eventsManager;
	}

	public function initialize($model)
	{

		$className = get_class_lower($model);

		if (isset($this->_initialized[$className]))
		{
			return false;
		}

		$this[$className] = $model;

		if (method_exists($model, "initialize"))
		{
			$model->initialize();

		}

		$this->_lastInitialized = $model;

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("modelsManager:afterInitialize", $this, $model);

		}

		return true;
	}

	public function isInitialized($modelName)
	{
		return isset($this->_initialized[strtolower($modelName)]);
	}

	public function getLastInitialized()
	{
		return $this->_lastInitialized;
	}

	public function load($modelName, $newInstance = false)
	{

		$colonPos = strpos($modelName, ":");

		if ($colonPos !== false)
		{
			$className = substr($modelName, $colonPos + 1);

			$namespaceAlias = substr($modelName, 0, $colonPos);

			$namespaceName = $this->getNamespaceAlias($namespaceAlias);

			$modelName = $namespaceName . "\\" . $className;

		}

		if (!(class_exists($modelName)))
		{
			throw new Exception("Model '" . $modelName . "' could not be loaded");
		}

		if (!($newInstance))
		{
			if (function() { if(isset($this->_initialized[strtolower($modelName)])) {$model = $this->_initialized[strtolower($modelName)]; return $model; } else { return false; } }())
			{
				$model->reset();

				return $model;
			}

		}

		return new $modelName(null, $this->_dependencyInjector, $this);
	}

	public function setModelPrefix($prefix)
	{
		$this->_prefix = $prefix;

	}

	public function getModelPrefix()
	{
		return $this->_prefix;
	}

	public function setModelSource($model, $source)
	{
		$this[get_class_lower($model)] = $source;

	}

	public final function isVisibleModelProperty($model, $property)
	{

		$className = get_class($model);

		if (!(isset($this->_modelVisibility[$className])))
		{
			$this[$className] = get_object_vars($model);

		}

		$properties = $this->_modelVisibility[$className];

		return array_key_exists($property, $properties);
	}

	public function getModelSource($model)
	{

		$entityName = get_class_lower($model);

		if (!(isset($this->_sources[$entityName])))
		{
			$this[$entityName] = uncamelize(get_class_ns($model));

		}

		return $this->_prefix . $this->_sources[$entityName];
	}

	public function setModelSchema($model, $schema)
	{
		$this[get_class_lower($model)] = $schema;

	}

	public function getModelSchema($model)
	{

		if (!(function() { if(isset($this->_schemas[get_class_lower($model)])) {$schema = $this->_schemas[get_class_lower($model)]; return $schema; } else { return false; } }()))
		{
			return "";
		}

		return $schema;
	}

	public function setConnectionService($model, $connectionService)
	{
		$this->setReadConnectionService($model, $connectionService);

		$this->setWriteConnectionService($model, $connectionService);

	}

	public function setWriteConnectionService($model, $connectionService)
	{
		$this[get_class_lower($model)] = $connectionService;

	}

	public function setReadConnectionService($model, $connectionService)
	{
		$this[get_class_lower($model)] = $connectionService;

	}

	public function getReadConnection($model)
	{
		return $this->_getConnection($model, $this->_readConnectionServices);
	}

	public function getWriteConnection($model)
	{
		return $this->_getConnection($model, $this->_writeConnectionServices);
	}

	protected function _getConnection($model, $connectionServices)
	{

		$service = $this->_getConnectionService($model, $connectionServices);

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injector container is required to obtain the services related to the ORM");
		}

		$connection = $dependencyInjector->getShared($service);

		if (typeof($connection) <> "object")
		{
			throw new Exception("Invalid injected connection service");
		}

		return $connection;
	}

	public function getReadConnectionService($model)
	{
		return $this->_getConnectionService($model, $this->_readConnectionServices);
	}

	public function getWriteConnectionService($model)
	{
		return $this->_getConnectionService($model, $this->_writeConnectionServices);
	}

	public function _getConnectionService($model, $connectionServices)
	{

		if (!(function() { if(isset($connectionServices[get_class_lower($model)])) {$connection = $connectionServices[get_class_lower($model)]; return $connection; } else { return false; } }()))
		{
			return "db";
		}

		return $connection;
	}

	public function notifyEvent($eventName, $model)
	{

		$status = null;

		if (function() { if(isset($this->_behaviors[get_class_lower($model)])) {$modelsBehaviors = $this->_behaviors[get_class_lower($model)]; return $modelsBehaviors; } else { return false; } }())
		{
			foreach ($modelsBehaviors as $behavior) {
				$status = $behavior->notify($eventName, $model);
				if ($status === false)
				{
					return false;
				}
			}

		}

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			$status = $eventsManager->fire("model:" . $eventName, $model);

			if ($status === false)
			{
				return $status;
			}

		}

		if (function() { if(isset($this->_customEventsManager[get_class_lower($model)])) {$customEventsManager = $this->_customEventsManager[get_class_lower($model)]; return $customEventsManager; } else { return false; } }())
		{
			$status = $customEventsManager->fire("model:" . $eventName, $model);

			if ($status === false)
			{
				return false;
			}

		}

		return $status;
	}

	public function missingMethod($model, $eventName, $data)
	{

		if (function() { if(isset($this->_behaviors[get_class_lower($model)])) {$modelsBehaviors = $this->_behaviors[get_class_lower($model)]; return $modelsBehaviors; } else { return false; } }())
		{
			foreach ($modelsBehaviors as $behavior) {
				$result = $behavior->missingMethod($model, $eventName, $data);
				if ($result !== null)
				{
					return $result;
				}
			}

		}

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			return $eventsManager->fire("model:" . $eventName, $model, $data);
		}

		return null;
	}

	public function addBehavior($model, $behavior)
	{

		$entityName = get_class_lower($model);

		if (!(function() { if(isset($this->_behaviors[$entityName])) {$modelsBehaviors = $this->_behaviors[$entityName]; return $modelsBehaviors; } else { return false; } }()))
		{
			$modelsBehaviors = [];

		}

		$modelsBehaviors = $behavior;

		$this[$entityName] = $modelsBehaviors;

	}

	public function keepSnapshots($model, $keepSnapshots)
	{
		$this[get_class_lower($model)] = $keepSnapshots;

	}

	public function isKeepingSnapshots($model)
	{

		$keepSnapshots = $this->_keepSnapshots;

		if (typeof($keepSnapshots) == "array")
		{
			if (function() { if(isset($keepSnapshots[get_class_lower($model)])) {$isKeeping = $keepSnapshots[get_class_lower($model)]; return $isKeeping; } else { return false; } }())
			{
				return $isKeeping;
			}

		}

		return false;
	}

	public function useDynamicUpdate($model, $dynamicUpdate)
	{

		$entityName = get_class_lower($model);
		$this[$entityName] = $dynamicUpdate;
		$this[$entityName] = $dynamicUpdate;

	}

	public function isUsingDynamicUpdate($model)
	{

		$dynamicUpdate = $this->_dynamicUpdate;

		if (typeof($dynamicUpdate) == "array")
		{
			if (function() { if(isset($dynamicUpdate[get_class_lower($model)])) {$isUsing = $dynamicUpdate[get_class_lower($model)]; return $isUsing; } else { return false; } }())
			{
				return $isUsing;
			}

		}

		return false;
	}

	public function addHasOne($model, $fields, $referencedModel, $referencedFields, $options = null)
	{

		$entityName = get_class_lower($model);
		$referencedEntity = strtolower($referencedModel);

		$keyRelation = $entityName . "$" . $referencedEntity;

		if (!(function() { if(isset($this->_hasOne[$keyRelation])) {$relations = $this->_hasOne[$keyRelation]; return $relations; } else { return false; } }()))
		{
			$relations = [];

		}

		if (typeof($referencedFields) == "array")
		{
			if (count($fields) <> count($referencedFields))
			{
				throw new Exception("Number of referenced fields are not the same");
			}

		}

		$relation = new Relation(Relation::HAS_ONE, $referencedModel, $fields, $referencedFields, $options);

		if (function() { if(isset($options["alias"])) {$alias = $options["alias"]; return $alias; } else { return false; } }())
		{
			if (typeof($alias) <> "string")
			{
				throw new Exception("Relation alias must be a string");
			}

			$lowerAlias = strtolower($alias);

		}

		$relations = $relation;
		$this[$entityName . "$" . $lowerAlias] = $relation;
		$this[$keyRelation] = $relations;

		if (!(function() { if(isset($this->_hasOneSingle[$entityName])) {$singleRelations = $this->_hasOneSingle[$entityName]; return $singleRelations; } else { return false; } }()))
		{
			$singleRelations = [];

		}

		$singleRelations = $relation;

		$this[$entityName] = $singleRelations;

		return $relation;
	}

	public function addBelongsTo($model, $fields, $referencedModel, $referencedFields, $options = null)
	{

		$entityName = get_class_lower($model);
		$referencedEntity = strtolower($referencedModel);

		$keyRelation = $entityName . "$" . $referencedEntity;

		if (!(function() { if(isset($this->_belongsTo[$keyRelation])) {$relations = $this->_belongsTo[$keyRelation]; return $relations; } else { return false; } }()))
		{
			$relations = [];

		}

		if (typeof($referencedFields) == "array")
		{
			if (count($fields) <> count($referencedFields))
			{
				throw new Exception("Number of referenced fields are not the same");
			}

		}

		$relation = new Relation(Relation::BELONGS_TO, $referencedModel, $fields, $referencedFields, $options);

		if (function() { if(isset($options["alias"])) {$alias = $options["alias"]; return $alias; } else { return false; } }())
		{
			if (typeof($alias) <> "string")
			{
				throw new Exception("Relation alias must be a string");
			}

			$lowerAlias = strtolower($alias);

		}

		$relations = $relation;
		$this[$entityName . "$" . $lowerAlias] = $relation;
		$this[$keyRelation] = $relations;

		if (!(function() { if(isset($this->_belongsToSingle[$entityName])) {$singleRelations = $this->_belongsToSingle[$entityName]; return $singleRelations; } else { return false; } }()))
		{
			$singleRelations = [];

		}

		$singleRelations = $relation;

		$this[$entityName] = $singleRelations;

		return $relation;
	}

	public function addHasMany($model, $fields, $referencedModel, $referencedFields, $options = null)
	{

		$entityName = get_class_lower($model);
		$referencedEntity = strtolower($referencedModel);
		$keyRelation = $entityName . "$" . $referencedEntity;

		$hasMany = $this->_hasMany;

		if (!(function() { if(isset($hasMany[$keyRelation])) {$relations = $hasMany[$keyRelation]; return $relations; } else { return false; } }()))
		{
			$relations = [];

		}

		if (typeof($referencedFields) == "array")
		{
			if (count($fields) <> count($referencedFields))
			{
				throw new Exception("Number of referenced fields are not the same");
			}

		}

		$relation = new Relation(Relation::HAS_MANY, $referencedModel, $fields, $referencedFields, $options);

		if (function() { if(isset($options["alias"])) {$alias = $options["alias"]; return $alias; } else { return false; } }())
		{
			if (typeof($alias) <> "string")
			{
				throw new Exception("Relation alias must be a string");
			}

			$lowerAlias = strtolower($alias);

		}

		$relations = $relation;
		$this[$entityName . "$" . $lowerAlias] = $relation;
		$this[$keyRelation] = $relations;

		if (!(function() { if(isset($this->_hasManySingle[$entityName])) {$singleRelations = $this->_hasManySingle[$entityName]; return $singleRelations; } else { return false; } }()))
		{
			$singleRelations = [];

		}

		$singleRelations = $relation;

		$this[$entityName] = $singleRelations;

		return $relation;
	}

	public function addHasManyToMany($model, $fields, $intermediateModel, $intermediateFields, $intermediateReferencedFields, $referencedModel, $referencedFields, $options = null)
	{

		$entityName = get_class_lower($model);
		$intermediateEntity = strtolower($intermediateModel);
		$referencedEntity = strtolower($referencedModel);
		$keyRelation = $entityName . "$" . $referencedEntity;

		$hasManyToMany = $this->_hasManyToMany;

		if (!(function() { if(isset($hasManyToMany[$keyRelation])) {$relations = $hasManyToMany[$keyRelation]; return $relations; } else { return false; } }()))
		{
			$relations = [];

		}

		if (typeof($intermediateFields) == "array")
		{
			if (count($fields) <> count($intermediateFields))
			{
				throw new Exception("Number of referenced fields are not the same");
			}

		}

		if (typeof($intermediateReferencedFields) == "array")
		{
			if (count($fields) <> count($intermediateFields))
			{
				throw new Exception("Number of referenced fields are not the same");
			}

		}

		$relation = new Relation(Relation::HAS_MANY_THROUGH, $referencedModel, $fields, $referencedFields, $options);

		$relation->setIntermediateRelation($intermediateFields, $intermediateModel, $intermediateReferencedFields);

		if (function() { if(isset($options["alias"])) {$alias = $options["alias"]; return $alias; } else { return false; } }())
		{
			if (typeof($alias) <> "string")
			{
				throw new Exception("Relation alias must be a string");
			}

			$lowerAlias = strtolower($alias);

		}

		$relations = $relation;

		$this[$entityName . "$" . $lowerAlias] = $relation;

		$this[$keyRelation] = $relations;

		if (!(function() { if(isset($this->_hasManyToManySingle[$entityName])) {$singleRelations = $this->_hasManyToManySingle[$entityName]; return $singleRelations; } else { return false; } }()))
		{
			$singleRelations = [];

		}

		$singleRelations = $relation;

		$this[$entityName] = $singleRelations;

		return $relation;
	}

	public function existsBelongsTo($modelName, $modelRelation)
	{

		$entityName = strtolower($modelName);

		$keyRelation = $entityName . "$" . strtolower($modelRelation);

		if (!(isset($this->_initialized[$entityName])))
		{
			$this->load($modelName);

		}

		return isset($this->_belongsTo[$keyRelation]);
	}

	public function existsHasMany($modelName, $modelRelation)
	{

		$entityName = strtolower($modelName);

		$keyRelation = $entityName . "$" . strtolower($modelRelation);

		if (!(isset($this->_initialized[$entityName])))
		{
			$this->load($modelName);

		}

		return isset($this->_hasMany[$keyRelation]);
	}

	public function existsHasOne($modelName, $modelRelation)
	{

		$entityName = strtolower($modelName);

		$keyRelation = $entityName . "$" . strtolower($modelRelation);

		if (!(isset($this->_initialized[$entityName])))
		{
			$this->load($modelName);

		}

		return isset($this->_hasOne[$keyRelation]);
	}

	public function existsHasManyToMany($modelName, $modelRelation)
	{

		$entityName = strtolower($modelName);

		$keyRelation = $entityName . "$" . strtolower($modelRelation);

		if (!(isset($this->_initialized[$entityName])))
		{
			$this->load($modelName);

		}

		return isset($this->_hasManyToMany[$keyRelation]);
	}

	public function getRelationByAlias($modelName, $alias)
	{

		if (!(function() { if(isset($this->_aliases[strtolower($modelName . "$" . $alias)])) {$relation = $this->_aliases[strtolower($modelName . "$" . $alias)]; return $relation; } else { return false; } }()))
		{
			return false;
		}

		return $relation;
	}

	protected final function _mergeFindParameters($findParamsOne, $findParamsTwo)
	{

		if (typeof($findParamsOne) == "string" && typeof($findParamsTwo) == "string")
		{
			return ["(" . $findParamsOne . ") AND (" . $findParamsTwo . ")"];
		}

		$findParams = [];

		if (typeof($findParamsOne) == "array")
		{
			foreach ($findParamsOne as $key => $value) {
				if ($key === 0 || $key === "conditions")
				{
					if (!(isset($findParams[0])))
					{
						$findParams[0] = $value;

					}

					continue;

				}
				$findParams[$key] = $value;
			}

		}

		if (typeof($findParamsTwo) == "array")
		{
			foreach ($findParamsTwo as $key => $value) {
				if ($key === 0 || $key === "conditions")
				{
					if (!(isset($findParams[0])))
					{
						$findParams[0] = $value;

					}

					continue;

				}
				if ($key === "bind" || $key === "bindTypes")
				{
					if (!(isset($findParams[$key])))
					{
						if (typeof($value) == "array")
						{
							$findParams[$key] = $value;

						}

					}

					continue;

				}
				$findParams[$key] = $value;
			}

		}

		return $findParams;
	}

	public function getRelationRecords($relation, $method, $record, $parameters = null)
	{


		$placeholders = [];

		$extraParameters = $relation->getParams();

		$referencedModel = $relation->getReferencedModel();

		if ($relation->isThrough())
		{
			$conditions = [];

			$intermediateModel = $relation->getIntermediateModel();
			$intermediateFields = $relation->getIntermediateFields();

			$fields = $relation->getFields();

			if (typeof($fields) <> "array")
			{
				$conditions = "[" . $intermediateModel . "].[" . $intermediateFields . "] = :APR0:";
				$placeholders["APR0"] = $record->readAttribute($fields);

			}

			$joinConditions = [];

			$intermediateFields = $relation->getIntermediateReferencedFields();

			if (typeof($intermediateFields) <> "array")
			{
				$joinConditions = "[" . $intermediateModel . "].[" . $intermediateFields . "] = [" . $referencedModel . "].[" . $relation->getReferencedFields() . "]";

			}

			$builder = $this->createBuilder($this->_mergeFindParameters($extraParameters, $parameters));

			$builder->from($referencedModel);

			$builder->innerJoin($intermediateModel, join(" AND ", $joinConditions));

			$builder->andWhere(join(" AND ", $conditions), $placeholders);

			if ($method == "count")
			{
				$builder->columns("COUNT(*) AS rowcount");

				$rows = $builder->getQuery()->execute();

				$firstRow = $rows->getFirst();

				return (int) $firstRow->readAttribute("rowcount");
			}

			return $builder->getQuery()->execute();
		}

		$conditions = [];

		$fields = $relation->getFields();

		if (typeof($fields) <> "array")
		{
			$conditions = "[" . $relation->getReferencedFields() . "] = :APR0:";
			$placeholders["APR0"] = $record->readAttribute($fields);

		}

		$findParams = [join(" AND ", $conditions), "bind" => $placeholders, "di" => $record->getDi()];

		$findArguments = $this->_mergeFindParameters($findParams, $parameters);

		if (typeof($extraParameters) == "array")
		{
			$findParams = $this->_mergeFindParameters($extraParameters, $findArguments);

		}

		if ($method === null)
		{
			switch ($relation->getType()) {
				case Relation::BELONGS_TO:

				case Relation::HAS_ONE:
					$retrieveMethod = "findFirst";
					break;

				case Relation::HAS_MANY:
					$retrieveMethod = "find";
					break;

				default:
					throw new Exception("Unknown relation type");

			}

		}

		$arguments = [$findParams];

		$reusable = (bool) $relation->isReusable();

		if ($reusable)
		{
			$uniqueKey = unique_key($referencedModel, $arguments);
			$records = $this->getReusableRecords($referencedModel, $uniqueKey);

			if (typeof($records) == "array" || typeof($records) == "object")
			{
				return $records;
			}

		}

		$records = call_user_func_array([$this->load($referencedModel), $retrieveMethod], $arguments);

		if ($reusable)
		{
			$this->setReusableRecords($referencedModel, $uniqueKey, $records);

		}

		return $records;
	}

	public function getReusableRecords($modelName, $key)
	{

		if (function() { if(isset($this->_reusable[$key])) {$records = $this->_reusable[$key]; return $records; } else { return false; } }())
		{
			return $records;
		}

		return null;
	}

	public function setReusableRecords($modelName, $key, $records)
	{
		$this[$key] = $records;

	}

	public function clearReusableObjects()
	{
		$this->_reusable = null;

	}

	public function getBelongsToRecords($method, $modelName, $modelRelation, $record, $parameters = null)
	{

		$keyRelation = strtolower($modelName) . "$" . strtolower($modelRelation);

		if (!(function() { if(isset($this->_hasMany[$keyRelation])) {$relations = $this->_hasMany[$keyRelation]; return $relations; } else { return false; } }()))
		{
			return false;
		}

		return $this->getRelationRecords($relations[0], $method, $record, $parameters);
	}

	public function getHasManyRecords($method, $modelName, $modelRelation, $record, $parameters = null)
	{

		$keyRelation = strtolower($modelName) . "$" . strtolower($modelRelation);

		if (!(function() { if(isset($this->_hasMany[$keyRelation])) {$relations = $this->_hasMany[$keyRelation]; return $relations; } else { return false; } }()))
		{
			return false;
		}

		return $this->getRelationRecords($relations[0], $method, $record, $parameters);
	}

	public function getHasOneRecords($method, $modelName, $modelRelation, $record, $parameters = null)
	{

		$keyRelation = strtolower($modelName) . "$" . strtolower($modelRelation);

		if (!(function() { if(isset($this->_hasOne[$keyRelation])) {$relations = $this->_hasOne[$keyRelation]; return $relations; } else { return false; } }()))
		{
			return false;
		}

		return $this->getRelationRecords($relations[0], $method, $record, $parameters);
	}

	public function getBelongsTo($model)
	{

		if (!(function() { if(isset($this->_belongsToSingle[get_class_lower($model)])) {$relations = $this->_belongsToSingle[get_class_lower($model)]; return $relations; } else { return false; } }()))
		{
			return [];
		}

		return $relations;
	}

	public function getHasMany($model)
	{

		if (!(function() { if(isset($this->_hasManySingle[get_class_lower($model)])) {$relations = $this->_hasManySingle[get_class_lower($model)]; return $relations; } else { return false; } }()))
		{
			return [];
		}

		return $relations;
	}

	public function getHasOne($model)
	{

		if (!(function() { if(isset($this->_hasOneSingle[get_class_lower($model)])) {$relations = $this->_hasOneSingle[get_class_lower($model)]; return $relations; } else { return false; } }()))
		{
			return [];
		}

		return $relations;
	}

	public function getHasManyToMany($model)
	{

		if (!(function() { if(isset($this->_hasManyToManySingle[get_class_lower($model)])) {$relations = $this->_hasManyToManySingle[get_class_lower($model)]; return $relations; } else { return false; } }()))
		{
			return [];
		}

		return $relations;
	}

	public function getHasOneAndHasMany($model)
	{
		return array_merge($this->getHasOne($model), $this->getHasMany($model));
	}

	public function getRelations($modelName)
	{

		$entityName = strtolower($modelName);
		$allRelations = [];

		if (function() { if(isset($this->_belongsToSingle[$entityName])) {$relations = $this->_belongsToSingle[$entityName]; return $relations; } else { return false; } }())
		{
			foreach ($relations as $relation) {
				$allRelations = $relation;
			}

		}

		if (function() { if(isset($this->_hasManySingle[$entityName])) {$relations = $this->_hasManySingle[$entityName]; return $relations; } else { return false; } }())
		{
			foreach ($relations as $relation) {
				$allRelations = $relation;
			}

		}

		if (function() { if(isset($this->_hasOneSingle[$entityName])) {$relations = $this->_hasOneSingle[$entityName]; return $relations; } else { return false; } }())
		{
			foreach ($relations as $relation) {
				$allRelations = $relation;
			}

		}

		return $allRelations;
	}

	public function getRelationsBetween($first, $second)
	{

		$keyRelation = strtolower($first) . "$" . strtolower($second);

		if (function() { if(isset($this->_belongsTo[$keyRelation])) {$relations = $this->_belongsTo[$keyRelation]; return $relations; } else { return false; } }())
		{
			return $relations;
		}

		if (function() { if(isset($this->_hasMany[$keyRelation])) {$relations = $this->_hasMany[$keyRelation]; return $relations; } else { return false; } }())
		{
			return $relations;
		}

		if (function() { if(isset($this->_hasOne[$keyRelation])) {$relations = $this->_hasOne[$keyRelation]; return $relations; } else { return false; } }())
		{
			return $relations;
		}

		return false;
	}

	public function createQuery($phql)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection object is required to access ORM services");
		}

		$query = $dependencyInjector->get("Phalcon\\Mvc\\Model\\Query", [$phql, $dependencyInjector]);

		$this->_lastQuery = $query;

		return $query;
	}

	public function executeQuery($phql, $placeholders = null, $types = null)
	{

		$query = $this->createQuery($phql);

		if (typeof($placeholders) == "array")
		{
			$query->setBindParams($placeholders);

		}

		if (typeof($types) == "array")
		{
			$query->setBindTypes($types);

		}

		return $query->execute();
	}

	public function createBuilder($params = null)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection object is required to access ORM services");
		}

		return $dependencyInjector->get("Phalcon\\Mvc\\Model\\Query\\Builder", [$params, $dependencyInjector]);
	}

	public function getLastQuery()
	{
		return $this->_lastQuery;
	}

	public function registerNamespaceAlias($alias, $namespaceName)
	{
		$this[$alias] = $namespaceName;

	}

	public function getNamespaceAlias($alias)
	{

		if (function() { if(isset($this->_namespaceAliases[$alias])) {$namespaceName = $this->_namespaceAliases[$alias]; return $namespaceName; } else { return false; } }())
		{
			return $namespaceName;
		}

		throw new Exception("Namespace alias '" . $alias . "' is not registered");
	}

	public function getNamespaceAliases()
	{
		return $this->_namespaceAliases;
	}

	public function __destruct()
	{
		phalcon_orm_destroy_cache();

		Query::clean();

	}


}