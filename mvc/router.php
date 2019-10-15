<?php
namespace Phalcon\Mvc;

use Phalcon\DiInterface;
use Phalcon\Mvc\Router\Route;
use Phalcon\Mvc\Router\Exception;
use Phalcon\Http\RequestInterface;
use Phalcon\Mvc\Router\GroupInterface;
use Phalcon\Mvc\Router\RouteInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Events\EventsAwareInterface;

class Router implements InjectionAwareInterface, RouterInterface, EventsAwareInterface
{
	const URI_SOURCE_GET_URL = 0;
	const URI_SOURCE_SERVER_REQUEST_URI = 1;
	const POSITION_FIRST = 0;
	const POSITION_LAST = 1;

	protected $_dependencyInjector;
	protected $_eventsManager;
	protected $_uriSource;
	protected $_namespace = null;
	protected $_module = null;
	protected $_controller = null;
	protected $_action = null;
	protected $_params = [];
	protected $_routes;
	protected $_matchedRoute;
	protected $_matches;
	protected $_wasMatched = false;
	protected $_defaultNamespace;
	protected $_defaultModule;
	protected $_defaultController;
	protected $_defaultAction;
	protected $_defaultParams = [];
	protected $_removeExtraSlashes;
	protected $_notFoundPaths;
	protected $_keyRouteNames = [];
	protected $_keyRouteIds = [];

	public function __construct($defaultRoutes = true)
	{

		if ($defaultRoutes)
		{
			$routes = new Route("#^/([\\w0-9\\_\\-]+)[/]{0,1}$#u", ["controller" => 1]);

			$routes = new Route("#^/([\\w0-9\\_\\-]+)/([\\w0-9\\.\\_]+)(/.*)*$#u", ["controller" => 1, "action" => 2, "params" => 3]);

		}

		$this->_routes = $routes;

	}

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

	public function getRewriteUri()
	{

		if (!($this->_uriSource))
		{
			if (function() { if(isset($_GET["_url"])) {$url = $_GET["_url"]; return $url; } else { return false; } }())
			{
				if (!(empty($url)))
				{
					return $url;
				}

			}

		}

		return "/";
	}

	public function setUriSource($uriSource)
	{
		$this->_uriSource = $uriSource;

		return $this;
	}

	public function removeExtraSlashes($remove)
	{
		$this->_removeExtraSlashes = $remove;

		return $this;
	}

	public function setDefaultNamespace($namespaceName)
	{
		$this->_defaultNamespace = $namespaceName;

		return $this;
	}

	public function setDefaultModule($moduleName)
	{
		$this->_defaultModule = $moduleName;

		return $this;
	}

	public function setDefaultController($controllerName)
	{
		$this->_defaultController = $controllerName;

		return $this;
	}

	public function setDefaultAction($actionName)
	{
		$this->_defaultAction = $actionName;

		return $this;
	}

	public function setDefaults($defaults)
	{

		if (function() { if(isset($defaults["namespace"])) {$namespaceName = $defaults["namespace"]; return $namespaceName; } else { return false; } }())
		{
			$this->_defaultNamespace = $namespaceName;

		}

		if (function() { if(isset($defaults["module"])) {$module = $defaults["module"]; return $module; } else { return false; } }())
		{
			$this->_defaultModule = $module;

		}

		if (function() { if(isset($defaults["controller"])) {$controller = $defaults["controller"]; return $controller; } else { return false; } }())
		{
			$this->_defaultController = $controller;

		}

		if (function() { if(isset($defaults["action"])) {$action = $defaults["action"]; return $action; } else { return false; } }())
		{
			$this->_defaultAction = $action;

		}

		if (function() { if(isset($defaults["params"])) {$params = $defaults["params"]; return $params; } else { return false; } }())
		{
			$this->_defaultParams = $params;

		}

		return $this;
	}

	public function getDefaults()
	{
		return ["namespace" => $this->_defaultNamespace, "module" => $this->_defaultModule, "controller" => $this->_defaultController, "action" => $this->_defaultAction, "params" => $this->_defaultParams];
	}

	public function handle($uri = null)
	{

		if (!($uri))
		{
			$realUri = $this->getRewriteUri();

		}

		if ($this->_removeExtraSlashes && $realUri <> "/")
		{
			$handledUri = rtrim($realUri, "/");

		}

		$request = null;
		$currentHostName = null;
		$routeFound = false;
		$parts = [];
		$params = [];
		$matches = null;
		$this->_wasMatched = false;
		$this->_matchedRoute = null;

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("router:beforeCheckRoutes", $this);

		}

