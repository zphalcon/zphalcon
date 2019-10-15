<?php
namespace Phalcon\Mvc;

use Phalcon\DiInterface;
use Phalcon\Di\Injectable;
use Phalcon\Mvc\Controller;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Micro\Exception;
use Phalcon\Di\ServiceInterface;
use Phalcon\Mvc\Micro\Collection;
use Phalcon\Mvc\Micro\LazyLoader;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Model\BinderInterface;
use Phalcon\Mvc\Router\RouteInterface;
use Phalcon\Mvc\Micro\MiddlewareInterface;
use Phalcon\Mvc\Micro\CollectionInterface;

class Micro extends Injectable implements \ArrayAccess
{
	protected $_dependencyInjector;
	protected $_handlers = [];
	protected $_router;
	protected $_stopped;
	protected $_notFoundHandler;
	protected $_errorHandler;
	protected $_activeHandler;
	protected $_beforeHandlers;
	protected $_afterHandlers;
	protected $_finishHandlers;
	protected $_returnedValue;
	protected $_modelBinder;
	protected $_afterBindingHandlers;

	public function __construct($dependencyInjector = null)
	{
		if (typeof($dependencyInjector) == "object")
		{
			if ($dependencyInjector instanceof $DiInterface)
			{
				$this->setDi($dependencyInjector);

			}

		}

	}

	public function setDI($dependencyInjector)
	{
		if (!($dependencyInjector->has("application")))
		{
			$dependencyInjector->set("application", $this);

		}

		$this->_dependencyInjector = $dependencyInjector;

	}

	public function map($routePattern, $handler)
	{

		$router = $this->getRouter();

		$route = $router->add($routePattern);

		$this[$route->getRouteId()] = $handler;

		return $route;
	}

	public function get($routePattern, $handler)
	{

		$router = $this->getRouter();

		$route = $router->addGet($routePattern);

		$this[$route->getRouteId()] = $handler;

		return $route;
	}

	public function post($routePattern, $handler)
	{

		$router = $this->getRouter();

		$route = $router->addPost($routePattern);

		$this[$route->getRouteId()] = $handler;

		return $route;
	}

	public function put($routePattern, $handler)
	{

		$router = $this->getRouter();

		$route = $router->addPut($routePattern);

		$this[$route->getRouteId()] = $handler;

		return $route;
	}

	public function patch($routePattern, $handler)
	{

		$router = $this->getRouter();

		$route = $router->addPatch($routePattern);

		$this[$route->getRouteId()] = $handler;

		return $route;
	}

	public function head($routePattern, $handler)
	{

		$router = $this->getRouter();

		$route = $router->addHead($routePattern);

		$this[$route->getRouteId()] = $handler;

		return $route;
	}

	public function delete($routePattern, $handler)
	{

		$router = $this->getRouter();

		$route = $router->addDelete($routePattern);

		$this[$route->getRouteId()] = $handler;

		return $route;
	}

	public function options($routePattern, $handler)
	{

		$router = $this->getRouter();

		$route = $router->addOptions($routePattern);

		$this[$route->getRouteId()] = $handler;

		return $route;
	}

	public function mount($collection)
	{

		$mainHandler = $collection->getHandler();

		if (empty($mainHandler))
		{
			throw new Exception("Collection requires a main handler");
		}

		$handlers = $collection->getHandlers();

		if (!(count($handlers)))
		{
			throw new Exception("There are no handlers to mount");
		}

		if (typeof($handlers) == "array")
		{
			if ($collection->isLazy())
			{
				$lazyHandler = new LazyLoader($mainHandler);

			}

			$prefix = $collection->getPrefix();

			foreach ($handlers as $handler) {
				if (typeof($handler) <> "array")
				{
					throw new Exception("One of the registered handlers is invalid");
				}
				$methods = $handler[0];
				$pattern = $handler[1];
				$subHandler = $handler[2];
				$name = $handler[3];
				$realHandler = [$lazyHandler, $subHandler];
				if (!(empty($prefix)))
				{
					if ($pattern == "/")
					{
						$prefixedPattern = $prefix;

					}

				}
				$route = $this->map($prefixedPattern, $realHandler);
				if (typeof($methods) == "string" && $methods <> "" || typeof($methods) == "array")
				{
					$route->via($methods);

				}
				if (typeof($name) == "string")
				{
					$route->setName($name);

				}
			}

		}

		return $this;
	}

	public function notFound($handler)
	{
		$this->_notFoundHandler = $handler;

		return $this;
	}

