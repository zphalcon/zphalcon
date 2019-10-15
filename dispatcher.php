<?php
namespace Phalcon;

use Exception;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DispatcherInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Exception as PhalconException;
use Phalcon\FilterInterface;
use Phalcon\Mvc\Model\Binder;
use Phalcon\Mvc\Model\BinderInterface;
abstract 
class Dispatcher implements DispatcherInterface, InjectionAwareInterface, EventsAwareInterface
{
	const EXCEPTION_NO_DI = 0;
	const EXCEPTION_CYCLIC_ROUTING = 1;
	const EXCEPTION_HANDLER_NOT_FOUND = 2;
	const EXCEPTION_INVALID_HANDLER = 3;
	const EXCEPTION_INVALID_PARAMS = 4;
	const EXCEPTION_ACTION_NOT_FOUND = 5;

	protected $_dependencyInjector;
	protected $_eventsManager;
	protected $_activeHandler;
	protected $_finished = false;
	protected $_forwarded = false;
	protected $_moduleName = null;
	protected $_namespaceName = null;
	protected $_handlerName = null;
	protected $_actionName = null;
	protected $_params = [];
	protected $_returnedValue = null;
	protected $_lastHandler = null;
	protected $_defaultNamespace = null;
	protected $_defaultHandler = null;
	protected $_defaultAction = "";
	protected $_handlerSuffix = "";
	protected $_actionSuffix = "Action";
	protected $_previousNamespaceName = null;
	protected $_previousHandlerName = null;
	protected $_previousActionName = null;
	protected $_modelBinding = false;
	protected $_modelBinder = null;
	protected $_isControllerInitialize = false;

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

	public function setActionSuffix($actionSuffix)
	{
		$this->_actionSuffix = $actionSuffix;

	}

	public function getActionSuffix()
	{
		return $this->_actionSuffix;
	}

	public function setModuleName($moduleName)
	{
		$this->_moduleName = $moduleName;

	}

	public function getModuleName()
	{
		return $this->_moduleName;
	}

	public function setNamespaceName($namespaceName)
	{
		$this->_namespaceName = $namespaceName;

	}

	public function getNamespaceName()
	{
		return $this->_namespaceName;
	}

	public function setDefaultNamespace($namespaceName)
	{
		$this->_defaultNamespace = $namespaceName;

	}

	public function getDefaultNamespace()
	{
		return $this->_defaultNamespace;
	}

	public function setDefaultAction($actionName)
	{
		$this->_defaultAction = $actionName;

	}

	public function setActionName($actionName)
	{
		$this->_actionName = $actionName;

	}

	public function getActionName()
	{
		return $this->_actionName;
	}

	public function setParams($params)
	{
		if (typeof($params) <> "array")
		{
			throw new PhalconException("Parameters must be an Array");
		}

		$this->_params = $params;

	}

	public function getParams()
	{
		return $this->_params;
	}

	public function setParam($param, $value)
	{
		$this[$param] = $value;

	}

	public function getParam($param, $filters = null, $defaultValue = null)
	{

		$params = $this->_params;

		if (!(function() { if(isset($params[$param])) {$paramValue = $params[$param]; return $paramValue; } else { return false; } }()))
		{
			return $defaultValue;
		}

		if ($filters === null)
		{
			return $paramValue;
		}

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			$this->_throwDispatchException("A dependency injection object is required to access the 'filter' service", self::EXCEPTION_NO_DI);

		}

		$filter = $dependencyInjector->getShared("filter");

