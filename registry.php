<?php
namespace Phalcon;

final 
class Registry implements \ArrayAccess, \Countable, \Iterator
{
	protected $_data;

	public final function __construct()
	{
		$this->_data = [];

	}

	public final function offsetExists($offset)
	{
		return isset($this->_data[$offset]);
	}

	public final function offsetGet($offset)
	{
		return $this->_data[$offset];
	}

	public final function offsetSet($offset, $value)
	{
		$this[$offset] = $value;

	}

	public final function offsetUnset($offset)
	{
		unset($this->_data[$offset]);

	}

	public final function count()
	{
		return count($this->_data);
	}

	public final function next()
	{
		next($this->_data);

	}

	public final function key()
	{
		return key($this->_data);
	}

	public final function rewind()
	{
		reset($this->_data);

	}

	public function valid()
	{
		return key($this->_data) !== null;
	}

	public function current()
	{
		return current($this->_data);
	}

	public final function __set($key, $value)
	{
		$this->offsetSet($key, $value);

	}

	public final function __get($key)
	{
		return $this->offsetGet($key);
	}

	public final function __isset($key)
	{
		return $this->offsetExists($key);
	}

	public final function __unset($key)
	{
		$this->offsetUnset($key);

	}


}