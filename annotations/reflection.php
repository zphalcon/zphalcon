<?php
namespace Phalcon\Annotations;

use Phalcon\Annotations\Collection;

class Reflection
{
	protected $_reflectionData;
	protected $_classAnnotations;
	protected $_methodAnnotations;
	protected $_propertyAnnotations;

	public function __construct($reflectionData = null)
	{
		if (typeof($reflectionData) == "array")
		{
			$this->_reflectionData = $reflectionData;

		}

	}

	public function getClassAnnotations()
	{

		$annotations = $this->_classAnnotations;

		if (typeof($annotations) <> "object")
		{
			if (function() { if(isset($this->_reflectionData["class"])) {$reflectionClass = $this->_reflectionData["class"]; return $reflectionClass; } else { return false; } }())
			{
				$collection = new Collection($reflectionClass);
				$this->_classAnnotations = $collection;

				return $collection;
			}

			$this->_classAnnotations = false;

			return false;
		}

		return $annotations;
	}

	public function getMethodsAnnotations()
	{

		$annotations = $this->_methodAnnotations;

		if (typeof($annotations) <> "object")
		{
			if (function() { if(isset($this->_reflectionData["methods"])) {$reflectionMethods = $this->_reflectionData["methods"]; return $reflectionMethods; } else { return false; } }())
			{
				if (count($reflectionMethods))
				{
					$collections = [];

					foreach ($reflectionMethods as $methodName => $reflectionMethod) {
						$collections[$methodName] = new Collection($reflectionMethod);
					}

					$this->_methodAnnotations = $collections;

					return $collections;
				}

			}

			$this->_methodAnnotations = false;

			return false;
		}

		return $annotations;
	}

	public function getPropertiesAnnotations()
	{

		$annotations = $this->_propertyAnnotations;

		if (typeof($annotations) <> "object")
		{
			if (function() { if(isset($this->_reflectionData["properties"])) {$reflectionProperties = $this->_reflectionData["properties"]; return $reflectionProperties; } else { return false; } }())
			{
				if (count($reflectionProperties))
				{
					$collections = [];

					foreach ($reflectionProperties as $property => $reflectionProperty) {
						$collections[$property] = new Collection($reflectionProperty);
					}

					$this->_propertyAnnotations = $collections;

					return $collections;
				}

			}

			$this->_propertyAnnotations = false;

			return false;
		}

		return $annotations;
	}

	public function getReflectionData()
	{
		return $this->_reflectionData;
	}

	public static function __set_state($data)
	{

		if (typeof($data) == "array")
		{
			if (function() { if(isset($data["_reflectionData"])) {$reflectionData = $data["_reflectionData"]; return $reflectionData; } else { return false; } }())
			{
				return new self($reflectionData);
			}

		}

		return new self();
	}


}