		return $filter->sanitize($paramValue, $filters);
	}

	public function hasParam($param)
	{
		return isset($this->_params[$param]);
	}

	public function getActiveMethod()
	{
		return $this->_actionName . $this->_actionSuffix;
	}

	public function isFinished()
	{
		return $this->_finished;
	}

	public function setReturnedValue($value)
	{
		$this->_returnedValue = $value;

	}

	public function getReturnedValue()
	{
		return $this->_returnedValue;
	}

	deprecated public function setModelBinding($value, $cache = null)
	{

		if (typeof($cache) == "string")
		{
			$dependencyInjector = $this->_dependencyInjector;

			$cache = $dependencyInjector->get($cache);

		}

		$this->_modelBinding = $value;

		if ($value)
		{
			$this->_modelBinder = new Binder($cache);

		}

		return $this;
	}

	public function setModelBinder($modelBinder, $cache = null)
	{

		if (typeof($cache) == "string")
		{
			$dependencyInjector = $this->_dependencyInjector;

			$cache = $dependencyInjector->get($cache);

		}

		if ($cache <> null)
		{
			$modelBinder->setCache($cache);

		}

		$this->_modelBinding = true;

		$this->_modelBinder = $modelBinder;

		return $this;
	}

	public function getModelBinder()
	{
		return $this->_modelBinder;
	}

	public function dispatch()
	{



		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			$this->_throwDispatchException("A dependency injection container is required to access related dispatching services", self::EXCEPTION_NO_DI);

			return false;
		}

		$eventsManager = $this->_eventsManager;

		$hasEventsManager = typeof($eventsManager) == "object";

		$this->_finished = true;

		if ($hasEventsManager)
		{
			try {
				if ($eventsManager->fire("dispatch:beforeDispatchLoop", $this) === false && $this->_finished !== false)
				{
					return false;
				}
			} catch (Exception $e) {
				$status = $this->_handleException($e);
				if ($this->_finished !== false)
				{
					if ($status === false)
					{
						return false;
					}

					throw $e;
				}

			}
		}

		$value = null;
		$handler = null;
		$numberDispatches = 0;
		$actionSuffix = $this->_actionSuffix;
		$this->_finished = false;

		while (!($this->_finished)) {
			$numberDispatches++;
			if ($numberDispatches == 256)
			{
				$this->_throwDispatchException("Dispatcher has detected a cyclic routing causing stability problems", self::EXCEPTION_CYCLIC_ROUTING);

				break;

			}
			$this->_finished = true;
			$this->_resolveEmptyProperties();
			if ($hasEventsManager)
			{
				try {
					if ($eventsManager->fire("dispatch:beforeDispatch", $this) === false || $this->_finished === false)
					{
						continue;

					}
				} catch (Exception $e) {
					if ($this->_handleException($e) === false || $this->_finished === false)
					{
						continue;

					}
					throw $e;
				}
			}
			$handlerClass = $this->getHandlerClass();
			$hasService = (bool) $dependencyInjector->has($handlerClass);
			if (!($hasService))
			{
				$hasService = (bool) class_exists($handlerClass);

			}
			if (!($hasService))
			{
				$status = $this->_throwDispatchException($handlerClass . " handler class cannot be loaded", self::EXCEPTION_HANDLER_NOT_FOUND);

				if ($status === false && $this->_finished === false)
				{
					continue;

				}

				break;

			}
			$handler = $dependencyInjector->getShared($handlerClass);
			$wasFresh = $dependencyInjector->wasFreshInstance();
			if (typeof($handler) !== "object")
			{
				$status = $this->_throwDispatchException("Invalid handler returned from the services container", self::EXCEPTION_INVALID_HANDLER);

				if ($status === false && $this->_finished === false)
				{
					continue;

				}

				break;

			}
			$this->_activeHandler = $handler;
			$namespaceName = $this->_namespaceName;
			$handlerName = $this->_handlerName;
			$actionName = $this->_actionName;
			$params = $this->_params;
			if (typeof($params) <> "array")
			{
				$status = $this->_throwDispatchException("Action parameters must be an Array", self::EXCEPTION_INVALID_PARAMS);

				if ($status === false && $this->_finished === false)
				{
					continue;

				}

				break;

			}
			$actionMethod = $this->getActiveMethod();
			if (!(is_callable([$handler, $actionMethod])))
			{
				if ($hasEventsManager)
				{
					if ($eventsManager->fire("dispatch:beforeNotFoundAction", $this) === false)
					{
						continue;

					}

					if ($this->_finished === false)
					{
						continue;

					}

				}

				$status = $this->_throwDispatchException("Action '" . $actionName . "' was not found on handler '" . $handlerName . "'", self::EXCEPTION_ACTION_NOT_FOUND);

				if ($status === false && $this->_finished === false)
				{
					continue;

				}

				break;

			}
			if ($hasEventsManager)
			{
				try {
					if ($eventsManager->fire("dispatch:beforeExecuteRoute", $this) === false || $this->_finished === false)
					{
						$dependencyInjector->remove($handlerClass);

						continue;

					}
				} catch (Exception $e) {
					if ($this->_handleException($e) === false || $this->_finished === false)
					{
						$dependencyInjector->remove($handlerClass);

						continue;

					}
					throw $e;
				}
			}
			if (method_exists($handler, "beforeExecuteRoute"))
			{
				try {
					if ($handler->beforeExecuteRoute($this) === false || $this->_finished === false)
					{
						$dependencyInjector->remove($handlerClass);

						continue;

					}
				} catch (Exception $e) {
					if ($this->_handleException($e) === false || $this->_finished === false)
					{
						$dependencyInjector->remove($handlerClass);

						continue;

					}
					throw $e;
				}
			}
			if ($wasFresh === true)
			{
				if (method_exists($handler, "initialize"))
				{
					try {
						$this->_isControllerInitialize = true;
						$handler->initialize();
					} catch (Exception $e) {
						$this->_isControllerInitialize = false;
						if ($this->_handleException($e) === false || $this->_finished === false)
						{
							continue;

						}
						throw $e;
					}
				}

				$this->_isControllerInitialize = false;

				if ($eventsManager)
				{
					try {
						if ($eventsManager->fire("dispatch:afterInitialize", $this) === false || $this->_finished === false)
						{
							continue;

						}
					} catch (Exception $e) {
						if ($this->_handleException($e) === false || $this->_finished === false)
						{
							continue;

						}
						throw $e;
					}
				}

			}
			if ($this->_modelBinding)
			{
				$modelBinder = $this->_modelBinder;

				$bindCacheKey = "_PHMB_" . $handlerClass . "_" . $actionMethod;

				$params = $modelBinder->bindToHandler($handler, $params, $bindCacheKey, $actionMethod);

			}
			if ($hasEventsManager)
			{
				if ($eventsManager->fire("dispatch:afterBinding", $this) === false)
				{
					continue;

				}

				if ($this->_finished === false)
				{
					continue;

				}

			}
			if (method_exists($handler, "afterBinding"))
			{
				if ($handler->afterBinding($this) === false)
				{
					continue;

				}

				if ($this->_finished === false)
				{
					continue;

				}

			}
			$this->_lastHandler = $handler;
			try {
				$this->_returnedValue = $this->callActionMethod($handler, $actionMethod, $params);
				if ($this->_finished === false)
				{
					continue;

				}
			} catch (Exception $e) {
				if ($this->_handleException($e) === false || $this->_finished === false)
				{
					continue;

				}
				throw $e;
			}			if ($hasEventsManager)
			{
				try {
					if ($eventsManager->fire("dispatch:afterExecuteRoute", $this, $value) === false || $this->_finished === false)
					{
						continue;

					}
				} catch (Exception $e) {
					if ($this->_handleException($e) === false || $this->_finished === false)
					{
						continue;

					}
					throw $e;
				}
			}
			if (method_exists($handler, "afterExecuteRoute"))
			{
				try {
					if ($handler->afterExecuteRoute($this, $value) === false || $this->_finished === false)
					{
						continue;

					}
				} catch (Exception $e) {
					if ($this->_handleException($e) === false || $this->_finished === false)
					{
						continue;

					}
					throw $e;
				}
			}
			if ($hasEventsManager)
			{
				try {
					$eventsManager->fire("dispatch:afterDispatch", $this, $value);
				} catch (Exception $e) {
					if ($this->_handleException($e) === false || $this->_finished === false)
					{
						continue;

					}
					throw $e;
				}
			}
		}

		if ($hasEventsManager)
		{
			try {
				$eventsManager->fire("dispatch:afterDispatchLoop", $this);
			} catch (Exception $e) {
				if ($this->_handleException($e) === false)
				{
					return false;
				}
				throw $e;
			}
		}

		return $handler;
	}

	public function forward($forward)
	{

		if ($this->_isControllerInitialize === true)
		{
			throw new PhalconException("Forwarding inside a controller's initialize() method is forbidden");
		}

		if (typeof($forward) !== "array")
		{
			throw new PhalconException("Forward parameter must be an Array");
		}

		$this->_previousNamespaceName = $this->_namespaceName;
		$this->_previousHandlerName = $this->_handlerName;
		$this->_previousActionName = $this->_actionName;

		if (function() { if(isset($forward["namespace"])) {$namespaceName = $forward["namespace"]; return $namespaceName; } else { return false; } }())
		{
			$this->_namespaceName = $namespaceName;

		}

		if (function() { if(isset($forward["controller"])) {$controllerName = $forward["controller"]; return $controllerName; } else { return false; } }())
		{
			$this->_handlerName = $controllerName;

		}

		if (function() { if(isset($forward["action"])) {$actionName = $forward["action"]; return $actionName; } else { return false; } }())
		{
			$this->_actionName = $actionName;

		}

		if (function() { if(isset($forward["params"])) {$params = $forward["params"]; return $params; } else { return false; } }())
		{
			$this->_params = $params;

		}

		$this->_finished = false;
		$this->_forwarded = true;

	}

	public function wasForwarded()
	{
		return $this->_forwarded;
	}

	public function getHandlerClass()
	{

		$this->_resolveEmptyProperties();

		$handlerSuffix = $this->_handlerSuffix;
		$handlerName = $this->_handlerName;
		$namespaceName = $this->_namespaceName;

		if (!(memstr($handlerName, "\\")))
		{
			$camelizedClass = camelize($handlerName);

		}

		if ($namespaceName)
		{
			if (ends_with($namespaceName, "\\"))
			{
				$handlerClass = $namespaceName . $camelizedClass . $handlerSuffix;

			}

		}

		return $handlerClass;
	}

	public function callActionMethod($handler, $actionMethod, $params = [])
	{
		return call_user_func_array([$handler, $actionMethod], $params);
	}

	public function getBoundModels()
	{

		$modelBinder = $this->_modelBinder;

		if ($modelBinder <> null)
		{
			return $modelBinder->getBoundModels();
		}

		return [];
	}

	protected function _resolveEmptyProperties()
	{
		if (!($this->_namespaceName))
		{
			$this->_namespaceName = $this->_defaultNamespace;

		}

		if (!($this->_handlerName))
		{
			$this->_handlerName = $this->_defaultHandler;

		}

		if (!($this->_actionName))
		{
			$this->_actionName = $this->_defaultAction;

		}

	}


}