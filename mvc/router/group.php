<?php
namespace Phalcon\Mvc\Router;


class Group implements GroupInterface
{
	protected $_prefix;
	protected $_hostname;
	protected $_paths;
	protected $_routes;
	protected $_beforeMatch;

	public function __construct($paths = null)
	{
		if (typeof($paths) == "array" || typeof($paths) == "string")
		{
			$this->_paths = $paths;

		}

		if (method_exists($this, "initialize"))
		{
			$this->initialize($paths);

		}

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

	public function setPrefix($prefix)
	{
		$this->_prefix = $prefix;

		return $this;
	}

	public function getPrefix()
	{
		return $this->_prefix;
	}

	public function beforeMatch($beforeMatch)
	{
		$this->_beforeMatch = $beforeMatch;

		return $this;
	}

	public function getBeforeMatch()
	{
		return $this->_beforeMatch;
	}

	public function setPaths($paths)
	{
		$this->_paths = $paths;

		return $this;
	}

	public function getPaths()
	{
		return $this->_paths;
	}

	public function getRoutes()
	{
		return $this->_routes;
	}

	public function add($pattern, $paths = null, $httpMethods = null)
	{
		return $this->_addRoute($pattern, $paths, $httpMethods);
	}

	public function addGet($pattern, $paths = null)
	{
		return $this->_addRoute($pattern, $paths, "GET");
	}

	public function addPost($pattern, $paths = null)
	{
		return $this->_addRoute($pattern, $paths, "POST");
	}

	public function addPut($pattern, $paths = null)
	{
		return $this->_addRoute($pattern, $paths, "PUT");
	}

	public function addPatch($pattern, $paths = null)
	{
		return $this->_addRoute($pattern, $paths, "PATCH");
	}

	public function addDelete($pattern, $paths = null)
	{
		return $this->_addRoute($pattern, $paths, "DELETE");
	}

	public function addOptions($pattern, $paths = null)
	{
		return $this->_addRoute($pattern, $paths, "OPTIONS");
	}

	public function addHead($pattern, $paths = null)
	{
		return $this->_addRoute($pattern, $paths, "HEAD");
	}

	public function clear()
	{
		$this->_routes = [];

	}

	protected function _addRoute($pattern, $paths = null, $httpMethods = null)
	{

		$defaultPaths = $this->_paths;

		if (typeof($defaultPaths) == "array")
		{
			if (typeof($paths) == "string")
			{
				$processedPaths = Route::getRoutePaths($paths);

			}

			if (typeof($processedPaths) == "array")
			{
				$mergedPaths = array_merge($defaultPaths, $processedPaths);

			}

		}

		$route = new Route($this->_prefix . $pattern, $mergedPaths, $httpMethods);
		$this->_routes[] = $route;

		$route->setGroup($this);

		return $route;
	}


}