	public function error($handler)
	{
		$this->_errorHandler = $handler;

		return $this;
	}

	public function getRouter()
	{

		$router = $this->_router;

		if (typeof($router) <> "object")
		{
			$router = $this->getSharedService("router");

			$router->clear();

			$router->removeExtraSlashes(true);

			$this->_router = $router;

		}

		return $router;
	}

	public function setService($serviceName, $definition, $shared = false)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = new FactoryDefault();

			$this->_dependencyInjector = $dependencyInjector;

		}

		return $dependencyInjector->set($serviceName, $definition, $shared);
	}

	public function hasService($serviceName)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = new FactoryDefault();

			$this->_dependencyInjector = $dependencyInjector;

		}

		return $dependencyInjector->has($serviceName);
	}

	public function getService($serviceName)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = new FactoryDefault();

			$this->_dependencyInjector = $dependencyInjector;

		}

		return $dependencyInjector->get($serviceName);
	}

	public function getSharedService($serviceName)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = new FactoryDefault();

			$this->_dependencyInjector = $dependencyInjector;

		}

		return $dependencyInjector->getShared($serviceName);
	}

	public function handle($uri = null)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection container is required to access required micro services");
		}

		try {
			$returnedValue = null;
			$eventsManager = $this->_eventsManager;
			if (typeof($eventsManager) == "object")
			{
				if ($eventsManager->fire("micro:beforeHandleRoute", $this) === false)
				{
					return false;
				}

			}
			$router = $dependencyInjector->getShared("router");
			$router->handle($uri);
			$matchedRoute = $router->getMatchedRoute();
			if (typeof($matchedRoute) == "object")
			{
				if (!(function() { if(isset($this->_handlers[$matchedRoute->getRouteId()])) {$handler = $this->_handlers[$matchedRoute->getRouteId()]; return $handler; } else { return false; } }()))
				{
					throw new Exception("Matched route doesn't have an associated handler");
				}

				$this->_activeHandler = $handler;

				if (typeof($eventsManager) == "object")
				{
					if ($eventsManager->fire("micro:beforeExecuteRoute", $this) === false)
					{
						return false;
					}

				}

				$beforeHandlers = $this->_beforeHandlers;

				if (typeof($beforeHandlers) == "array")
				{
					$this->_stopped = false;

					foreach ($beforeHandlers as $before) {
						if (typeof($before) == "object")
						{
							if ($before instanceof $MiddlewareInterface)
							{
								$status = $before->call($this);

								if ($this->_stopped)
								{
									break;

								}

								continue;

							}

						}
						if (!(is_callable($before)))
						{
							throw new Exception("'before' handler is not callable");
						}
						$status = call_user_func($before);
						if ($this->_stopped)
						{
							break;

						}
					}

					if ($this->_stopped)
					{
						return $status;
					}

				}

				$params = $router->getParams();

				$modelBinder = $this->_modelBinder;

				if (typeof($handler) == "object" && $handler instanceof $\Closure)
				{
					$handler = \Closure::bind($handler, $this);

					if ($modelBinder <> null)
					{
						$routeName = $matchedRoute->getName();

						if ($routeName <> null)
						{
							$bindCacheKey = "_PHMB_" . $routeName;

						}

						$params = $modelBinder->bindToHandler($handler, $params, $bindCacheKey);

					}

				}

				if (typeof($handler) == "array")
				{
					$realHandler = $handler[0];

					if ($realHandler instanceof $Controller && $modelBinder <> null)
					{
						$methodName = $handler[1];

						$bindCacheKey = "_PHMB_" . get_class($realHandler) . "_" . $methodName;

						$params = $modelBinder->bindToHandler($realHandler, $params, $bindCacheKey, $methodName);

					}

				}

				if ($realHandler <> null && $realHandler instanceof $LazyLoader)
				{
					$methodName = $handler[1];

					$lazyReturned = $realHandler->callMethod($methodName, $params, $modelBinder);

					$returnedValue = $lazyReturned;

				}

				if (typeof($eventsManager) == "object")
				{
					if ($eventsManager->fire("micro:afterBinding", $this) === false)
					{
						return false;
					}

				}

				$afterBindingHandlers = $this->_afterBindingHandlers;

				if (typeof($afterBindingHandlers) == "array")
				{
					$this->_stopped = false;

					foreach ($afterBindingHandlers as $afterBinding) {
						if (typeof($afterBinding) == "object" && $afterBinding instanceof $MiddlewareInterface)
						{
							$status = $afterBinding->call($this);

							if ($this->_stopped)
							{
								break;

							}

							continue;

						}
						if (!(is_callable($afterBinding)))
						{
							throw new Exception("'afterBinding' handler is not callable");
						}
						$status = call_user_func($afterBinding);
						if ($this->_stopped)
						{
							break;

						}
					}

					if ($this->_stopped)
					{
						return $status;
					}

				}

				$this->_returnedValue = $returnedValue;

				if (typeof($eventsManager) == "object")
				{
					$eventsManager->fire("micro:afterExecuteRoute", $this);

				}

				$afterHandlers = $this->_afterHandlers;

				if (typeof($afterHandlers) == "array")
				{
					$this->_stopped = false;

					foreach ($afterHandlers as $after) {
						if (typeof($after) == "object")
						{
							if ($after instanceof $MiddlewareInterface)
							{
								$status = $after->call($this);

								if ($this->_stopped)
								{
									break;

								}

								continue;

							}

						}
						if (!(is_callable($after)))
						{
							throw new Exception("One of the 'after' handlers is not callable");
						}
						$status = call_user_func($after);
						if ($this->_stopped)
						{
							break;

						}
					}

				}

			}
			if (typeof($eventsManager) == "object")
			{
				$eventsManager->fire("micro:afterHandleRoute", $this, $returnedValue);

			}
			$finishHandlers = $this->_finishHandlers;
			if (typeof($finishHandlers) == "array")
			{
				$this->_stopped = false;

				$params = null;

				foreach ($finishHandlers as $finish) {
					if (typeof($finish) == "object")
					{
						if ($finish instanceof $MiddlewareInterface)
						{
							$status = $finish->call($this);

							if ($this->_stopped)
							{
								break;

							}

							continue;

						}

					}
					if (!(is_callable($finish)))
					{
						throw new Exception("One of the 'finish' handlers is not callable");
					}
					if ($params === null)
					{
						$params = [$this];

					}
					$status = call_user_func_array($finish, $params);
					if ($this->_stopped)
					{
						break;

					}
				}

			}
		} catch (\Exception $e) {
			$eventsManager = $this->_eventsManager;
			if (typeof($eventsManager) == "object")
			{
				$returnedValue = $eventsManager->fire("micro:beforeException", $this, $e);

			}
			$errorHandler = $this->_errorHandler;
			if ($errorHandler)
			{
				if (!(is_callable($errorHandler)))
				{
					throw new Exception("Error handler is not callable");
				}

				$returnedValue = call_user_func_array($errorHandler, [$e]);

				if (typeof($returnedValue) == "object")
				{
					if (!($returnedValue instanceof $ResponseInterface))
					{
						throw $e;
					}

				}

			}

		}
		if (typeof($returnedValue) == "string")
		{
			$response = $dependencyInjector->getShared("response");

			if (!($response->isSent()))
			{
				$response->setContent($returnedValue);

				$response->send();

			}

		}

		if (typeof($returnedValue) == "object")
		{
			if ($returnedValue instanceof $ResponseInterface)
			{
				if (!($returnedValue->isSent()))
				{
					$returnedValue->send();

				}

			}

		}

		return $returnedValue;
	}

	public function stop()
	{
		$this->_stopped = true;

	}

	public function setActiveHandler($activeHandler)
	{
		$this->_activeHandler = $activeHandler;

	}

	public function getActiveHandler()
	{
		return $this->_activeHandler;
	}

	public function getReturnedValue()
	{
		return $this->_returnedValue;
	}

	public function offsetExists($alias)
	{
		return $this->hasService($alias);
	}

	public function offsetSet($alias, $definition)
	{
		$this->setService($alias, $definition);

	}

	public function offsetGet($alias)
	{
		return $this->getService($alias);
	}

	public function offsetUnset($alias)
	{
		return $alias;
	}

	public function before($handler)
	{
		$this->_beforeHandlers[] = $handler;

		return $this;
	}

	public function afterBinding($handler)
	{
		$this->_afterBindingHandlers[] = $handler;

		return $this;
	}

	public function after($handler)
	{
		$this->_afterHandlers[] = $handler;

		return $this;
	}

	public function finish($handler)
	{
		$this->_finishHandlers[] = $handler;

		return $this;
	}

	public function getHandlers()
	{
		return $this->_handlers;
	}

	public function getModelBinder()
	{
		return $this->_modelBinder;
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

		$this->_modelBinder = $modelBinder;

		return $this;
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


}