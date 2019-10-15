<?php
namespace Phalcon\Mvc\Micro;

use Phalcon\Mvc\Model\BinderInterface;

class LazyLoader
{
	protected $_handler;
	protected $_modelBinder;
	protected $_definition;

	public function __construct($definition)
	{
		$this->_definition = $definition;

	}

	public function __call($method, $arguments)
	{

		$handler = $this->_handler;

		$definition = $this->_definition;

		if (typeof($handler) <> "object")
		{
			$handler = new $definition();

			$this->_handler = $handler;

		}

		$modelBinder = $this->_modelBinder;

		if ($modelBinder <> null)
		{
			$bindCacheKey = "_PHMB_" . $definition . "_" . $method;

			$arguments = $modelBinder->bindToHandler($handler, $arguments, $bindCacheKey, $method);

		}

		return call_user_func_array([$handler, $method], $arguments);
	}

	public function callMethod($method, $arguments, $modelBinder = null)
	{
		$this->_modelBinder = $modelBinder;

		return $this->__call($method, $arguments);
	}


}