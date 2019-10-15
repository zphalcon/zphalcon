<?php
namespace Phalcon\Mvc;

use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Mvc\Collection\Document;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Mvc\Collection\ManagerInterface;
use Phalcon\Mvc\Collection\BehaviorInterface;
use Phalcon\Mvc\Collection\Exception;
use Phalcon\Mvc\Model\MessageInterface;
use Phalcon\Mvc\Model\Message as Message;
use Phalcon\ValidationInterface;
abstract 
class Collection implements EntityInterface, CollectionInterface, InjectionAwareInterface, \Serializable
{
	const OP_NONE = 0;
	const OP_CREATE = 1;
	const OP_UPDATE = 2;
	const OP_DELETE = 3;
	const DIRTY_STATE_PERSISTENT = 0;
	const DIRTY_STATE_TRANSIENT = 1;
	const DIRTY_STATE_DETACHED = 2;

	public $_id;
	protected $_dependencyInjector;
	protected $_modelsManager;
	protected $_source;
	protected $_operationMade = 0;
	protected $_dirtyState = 1;
	protected $_connection;
	protected $_errorMessages = [];
	protected static $_reserved;
	protected static $_disableEvents;
	protected $_skipped = false;

	public final function __construct($dependencyInjector = null, $modelsManager = null)
	{
		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = Di::getDefault();

		}

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injector container is required to obtain the services related to the ODM");
		}

		$this->_dependencyInjector = $dependencyInjector;

		if (typeof($modelsManager) <> "object")
		{
			$modelsManager = $dependencyInjector->getShared("collectionManager");

			if (typeof($modelsManager) <> "object")
			{
				throw new Exception("The injected service 'modelsManager' is not valid");
			}

		}

		$this->_modelsManager = $modelsManager;

		$modelsManager->initialize($this);

		if (method_exists($this, "onConstruct"))
		{
			$this->onConstruct();

		}

	}

	public function setId($id)
	{

		if (typeof($id) <> "object")
		{
			if ($this->_modelsManager->isUsingImplicitObjectIds($this))
			{
				$mongoId = new \MongoId($id);

			}

		}

		$this->_id = $mongoId;

	}

	public function getId()
	{
		return $this->_id;
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

	public function getCollectionManager()
	{
		return $this->_modelsManager;
	}

	public function getReservedAttributes()
	{

		$reserved = self::_reserved;

		if (typeof($reserved) <> "array")
		{
			$reserved = ["_connection" => true, "_dependencyInjector" => true, "_source" => true, "_operationMade" => true, "_errorMessages" => true, "_dirtyState" => true, "_modelsManager" => true, "_skipped" => true];

			self::_reserved = $reserved;

		}

		return $reserved;
	}

	protected function useImplicitObjectIds($useImplicitObjectIds)
	{
		$this->_modelsManager->useImplicitObjectIds($this, $useImplicitObjectIds);

	}

	protected function setSource($source)
	{
		$this->_source = $source;

		return $this;
	}

	public function getSource()
	{

		if (!($this->_source))
		{
			$collection = $this;

			$this->_source = uncamelize(get_class_ns($collection));

		}

		return $this->_source;
	}

	public function setConnectionService($connectionService)
	{
		$this->_modelsManager->setConnectionService($this, $connectionService);

		return $this;
	}

	public function getConnectionService()
	{
		return $this->_modelsManager->getConnectionService($this);
	}

	public function getConnection()
	{
		if (typeof($this->_connection) <> "object")
		{
			$this->_connection = $this->_modelsManager->getConnection($this);

		}

		return $this->_connection;
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

	public static function cloneResult($collection, $document)
	{

		$clonedCollection = clone $collection;

		foreach ($document as $key => $value) {
			$clonedCollection->writeAttribute($key, $value);
		}

		if (method_exists($clonedCollection, "afterFetch"))
		{
			$clonedCollection->afterFetch();

		}

		return $clonedCollection;
	}

	protected static function _getResultset($params, $collection, $connection, $unique)
	{

		if (function() { if(isset($params["class"])) {$className = $params["class"]; return $className; } else { return false; } }())
		{
			$base = new $className();

			if (!($base instanceof $CollectionInterface || $base instanceof $Collection\Document))
			{
				throw new Exception("Object of class '" . $className . "' must be an implementation of Phalcon\\Mvc\\CollectionInterface or an instance of Phalcon\\Mvc\\Collection\\Document");
			}

		}

		if ($base instanceof $Collection)
		{
			$base->setDirtyState(self::DIRTY_STATE_PERSISTENT);

		}

		$source = $collection->getSource();

		if (empty($source))
		{
			throw new Exception("Method getSource() returns empty string");
		}

		$mongoCollection = $connection->selectCollection($source);

		if (typeof($mongoCollection) <> "object")
		{
			throw new Exception("Couldn't select mongo collection");
		}

		if (!(function() { if(isset($params[0])) {$conditions = $params[0]; return $conditions; } else { return false; } }()))
		{
			if (!(function() { if(isset($params["conditions"])) {$conditions = $params["conditions"]; return $conditions; } else { return false; } }()))
			{
				$conditions = [];

			}

		}

		if (typeof($conditions) <> "array")
		{
			throw new Exception("Find parameters must be an array");
		}

		if (function() { if(isset($params["fields"])) {$fields = $params["fields"]; return $fields; } else { return false; } }())
		{
			$documentsCursor = $mongoCollection->find($conditions, $fields);

		}

		if (function() { if(isset($params["limit"])) {$limit = $params["limit"]; return $limit; } else { return false; } }())
		{
			$documentsCursor->limit($limit);

		}

		if (function() { if(isset($params["sort"])) {$sort = $params["sort"]; return $sort; } else { return false; } }())
		{
			$documentsCursor->sort($sort);

		}

		if (function() { if(isset($params["skip"])) {$skip = $params["skip"]; return $skip; } else { return false; } }())
		{
			$documentsCursor->skip($skip);

		}

		if ($unique === true)
		{
			$documentsCursor->rewind();

			$document = $documentsCursor->current();

			if (typeof($document) <> "array")
			{
				return false;
			}

			return static::cloneResult($base, $document);
		}

		$collections = [];

		foreach (iterator_to_array($documentsCursor, false) as $document) {
			$collections = static::cloneResult($base, $document);
		}

		return $collections;
	}

	protected static function _getGroupResultset($params, $collection, $connection)
	{

		$source = $collection->getSource();

		if (empty($source))
		{
			throw new Exception("Method getSource() returns empty string");
		}

		$mongoCollection = $connection->selectCollection($source);

		if (!(function() { if(isset($params[0])) {$conditions = $params[0]; return $conditions; } else { return false; } }()))
		{
			if (!(function() { if(isset($params["conditions"])) {$conditions = $params["conditions"]; return $conditions; } else { return false; } }()))
			{
				$conditions = [];

			}

		}

		if (isset($params["limit"]) || isset($params["sort"]) || isset($params["skip"]))
		{
			$documentsCursor = $mongoCollection->find($conditions);

			if (function() { if(isset($params["limit"])) {$limit = $params["limit"]; return $limit; } else { return false; } }())
			{
				$documentsCursor->limit($limit);

			}

			if (function() { if(isset($params["sort"])) {$sort = $params["sort"]; return $sort; } else { return false; } }())
			{
				$documentsCursor->sort($sort);

			}

			if (function() { if(isset($params["skip"])) {$sort = $params["skip"]; return $sort; } else { return false; } }())
			{
				$documentsCursor->skip($sort);

			}

			return count($documentsCursor);
		}

		return $mongoCollection->count($conditions);
	}

	protected final function _preSave($dependencyInjector, $disableEvents, $exists)
	{

		if (!($disableEvents))
		{
			if ($this->fireEventCancel("beforeValidation") === false)
			{
				return false;
			}

			if (!($exists))
			{
				$eventName = "beforeValidationOnCreate";

			}

			if ($this->fireEventCancel($eventName) === false)
			{
				return false;
			}

		}

		if ($this->fireEventCancel("validation") === false)
		{
			if (!($disableEvents))
			{
				$this->fireEvent("onValidationFails");

			}

			return false;
		}

		if (!($disableEvents))
		{
			if (!($exists))
			{
				$eventName = "afterValidationOnCreate";

			}

			if ($this->fireEventCancel($eventName) === false)
			{
				return false;
			}

			if ($this->fireEventCancel("afterValidation") === false)
			{
				return false;
			}

			if ($this->fireEventCancel("beforeSave") === false)
			{
				return false;
			}

			if ($exists)
			{
				$eventName = "beforeUpdate";

			}

			if ($this->fireEventCancel($eventName) === false)
			{
				return false;
			}

		}

		return true;
	}

	protected final function _postSave($disableEvents, $success, $exists)
	{

		if ($success)
		{
			if (!($disableEvents))
			{
				if ($exists)
				{
					$eventName = "afterUpdate";

				}

				$this->fireEvent($eventName);

				$this->fireEvent("afterSave");

			}

			return $success;
		}

		if (!($disableEvents))
		{
			$this->fireEvent("notSave");

		}

		$this->_cancelOperation($disableEvents);

		return false;
	}

	protected function validate($validator)
	{

		if ($validator instanceof $Model\ValidatorInterface)
		{
			if ($validator->validate($this) === false)
			{
				foreach ($validator->getMessages() as $message) {
					$this->_errorMessages[] = $message;
				}

			}

		}

	}

	public function validationHasFailed()
	{
		return count($this->_errorMessages) > 0;
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

	protected function _cancelOperation($disableEvents)
	{

		if (!($disableEvents))
		{
			if ($this->_operationMade == self::OP_DELETE)
			{
				$eventName = "notDeleted";

			}

			$this->fireEvent($eventName);

		}

		return false;
	}

	protected function _exists($collection)
	{

		if (!(function() { if(isset($this->_id)) {$id = $this->_id; return $id; } else { return false; } }()))
		{
			return false;
		}

		if (typeof($id) == "object")
		{
			$mongoId = $id;

		}

		if (!($this->_dirtyState))
		{
			return true;
		}

		$exists = $collection->count(["_id" => $mongoId]) > 0;

		if ($exists)
		{
			$this->_dirtyState = self::DIRTY_STATE_PERSISTENT;

		}

		return $exists;
	}

	public function getMessages()
	{
		return $this->_errorMessages;
	}

	public function appendMessage($message)
	{
		$this->_errorMessages[] = $message;

	}

	protected function prepareCU()
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injector container is required to obtain the services related to the ODM");
		}

		$source = $this->getSource();

		if (empty($source))
		{
			throw new Exception("Method getSource() returns empty string");
		}

		$connection = $this->getConnection();

		$collection = $connection->selectCollection($source);

		return $collection;
	}

	public function save()
	{

		$collection = $this->prepareCU();

		$exists = $this->_exists($collection);

		if ($exists === false)
		{
			$this->_operationMade = self::OP_CREATE;

		}

		$this->_errorMessages = [];

		if ($this->_preSave($this->_dependencyInjector, self::_disableEvents, $exists) === false)
		{
			return false;
		}

		$data = $this->toArray();

		$success = false;

		$status = $collection->save($data, ["w" => true]);

		if (typeof($status) == "array")
		{
			if (function() { if(isset($status["ok"])) {$ok = $status["ok"]; return $ok; } else { return false; } }())
			{
				if ($ok)
				{
					$success = true;

					if ($exists === false)
					{
						if (function() { if(isset($data["_id"])) {$id = $data["_id"]; return $id; } else { return false; } }())
						{
							$this->_id = $id;

						}

						$this->_dirtyState = self::DIRTY_STATE_PERSISTENT;

					}

				}

			}

		}

		return $this->_postSave(self::_disableEvents, $success, $exists);
	}

	public function create()
	{

		$collection = $this->prepareCU();

		$exists = false;

		$this->_operationMade = self::OP_CREATE;

		$this->_errorMessages = [];

		if ($this->_preSave($this->_dependencyInjector, self::_disableEvents, $exists) === false)
		{
			return false;
		}

		$data = $this->toArray();

		$success = false;

		$status = $collection->insert($data, ["w" => true]);

		if (typeof($status) == "array")
		{
			if (function() { if(isset($status["ok"])) {$ok = $status["ok"]; return $ok; } else { return false; } }())
			{
				if ($ok)
				{
					$success = true;

					if ($exists === false)
					{
						if (function() { if(isset($data["_id"])) {$id = $data["_id"]; return $id; } else { return false; } }())
						{
							$this->_id = $id;

						}

						$this->_dirtyState = self::DIRTY_STATE_PERSISTENT;

					}

				}

			}

		}

		return $this->_postSave(self::_disableEvents, $success, $exists);
	}

	public function createIfNotExist($criteria)
	{

		if (empty($criteria))
		{
			throw new Exception("Criteria parameter must be array with one or more attributes of the model");
		}

		$collection = $this->prepareCU();

		$exists = false;

		$this->_operationMade = self::OP_NONE;

		$this->_errorMessages = [];

		if ($this->_preSave($this->_dependencyInjector, self::_disableEvents, $exists) === false)
		{
			return false;
		}

		$keys = array_flip($criteria);

		$data = $this->toArray();

		if (array_diff_key($keys, $data))
		{
			throw new Exception("Criteria parameter must be array with one or more attributes of the model");
		}

		$query = array_intersect_key($data, $keys);

		$success = false;

		$status = $collection->findAndModify($query, ["$setOnInsert" => $data], null, ["new" => false, "upsert" => true]);

		if ($status == null)
		{
			$doc = $collection->findOne($query);

			if (typeof($doc) == "array")
			{
				$success = true;

				$this->_operationMade = self::OP_CREATE;

				$this->_id = $doc["_id"];

			}

		}

		return $this->_postSave(self::_disableEvents, $success, $exists);
	}

	public function update()
	{

		$collection = $this->prepareCU();

		$exists = $this->_exists($collection);

		if (!($exists))
		{
			throw new Exception("The document cannot be updated because it doesn't exist");
		}

		$this->_operationMade = self::OP_UPDATE;

		$this->_errorMessages = [];

		if ($this->_preSave($this->_dependencyInjector, self::_disableEvents, $exists) === false)
		{
			return false;
		}

		$data = $this->toArray();

		$success = false;

		$status = $collection->update(["_id" => $this->_id], $data, ["w" => true]);

		if (typeof($status) == "array")
		{
			if (function() { if(isset($status["ok"])) {$ok = $status["ok"]; return $ok; } else { return false; } }())
			{
				if ($ok)
				{
					$success = true;

				}

			}

		}

		return $this->_postSave(self::_disableEvents, $success, $exists);
	}

	public static function findById($id)
	{

		if (typeof($id) <> "object")
		{
			if (!(preg_match("/^[a-f\d]{24}$/i", $id)))
			{
				return null;
			}

			$className = get_called_class();

			$collection = new $className();

			if ($collection->getCollectionManager()->isUsingImplicitObjectIds($collection))
			{
				$mongoId = new \MongoId($id);

			}

		}

		return static::findFirst([["_id" => $mongoId]]);
	}

	public static function findFirst($parameters = null)
	{

		$className = get_called_class();

		$collection = new $className();

		$connection = $collection->getConnection();

		return static::_getResultset($parameters, $collection, $connection, true);
	}

	public static function find($parameters = null)
	{

		$className = get_called_class();

		$collection = new $className();

		return static::_getResultset($parameters, $collection, $collection->getConnection(), false);
	}

	public static function count($parameters = null)
	{

		$className = get_called_class();

		$collection = new $className();

		$connection = $collection->getConnection();

		return static::_getGroupResultset($parameters, $collection, $connection);
	}

	public static function aggregate($parameters = null, $options = null)
	{

		$className = get_called_class();

		$model = new $className();

		$connection = $model->getConnection();

		$source = $model->getSource();

		if (empty($source))
		{
			throw new Exception("Method getSource() returns empty string");
		}

		return $connection->selectCollection($source)->aggregate($parameters, $options);
	}

	public static function summatory($field, $conditions = null, $finalize = null)
	{

		$className = get_called_class();

		$model = new $className();

		$connection = $model->getConnection();

		$source = $model->getSource();

		if (empty($source))
		{
			throw new Exception("Method getSource() returns empty string");
		}

		$collection = $connection->selectCollection($source);

		$initial = ["summatory" => []];

		$reduce = "function (curr, result) { if (typeof result.summatory[curr." . $field . "] === \"undefined\") { result.summatory[curr." . $field . "] = 1; } else { result.summatory[curr." . $field . "]++; } }";

		$group = $collection->group([], $initial, $reduce);

		if (function() { if(isset($group["retval"])) {$retval = $group["retval"]; return $retval; } else { return false; } }())
		{
			if (function() { if(isset($retval[0])) {$firstRetval = $retval[0]; return $firstRetval; } else { return false; } }())
			{
				if (isset($firstRetval["summatory"]))
				{
					return $firstRetval["summatory"];
				}

				return $firstRetval;
			}

			return $retval;
		}

		return [];
	}

	public function delete()
	{

		if (!(function() { if(isset($this->_id)) {$id = $this->_id; return $id; } else { return false; } }()))
		{
			throw new Exception("The document cannot be deleted because it doesn't exist");
		}

		$disableEvents = self::_disableEvents;

		if (!($disableEvents))
		{
			if ($this->fireEventCancel("beforeDelete") === false)
			{
				return false;
			}

		}

		if ($this->_skipped === true)
		{
			return true;
		}

		$connection = $this->getConnection();

		$source = $this->getSource();

		if (empty($source))
		{
			throw new Exception("Method getSource() returns empty string");
		}

		$collection = $connection->selectCollection($source);

		if (typeof($id) == "object")
		{
			$mongoId = $id;

		}

		$success = false;

		$status = $collection->remove(["_id" => $mongoId], ["w" => true]);

		if (typeof($status) <> "array")
		{
			return false;
		}

		if (function() { if(isset($status["ok"])) {$ok = $status["ok"]; return $ok; } else { return false; } }())
		{
			if ($ok)
			{
				$success = true;

				if (!($disableEvents))
				{
					$this->fireEvent("afterDelete");

				}

				$this->_dirtyState = self::DIRTY_STATE_DETACHED;

			}

		}

		return $success;
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

	protected function addBehavior($behavior)
	{
		$this->_modelsManager->addBehavior($this, $behavior);

	}

	public function skipOperation($skip)
	{
		$this->_skipped = $skip;

	}

	public function toArray()
	{

		$reserved = $this->getReservedAttributes();

		$data = [];

		foreach (get_object_vars($this) as $key => $value) {
			if ($key == "_id")
			{
				if ($value)
				{
					$data[$key] = $value;

				}

			}
		}

		return $data;
	}

	public function serialize()
	{
		return serialize($this->toArray());
	}

	public function unserialize($data)
	{

		$attributes = unserialize($data);

		if (typeof($attributes) == "array")
		{
			$dependencyInjector = Di::getDefault();

			if (typeof($dependencyInjector) <> "object")
			{
				throw new Exception("A dependency injector container is required to obtain the services related to the ODM");
			}

			$this->_dependencyInjector = $dependencyInjector;

			$manager = $dependencyInjector->getShared("collectionManager");

			if (typeof($manager) <> "object")
			{
				throw new Exception("The injected service 'collectionManager' is not valid");
			}

			$this->_modelsManager = $manager;

			foreach ($attributes as $key => $value) {
				$this->{$key} = $value;
			}

		}

	}


}