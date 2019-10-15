<?php
namespace Phalcon\Session;

use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;

class Bag implements InjectionAwareInterface, BagInterface, \IteratorAggregate, \ArrayAccess, \Countable
{
	protected $_dependencyInjector;
	protected $_name = null;
	protected $_data;
	protected $_initialized = false;
	protected $_session;

	public function __construct($name)
	{
		$this->_name = $name;

	}

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	public function initialize()
	{

		$session = $this->_session;

		if (typeof($session) <> "object")
		{
			$dependencyInjector = $this->_dependencyInjector;

			if (typeof($dependencyInjector) <> "object")
			{
				$dependencyInjector = Di::getDefault();

				if (typeof($dependencyInjector) <> "object")
				{
					throw new Exception("A dependency injection object is required to access the 'session' service");
				}

			}

			$session = $dependencyInjector->getShared("session");
			$this->_session = $session;

		}

		$data = $session->get($this->_name);

		if (typeof($data) <> "array")
		{
			$data = [];

		}

		$this->_data = $data;

		$this->_initialized = true;

	}

	public function destroy()
	{
		if ($this->_initialized === false)
		{
			$this->initialize();

		}

		$this->_data = [];

		$this->_session->remove($this->_name);

	}

	public function set($property, $value)
	{
		if ($this->_initialized === false)
		{
			$this->initialize();

		}

		$this[$property] = $value;

		$this->_session->set($this->_name, $this->_data);

	}

	public function __set($property, $value)
	{
		$this->set($property, $value);

	}

	public function get($property, $defaultValue = null)
	{

		if ($this->_initialized === false)
		{
			$this->initialize();

		}

		if (function() { if(isset($this->_data[$property])) {$value = $this->_data[$property]; return $value; } else { return false; } }())
		{
			return $value;
		}

		return $defaultValue;
	}

	public function __get($property)
	{
		return $this->get($property);
	}

	public function has($property)
	{
		if ($this->_initialized === false)
		{
			$this->initialize();

		}

		return isset($this->_data[$property]);
	}

	public function __isset($property)
	{
		return $this->has($property);
	}

	public function remove($property)
	{
		if ($this->_initialized === false)
		{
			$this->initialize();

		}


		$data = $this->_data;

		if (isset($data[$property]))
		{
			unset($data[$property]);

			$this->_session->set($this->_name, $data);

			$this->_data = $data;

			return true;
		}

		return false;
	}

	public function __unset($property)
	{
		return $this->remove($property);
	}

	public final function count()
	{
		if ($this->_initialized === false)
		{
			$this->initialize();

		}

		return count($this->_data);
	}

	public final function getIterator()
	{
		if ($this->_initialized === false)
		{
			$this->initialize();

		}

		return new \ArrayIterator($this->_data);
	}

	public final function offsetSet($property, $value)
	{
		return $this->set($property, $value);
	}

	public final function offsetExists($property)
	{
		return $this->has($property);
	}

	public final function offsetUnset($property)
	{
		return $this->remove($property);
	}

	public final function offsetGet($property)
	{
		return $this->get($property);
	}


}