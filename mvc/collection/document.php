<?php
namespace Phalcon\Mvc\Collection;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Collection\Exception;

class Document implements EntityInterface, \ArrayAccess
{
	public function offsetExists($index)
	{
		return isset($this->$index);
	}

	public function offsetGet($index)
	{

		if (function() { if(isset($this->$index)) {$value = $this->$index; return $value; } else { return false; } }())
		{
			return $value;
		}

		throw new Exception("The index does not exist in the row");
	}

	public function offsetSet($index, $value)
	{
		$this->{$index} = $value;

	}

	public function offsetUnset($offset)
	{
		throw new Exception("The index does not exist in the row");
	}

	public function readAttribute($attribute)
	{

		if (function() { if(isset($this->$attribute)) {$value = $this->$attribute; return $value; } else { return false; } }())
		{
			return $value;
		}

		return null;
	}

	public function writeAttribute($attribute, $value)
	{
		$this->{$attribute} = $value;

	}

	public function toArray()
	{
		return get_object_vars($this);
	}


}