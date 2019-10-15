<?php
namespace Phalcon\Mvc\Micro;


class Collection implements CollectionInterface
{
	protected $_prefix;
	protected $_lazy;
	protected $_handler;
	protected $_handlers;

	protected function _addMap($method, $routePattern, $handler, $name)
	{
		$this->_handlers[] = [$method, $routePattern, $handler, $name];

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

	public function getHandlers()
	{
		return $this->_handlers;
	}

	public function setHandler($handler, $lazy = false)
	{
		$this->_handler = $handler;
		$this->_lazy = $lazy;

		return $this;
	}

	public function setLazy($lazy)
	{
		$this->_lazy = $lazy;

		return $this;
	}

	public function isLazy()
	{
		return $this->_lazy;
	}

	public function getHandler()
	{
		return $this->_handler;
	}

	public function map($routePattern, $handler, $name = null)
	{
		$this->_addMap(null, $routePattern, $handler, $name);

		return $this;
	}

	public function mapVia($routePattern, $handler, $method, $name = null)
	{
		$this->_addMap($method, $routePattern, $handler, $name);

		return $this;
	}

	public function get($routePattern, $handler, $name = null)
	{
		$this->_addMap("GET", $routePattern, $handler, $name);

		return $this;
	}

	public function post($routePattern, $handler, $name = null)
	{
		$this->_addMap("POST", $routePattern, $handler, $name);

		return $this;
	}

	public function put($routePattern, $handler, $name = null)
	{
		$this->_addMap("PUT", $routePattern, $handler, $name);

		return $this;
	}

	public function patch($routePattern, $handler, $name = null)
	{
		$this->_addMap("PATCH", $routePattern, $handler, $name);

		return $this;
	}

	public function head($routePattern, $handler, $name = null)
	{
		$this->_addMap("HEAD", $routePattern, $handler, $name);

		return $this;
	}

	public function delete($routePattern, $handler, $name = null)
	{
		$this->_addMap("DELETE", $routePattern, $handler, $name);

		return $this;
	}

	public function options($routePattern, $handler, $name = null)
	{
		$this->_addMap("OPTIONS", $routePattern, $handler, $name);

		return $this;
	}


}