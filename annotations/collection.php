<?php
namespace Phalcon\Annotations;

use Phalcon\Annotations\Annotation;
use Phalcon\Annotations\Exception;

class Collection implements \Iterator, \Countable
{
	protected $_position = 0;
	protected $_annotations;

	public function __construct($reflectionData = null)
	{

		if (typeof($reflectionData) <> "null" && typeof($reflectionData) <> "array")
		{
			throw new Exception("Reflection data must be an array");
		}

		$annotations = [];

		if (typeof($reflectionData) == "array")
		{
			foreach ($reflectionData as $annotationData) {
				$annotations = new Annotation($annotationData);
			}

		}

		$this->_annotations = $annotations;

	}

	public function count()
	{
		return count($this->_annotations);
	}

	public function rewind()
	{
		$this->_position = 0;

	}

	public function current()
	{

		if (function() { if(isset($this->_annotations[$this->_position])) {$annotation = $this->_annotations[$this->_position]; return $annotation; } else { return false; } }())
		{
			return $annotation;
		}

		return false;
	}

	public function key()
	{
		return $this->_position;
	}

	public function next()
	{
		$this->_position++;
	}

	public function valid()
	{
		return isset($this->_annotations[$this->_position]);
	}

	public function getAnnotations()
	{
		return $this->_annotations;
	}

	public function get($name)
	{

		$annotations = $this->_annotations;

		if (typeof($annotations) == "array")
		{
			foreach ($annotations as $annotation) {
				if ($name == $annotation->getName())
				{
					return $annotation;
				}
			}

		}

		throw new Exception("Collection doesn't have an annotation called '" . $name . "'");
	}

	public function getAll($name)
	{

		$found = [];
		$annotations = $this->_annotations;

		if (typeof($annotations) == "array")
		{
			foreach ($annotations as $annotation) {
				if ($name == $annotation->getName())
				{
					$found = $annotation;

				}
			}

		}

		return $found;
	}

	public function has($name)
	{

		$annotations = $this->_annotations;

		if (typeof($annotations) == "array")
		{
			foreach ($annotations as $annotation) {
				if ($name == $annotation->getName())
				{
					return true;
				}
			}

		}

		return false;
	}


}