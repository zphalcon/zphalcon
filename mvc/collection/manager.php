<?php
namespace Phalcon\Mvc\Collection;

use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Mvc\CollectionInterface;
use Phalcon\Mvc\Collection\BehaviorInterface;

class Manager implements InjectionAwareInterface, EventsAwareInterface
{
	protected $_dependencyInjector;
	protected $_initialized;
	protected $_lastInitialized;
	protected $_eventsManager;
	protected $_customEventsManager;
	protected $_connectionServices;
	protected $_implicitObjectsIds;
	protected $_behaviors;
	protected $_serviceName = "mongo";

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

	}

	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	public function setCustomEventsManager($model, $eventsManager)
	{
		$this[get_class($model)] = $eventsManager;

	}

	public function getCustomEventsManager($model)
	{

		$customEventsManager = $this->_customEventsManager;

		if (typeof($customEventsManager) == "array")
		{
			$className = get_class_lower($model);

			if (isset($customEventsManager[$className]))
			{
				return $customEventsManager[$className];
			}

		}

		return null;
	}

	public function initialize($model)
	{

		$className = get_class($model);

		$initialized = $this->_initialized;

		if (!(isset($initialized[$className])))
		{
			if (method_exists($model, "initialize"))
			{
				$model->initialize();

			}

			$eventsManager = $this->_eventsManager;

			if (typeof($eventsManager) == "object")
			{
				$eventsManager->fire("collectionManager:afterInitialize", $model);

			}

			$this[$className] = $model;

			$this->_lastInitialized = $model;

		}

	}

	public function isInitialized($modelName)
	{
		return isset($this->_initialized[strtolower($modelName)]);
	}

	public function getLastInitialized()
	{
		return $this->_lastInitialized;
	}

	public function setConnectionService($model, $connectionService)
	{
		$this[get_class($model)] = $connectionService;

	}

	public function getConnectionService($model)
	{

		$service = $this->_serviceName;

		$entityName = get_class($model);

		if (isset($this->_connectionServices[$entityName]))
		{
			$service = $this->_connectionServices[$entityName];

		}

		return $service;
	}

	public function useImplicitObjectIds($model, $useImplicitObjectIds)
	{
		$this[get_class($model)] = $useImplicitObjectIds;

	}

	public function isUsingImplicitObjectIds($model)
	{

		if (function() { if(isset($this->_implicitObjectsIds[get_class($model)])) {$implicit = $this->_implicitObjectsIds[get_class($model)]; return $implicit; } else { return false; } }())
		{
			return $implicit;
		}

		return true;
	}

	public function getConnection($model)
	{

		$service = $this->_serviceName;

		$connectionService = $this->_connectionServices;

		if (typeof($connectionService) == "array")
		{
			$entityName = get_class($model);

			if (isset($connectionService[$entityName]))
			{
				$service = $connectionService[$entityName];

			}

		}

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

	public function notifyEvent($eventName, $model)
	{

		$behaviors = $this->_behaviors;

		if (typeof($behaviors) == "array")
		{
			if (function() { if(isset($behaviors[get_class_lower($model)])) {$modelsBehaviors = $behaviors[get_class_lower($model)]; return $modelsBehaviors; } else { return false; } }())
			{
				foreach ($modelsBehaviors as $behavior) {
					$status = $behavior->notify($eventName, $model);
					if ($status === false)
					{
						return false;
					}
				}

			}

		}

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			$status = $eventsManager->fire("collection:" . $eventName, $model);

			if (!($status))
			{
				return $status;
			}

		}

		$customEventsManager = $this->_customEventsManager;

		if (typeof($customEventsManager) == "array")
		{
			if (isset($customEventsManager[get_class_lower($model)]))
			{
				$status = $customEventsManager->fire("collection:" . $eventName, $model);

				if (!($status))
				{
					return $status;
				}

			}

		}

		return $status;
	}

	public function missingMethod($model, $eventName, $data)
	{

		$behaviors = $this->_behaviors;

		if (typeof($behaviors) == "array")
		{
			if (function() { if(isset($behaviors[get_class_lower($model)])) {$modelsBehaviors = $behaviors[get_class_lower($model)]; return $modelsBehaviors; } else { return false; } }())
			{
				foreach ($modelsBehaviors as $behavior) {
					$result = $behavior->missingMethod($model, $eventName, $data);
					if ($result !== null)
					{
						return $result;
					}
				}

			}

		}

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			return $eventsManager->fire("model:" . $eventName, $model, $data);
		}

		return false;
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


}