		foreach ($this->_routes as $route) {
			$params = [];
			$matches = null;
			$methods = $route->getHttpMethods();
			if ($methods !== null)
			{
				if ($request === null)
				{
					$dependencyInjector = $this->_dependencyInjector;

					if (typeof($dependencyInjector) <> "object")
					{
						throw new Exception("A dependency injection container is required to access the 'request' service");
					}

					$request = $dependencyInjector->getShared("request");

				}

				if ($request->isMethod($methods, true) === false)
				{
					continue;

				}

			}
			$hostname = $route->getHostName();
			if ($hostname !== null)
			{
				if ($request === null)
				{
					$dependencyInjector = $this->_dependencyInjector;

					if (typeof($dependencyInjector) <> "object")
					{
						throw new Exception("A dependency injection container is required to access the 'request' service");
					}

					$request = $dependencyInjector->getShared("request");

				}

				if (typeof($currentHostName) == "null")
				{
					$currentHostName = $request->getHttpHost();

				}

				if (!($currentHostName))
				{
					continue;

				}

				if (memstr($hostname, "("))
				{
					if (!(memstr($hostname, "#")))
					{
						$regexHostName = "#^" . $hostname;

						if (!(memstr($hostname, ":")))
						{
							$regexHostName .= "(:[[:digit:]]+)?";

						}

						$regexHostName .= "$#i";

					}

					$matched = preg_match($regexHostName, $currentHostName);

				}

				if (!($matched))
				{
					continue;

				}

			}
			if (typeof($eventsManager) == "object")
			{
				$eventsManager->fire("router:beforeCheckRoute", $this, $route);

			}
			$pattern = $route->getCompiledPattern();
			if (memstr($pattern, "^"))
			{
				$routeFound = preg_match($pattern, $handledUri, $matches);

			}
			if ($routeFound)
			{
				if (typeof($eventsManager) == "object")
				{
					$eventsManager->fire("router:matchedRoute", $this, $route);

				}

				$beforeMatch = $route->getBeforeMatch();

				if ($beforeMatch !== null)
				{
					if (!(is_callable($beforeMatch)))
					{
						throw new Exception("Before-Match callback is not callable in matched route");
					}

					$routeFound = call_user_func_array($beforeMatch, [$handledUri, $route, $this]);

				}

			}
			if ($routeFound)
			{
				$paths = $route->getPaths();
				$parts = $paths;

				if (typeof($matches) == "array")
				{
					$converters = $route->getConverters();

					foreach ($paths as $part => $position) {
						if (typeof($part) <> "string")
						{
							throw new Exception("Wrong key in paths: " . $part);
						}
						if (typeof($position) <> "string" && typeof($position) <> "integer")
						{
							continue;

						}
						if (function() { if(isset($matches[$position])) {$matchPosition = $matches[$position]; return $matchPosition; } else { return false; } }())
						{
							if (typeof($converters) == "array")
							{
								if (function() { if(isset($converters[$part])) {$converter = $converters[$part]; return $converter; } else { return false; } }())
								{
									$parts[$part] = call_user_func_array($converter, [$matchPosition]);

									continue;

								}

							}

							$parts[$part] = $matchPosition;

						}
					}

					$this->_matches = $matches;

				}

				$this->_matchedRoute = $route;

				break;

			}
		}

		if ($routeFound)
		{
			$this->_wasMatched = true;

		}

		if (!($routeFound))
		{
			$notFoundPaths = $this->_notFoundPaths;

			if ($notFoundPaths !== null)
			{
				$parts = Route::getRoutePaths($notFoundPaths);
				$routeFound = true;

			}

		}

		$this->_namespace = $this->_defaultNamespace;
		$this->_module = $this->_defaultModule;
		$this->_controller = $this->_defaultController;
		$this->_action = $this->_defaultAction;
		$this->_params = $this->_defaultParams;

