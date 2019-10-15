<?php
namespace Phalcon\Cli\Router;


class Route
{
	const DEFAULT_DELIMITER = " ";

	protected $_pattern;
	protected $_compiledPattern;
	protected $_paths;
	protected $_converters;
	protected $_id;
	protected $_name;
	protected $_beforeMatch;
	protected $_delimiter;
	protected static $_uniqueId;
	protected static $_delimiterPath;

	public function __construct($pattern, $paths = null)
	{

		$delimiter = self::_delimiterPath;

		if (!($delimiter))
		{
			$delimiter = self::DEFAULT_DELIMITER;

		}

		$this->_delimiter = $delimiter;

		$this->reConfigure($pattern, $paths);

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
			$idPattern = $this->_delimiter . "([a-zA-Z0-9\\_\\-]+)";

			if (memstr($pattern, ":delimiter"))
			{
				$pattern = str_replace(":delimiter", $this->_delimiter, $pattern);

			}

			$part = $this->_delimiter . ":module";

			if (memstr($pattern, $part))
			{
				$pattern = str_replace($part, $idPattern, $pattern);

			}

			$part = $this->_delimiter . ":task";

			if (memstr($pattern, $part))
			{
				$pattern = str_replace($part, $idPattern, $pattern);

			}

			$part = $this->_delimiter . ":namespace";

			if (memstr($pattern, $part))
			{
				$pattern = str_replace($part, $idPattern, $pattern);

			}

			$part = $this->_delimiter . ":action";

			if (memstr($pattern, $part))
			{
				$pattern = str_replace($part, $idPattern, $pattern);

			}

			$part = $this->_delimiter . ":params";

			if (memstr($pattern, $part))
			{
				$pattern = str_replace($part, "(" . $this->_delimiter . ".*)*", $pattern);

			}

			$part = $this->_delimiter . ":int";

			if (memstr($pattern, $part))
			{
				$pattern = str_replace($part, $this->_delimiter . "([0-9]+)", $pattern);

			}

		}

		if (memstr($pattern, "("))
		{
			return "#^" . $pattern . "$#";
		}

		if (memstr($pattern, "["))
		{
			return "#^" . $pattern . "$#";
		}

		return $pattern;
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

		if ($paths !== null)
		{
			if (typeof($paths) == "string")
			{
				$moduleName = null;
				$taskName = null;
				$actionName = null;

				$parts = explode("::", $paths);

				switch (count($parts)) {
					case 3:
						$moduleName = $parts[0];
						$taskName = $parts[1];
						$actionName = $parts[2];
						break;
					case 2:
						$taskName = $parts[0];
						$actionName = $parts[1];
						break;
					case 1:
						$taskName = $parts[0];
						break;

				}

				$routePaths = [];

				if ($moduleName !== null)
				{
					$routePaths["module"] = $moduleName;

				}

				if ($taskName !== null)
				{
					if (memstr($taskName, "\\"))
					{
						$realClassName = get_class_ns($taskName);

						$namespaceName = get_ns_class($taskName);

						if ($namespaceName)
						{
							$routePaths["namespace"] = $namespaceName;

						}

					}

					$routePaths["task"] = uncamelize($realClassName);

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

	public static function delimiter($delimiter = null)
	{
		self::_delimiterPath = $delimiter;

	}

	public static function getDelimiter()
	{

		$delimiter = self::_delimiterPath;

		if (!($delimiter))
		{
			$delimiter = self::DEFAULT_DELIMITER;

		}

		return $delimiter;
	}


}