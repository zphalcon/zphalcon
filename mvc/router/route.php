<?php
namespace Phalcon\Mvc\Router;

use Phalcon\Mvc\Router\Exception;

class Route implements RouteInterface
{
	protected $_pattern;
	protected $_compiledPattern;
	protected $_paths;
	protected $_methods;
	protected $_hostname;
	protected $_converters;
	protected $_id;
	protected $_name;
	protected $_beforeMatch;
	protected $_match;
	protected $_group;
	protected static $_uniqueId;

	public function __construct($pattern, $paths = null, $httpMethods = null)
	{

		$this->reConfigure($pattern, $paths);

		$this->_methods = $httpMethods;

		$uniqueId = self::_uniqueId;

		if ($uniqueId === null)
		{
			$uniqueId = 0;

		}

		$routeId = $uniqueId;
		$this->_id = $routeId;
		self::_uniqueId = $uniqueId + 1;

	}

	public function compilePattern($pattern)
	{

		if (memstr($pattern, ":"))
		{
			$idPattern = "/([\\w0-9\\_\\-]+)";

			if (memstr($pattern, "/:module"))
			{
				$pattern = str_replace("/:module", $idPattern, $pattern);

			}

			if (memstr($pattern, "/:controller"))
			{
				$pattern = str_replace("/:controller", $idPattern, $pattern);

			}

			if (memstr($pattern, "/:namespace"))
			{
				$pattern = str_replace("/:namespace", $idPattern, $pattern);

			}

			if (memstr($pattern, "/:action"))
			{
				$pattern = str_replace("/:action", $idPattern, $pattern);

			}

			if (memstr($pattern, "/:params"))
			{
				$pattern = str_replace("/:params", "(/.*)*", $pattern);

			}

			if (memstr($pattern, "/:int"))
			{
				$pattern = str_replace("/:int", "/([0-9]+)", $pattern);

			}

		}

		if (memstr($pattern, "("))
		{
			return "#^" . $pattern . "$#u";
		}

		if (memstr($pattern, "["))
		{
			return "#^" . $pattern . "$#u";
		}

		return $pattern;
	}

	public function via($httpMethods)
	{
		$this->_methods = $httpMethods;

		return $this;
	}

	public function extractNamedParams($pattern)
	{






		if (strlen($pattern) <= 0)
		{
			return false;
		}

		$matches = [];
		$route = "";

		foreach ($pattern as $cursor => $ch) {
			if ($parenthesesCount == 0)
			{
				if ($ch == "{")
				{
					if ($bracketCount == 0)
					{
						$marker = $cursor + 1;
						$intermediate = 0;
						$notValid = false;

					}

					$bracketCount++;

				}

			}
			if ($bracketCount == 0)
			{
				if ($ch == "(")
				{
					$parenthesesCount++;

				}

			}
			if ($bracketCount > 0)
			{
				$intermediate++;

			}
		}

		return [$route, $matches];
	}

	public function reConfigure($pattern, $paths = null)
	{

		$routePaths = self::getRoutePaths($paths);

		if (!(starts_with($pattern, "#")))
		{
			if (memstr($pattern, "{"))
			{
				$extracted = $this->extractNamedParams($pattern);
				$pcrePattern = $extracted[0];
				$routePaths = array_merge($routePaths, $extracted[1]);

			}

			$compiledPattern = $this->compilePattern($pcrePattern);

		}

		$this->_pattern = $pattern;

		$this->_compiledPattern = $compiledPattern;

		$this->_paths = $routePaths;

	}

	public static function getRoutePaths($paths = null)
	{

		if ($paths !== null)
		{
			if (typeof($paths) == "string")
			{
				$moduleName = null;
				$controllerName = null;
				$actionName = null;

				$parts = explode("::", $paths);

				switch (count($parts)) {
					case 3:
						$moduleName = $parts[0];
						$controllerName = $parts[1];
						$actionName = $parts[2];
						break;
					case 2:
						$controllerName = $parts[0];
						$actionName = $parts[1];
						break;
					case 1:
						$controllerName = $parts[0];
						break;

				}

				$routePaths = [];

				if ($moduleName !== null)
				{
					$routePaths["module"] = $moduleName;

				}

				if ($controllerName !== null)
				{
					if (memstr($controllerName, "\\"))
					{
						$realClassName = get_class_ns($controllerName);

						$namespaceName = get_ns_class($controllerName);

						if ($namespaceName)
						{
							$routePaths["namespace"] = $namespaceName;

						}

					}

					$routePaths["controller"] = uncamelize($realClassName);

				}

				if ($actionName !== null)
				{
					$routePaths["action"] = $actionName;

				}

			}

		}

		if (typeof($routePaths) !== "array")
		{
			throw new Exception("The route contains invalid paths");
		}

		return $routePaths;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function setName($name)
	{
		$this->_name = $name;

		return $this;
	}

	public function beforeMatch($callback)
	{
		$this->_beforeMatch = $callback;

		return $this;
	}

	public function getBeforeMatch()
	{
		return $this->_beforeMatch;
	}

	public function match($callback)
	{
		$this->_match = $callback;

		return $this;
	}

	public function getMatch()
	{
		return $this->_match;
	}

	public function getRouteId()
	{
		return $this->_id;
	}

	public function getPattern()
	{
		return $this->_pattern;
	}

	public function getCompiledPattern()
	{
		return $this->_compiledPattern;
	}

	public function getPaths()
	{
		return $this->_paths;
	}

	public function getReversedPaths()
	{

		$reversed = [];

		foreach ($this->_paths as $path => $position) {
			$reversed[$position] = $path;
		}

		return $reversed;
	}

	public function setHttpMethods($httpMethods)
	{
		$this->_methods = $httpMethods;

		return $this;
	}

	public function getHttpMethods()
	{
		return $this->_methods;
	}

	public function setHostname($hostname)
	{
		$this->_hostname = $hostname;

		return $this;
	}

	public function getHostname()
	{
		return $this->_hostname;
	}

	public function setGroup($group)
	{
		$this->_group = $group;

		return $this;
	}

	public function getGroup()
	{
		return $this->_group;
	}

	public function convert($name, $converter)
	{
		$this[$name] = $converter;

		return $this;
	}

	public function getConverters()
	{
		return $this->_converters;
	}

	public static function reset()
	{
		self::_uniqueId = null;

	}


}