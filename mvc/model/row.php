<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\ResultInterface;

class Row implements EntityInterface, ResultInterface, \ArrayAccess, \JsonSerializable
{
	public function setDirtyState($dirtyState)
	{
		return false;
	}

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
		throw new Exception("Row is an immutable ArrayAccess object");
	}

	public function offsetUnset($offset)
	{
		throw new Exception("Row is an immutable ArrayAccess object");
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

	public function jsonSerialize()
	{
		return $this->toArray();
	}


}