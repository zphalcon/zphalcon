<?php
namespace Phalcon\Cli;

use Phalcon\DiInterface;
use Phalcon\Cli\Router\Route;
use Phalcon\Cli\Router\Exception;

class Router implements \Phalcon\Di\InjectionAwareInterface
{
	protected $_dependencyInjector;
	protected $_module;
	protected $_task;
	protected $_action;
	protected $_params = [];
	protected $_defaultModule = null;
	protected $_defaultTask = null;
	protected $_defaultAction = null;
	protected $_defaultParams = [];
	protected $_routes;
	protected $_matchedRoute;
	protected $_matches;
	protected $_wasMatched = false;

	public function __construct($defaultRoutes = true)
	{

		$routes = [];

		if ($defaultRoutes === true)
		{
			$routes = new Route("#^(?::delimiter)?([a-zA-Z0-9\\_\\-]+)[:delimiter]{0,1}$#", ["task" => 1]);

			$routes = new Route("#^(?::delimiter)?([a-zA-Z0-9\\_\\-]+):delimiter([a-zA-Z0-9\\.\\_]+)(:delimiter.*)*$#", ["task" => 1, "action" => 2, "params" => 3]);

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

	public function setDefaultModule($moduleName)
	{
		$this->_defaultModule = $moduleName;

	}

	public function setDefaultTask($taskName)
	{
		$this->_defaultTask = $taskName;

	}

	public function setDefaultAction($actionName)
	{
		$this->_defaultAction = $actionName;

	}

	public function setDefaults($defaults)
	{

		if (function() { if(isset($defaults["module"])) {$module = $defaults["module"]; return $module; } else { return false; } }())
		{
			$this->_defaultModule = $module;

		}

		if (function() { if(isset($defaults["task"])) {$task = $defaults["task"]; return $task; } else { return false; } }())
		{
			$this->_defaultTask = $task;

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

	public function handle($arguments = null)
	{

		$routeFound = false;
		$parts = [];
		$params = [];
		$matches = null;
		$this->_wasMatched = false;
		$this->_matchedRoute = null;

		if (typeof($arguments) <> "array")
		{
			if (typeof($arguments) <> "string" && typeof($arguments) <> "null")
			{
				throw new Exception("Arguments must be an array or string");
			}

			foreach ($this->_routes as $route) {
				$pattern = $route->getCompiledPattern();
				if (memstr($pattern, "^"))
				{
					$routeFound = preg_match($pattern, $arguments, $matches);

				}
				if ($routeFound)
				{
					$beforeMatch = $route->getBeforeMatch();

					if ($beforeMatch !== null)
					{
						if (!(is_callable($beforeMatch)))
						{
							throw new Exception("Before-Match callback is not callable in matched route");
						}

						$routeFound = call_user_func_array($beforeMatch, [$arguments, $route, $this]);

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

		}

		$moduleName = null;
		$taskName = null;
		$actionName = null;

		if (function() { if(isset($parts["module"])) {$moduleName = $parts["module"]; return $moduleName; } else { return false; } }())
		{
			unset($parts["module"]);

		}

		if (function() { if(isset($parts["task"])) {$taskName = $parts["task"]; return $taskName; } else { return false; } }())
		{
			unset($parts["task"]);

		}

		if (function() { if(isset($parts["action"])) {$actionName = $parts["action"]; return $actionName; } else { return false; } }())
		{
			unset($parts["action"]);

		}

		if (function() { if(isset($parts["params"])) {$params = $parts["params"]; return $params; } else { return false; } }())
		{
			if (typeof($params) <> "array")
			{
				$strParams = substr((string) $params, 1);

				if ($strParams)
				{
					$params = explode(Route::getDelimiter(), $strParams);

				}

			}

			unset($parts["params"]);

		}

		if (count($params))
		{
			$params = array_merge($params, $parts);

		}

		$this->_module = $moduleName;
		$this->_task = $taskName;
		$this->_action = $actionName;
		$this->_params = $params;

	}

	public function add($pattern, $paths = null)
	{

		$route = new Route($pattern, $paths);
		$this->_routes[] = $route;

		return $route;
	}

	public function getModuleName()
	{
		return $this->_module;
	}

	public function getTaskName()
	{
		return $this->_task;
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

		foreach ($this->_routes as $route) {
			if ($route->getRouteId() == $id)
			{
				return $route;
			}
		}

		return false;
	}

	public function getRouteByName($name)
	{

		foreach ($this->_routes as $route) {
			if ($route->getName() == $name)
			{
				return $route;
			}
		}

		return false;
	}


}