		if ($routeFound)
		{
			if (function() { if(isset($parts["namespace"])) {$vnamespace = $parts["namespace"]; return $vnamespace; } else { return false; } }())
			{
				if (!(is_numeric($vnamespace)))
				{
					$this->_namespace = $vnamespace;

				}

				unset($parts["namespace"]);

			}

			if (function() { if(isset($parts["module"])) {$module = $parts["module"]; return $module; } else { return false; } }())
			{
				if (!(is_numeric($module)))
				{
					$this->_module = $module;

				}

				unset($parts["module"]);

			}

			if (function() { if(isset($parts["controller"])) {$controller = $parts["controller"]; return $controller; } else { return false; } }())
			{
				if (!(is_numeric($controller)))
				{
					$this->_controller = $controller;

				}

				unset($parts["controller"]);

			}

			if (function() { if(isset($parts["action"])) {$action = $parts["action"]; return $action; } else { return false; } }())
			{
				if (!(is_numeric($action)))
				{
					$this->_action = $action;

				}

				unset($parts["action"]);

			}

			if (function() { if(isset($parts["params"])) {$paramsStr = $parts["params"]; return $paramsStr; } else { return false; } }())
			{
				if (typeof($paramsStr) == "string")
				{
					$strParams = trim($paramsStr, "/");

					if ($strParams !== "")
					{
						$params = explode("/", $strParams);

					}

				}

				unset($parts["params"]);

			}

			if (count($params))
			{
				$this->_params = array_merge($params, $parts);

			}

		}

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("router:afterCheckRoutes", $this);

		}

	}

	public function attach($route, $position = Router::POSITION_LAST)
	{
		switch ($position) {
			case self::POSITION_LAST:
				$this->_routes[] = $route;
				break;
			case self::POSITION_FIRST:
				$this->_routes = array_merge([$route], $this->_routes);
				break;
			default:
				throw new Exception("Invalid route position");
		}

		return $this;
	}

	public function add($pattern, $paths = null, $httpMethods = null, $position = Router::POSITION_LAST)
	{

		$route = new Route($pattern, $paths, $httpMethods);

		$this->attach($route, $position);

		return $route;
	}

	public function addGet($pattern, $paths = null, $position = Router::POSITION_LAST)
	{
		return $this->add($pattern, $paths, "GET", $position);
	}

	public function addPost($pattern, $paths = null, $position = Router::POSITION_LAST)
	{
		return $this->add($pattern, $paths, "POST", $position);
	}

	public function addPut($pattern, $paths = null, $position = Router::POSITION_LAST)
	{
		return $this->add($pattern, $paths, "PUT", $position);
	}

	public function addPatch($pattern, $paths = null, $position = Router::POSITION_LAST)
	{
		return $this->add($pattern, $paths, "PATCH", $position);
	}

	public function addDelete($pattern, $paths = null, $position = Router::POSITION_LAST)
	{
		return $this->add($pattern, $paths, "DELETE", $position);
	}

	public function addOptions($pattern, $paths = null, $position = Router::POSITION_LAST)
	{
		return $this->add($pattern, $paths, "OPTIONS", $position);
	}

	public function addHead($pattern, $paths = null, $position = Router::POSITION_LAST)
	{
		return $this->add($pattern, $paths, "HEAD", $position);
	}

	public function addPurge($pattern, $paths = null, $position = Router::POSITION_LAST)
	{
		return $this->add($pattern, $paths, "PURGE", $position);
	}

	public function addTrace($pattern, $paths = null, $position = Router::POSITION_LAST)
	{
		return $this->add($pattern, $paths, "TRACE", $position);
	}

	public function addConnect($pattern, $paths = null, $position = Router::POSITION_LAST)
	{
		return $this->add($pattern, $paths, "CONNECT", $position);
	}

	public function mount($group)
	{

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("router:beforeMount", $this, $group);

		}

		$groupRoutes = $group->getRoutes();

		if (!(count($groupRoutes)))
		{
			throw new Exception("The group of routes does not contain any routes");
		}

		$beforeMatch = $group->getBeforeMatch();

		if ($beforeMatch !== null)
		{
			foreach ($groupRoutes as $route) {
				$route->beforeMatch($beforeMatch);
			}

		}

		$hostname = $group->getHostName();

		if ($hostname !== null)
		{
			foreach ($groupRoutes as $route) {
				$route->setHostName($hostname);
			}

		}

		$routes = $this->_routes;

		if (typeof($routes) == "array")
		{
			$this->_routes = array_merge($routes, $groupRoutes);

		}

		return $this;
	}

	public function notFound($paths)
	{
		if (typeof($paths) <> "array" && typeof($paths) <> "string")
		{
			throw new Exception("The not-found paths must be an array or string");
		}

		$this->_notFoundPaths = $paths;

		return $this;
	}

	public function clear()
	{
		$this->_routes = [];

	}

	public function getNamespaceName()
	{
		return $this->_namespace;
	}

	public function getModuleName()
	{
		return $this->_module;
	}

	public function getControllerName()
	{
		return $this->_controller;
	}

	public function getActionName()
	{
		return $this->_action;
	}

	public function getParams()
	{
		return $this->_params;
	}

	public function getMatchedRoute()
	{
		return $this->_matchedRoute;
	}

	public function getMatches()
	{
		return $this->_matches;
	}

	public function wasMatched()
	{
		return $this->_wasMatched;
	}

	public function getRoutes()
	{
		return $this->_routes;
	}

	public function getRouteById($id)
	{

		if (function() { if(isset($this->_keyRouteIds[$id])) {$key = $this->_keyRouteIds[$id]; return $key; } else { return false; } }())
		{
			return $this->_routes[$key];
		}

		foreach ($this->_routes as $key => $route) {
			$routeId = $route->getRouteId();
			$this[$routeId] = $key;
			if ($routeId == $id)
			{
				return $route;
			}
		}

		return false;
	}

	public function getRouteByName($name)
	{

		if (function() { if(isset($this->_keyRouteNames[$name])) {$key = $this->_keyRouteNames[$name]; return $key; } else { return false; } }())
		{
			return $this->_routes[$key];
		}

		foreach ($this->_routes as $key => $route) {
			$routeName = $route->getName();
			if (!(empty($routeName)))
			{
				$this[$routeName] = $key;

				if ($routeName == $name)
				{
					return $route;
				}

			}
		}

		return false;
	}

	public function isExactControllerName()
	{
		return true;
	}


}