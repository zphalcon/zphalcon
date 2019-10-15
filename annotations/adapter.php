<?php
namespace Phalcon\Annotations;

use Phalcon\Annotations\AdapterInterface;
use Phalcon\Annotations\Reader;
use Phalcon\Annotations\Exception;
use Phalcon\Annotations\Collection;
use Phalcon\Annotations\Reflection;
use Phalcon\Annotations\ReaderInterface;
abstract 
class Adapter implements AdapterInterface
{
	protected $_reader;
	protected $_annotations;

	public function setReader($reader)
	{
		$this->_reader = $reader;

	}

	public function getReader()
	{
		if (typeof($this->_reader) <> "object")
		{
			$this->_reader = new Reader();

		}

		return $this->_reader;
	}

	public function get($className)
	{

		if (typeof($className) == "object")
		{
			$realClassName = get_class($className);

		}

		$annotations = $this->_annotations;

		if (typeof($annotations) == "array")
		{
			if (isset($annotations[$realClassName]))
			{
				return $annotations[$realClassName];
			}

		}

		$classAnnotations = $this->read($realClassName);

		if ($classAnnotations === null || $classAnnotations === false)
		{
			$reader = $this->getReader();
			$parsedAnnotations = $reader->parse($realClassName);

			if (typeof($parsedAnnotations) == "array")
			{
				$classAnnotations = new Reflection($parsedAnnotations);
				$this[$realClassName] = $classAnnotations;

				$this->write($realClassName, $classAnnotations);

			}

		}

		return $classAnnotations;
	}

	public function getMethods($className)
	{

		$classAnnotations = $this->get($className);

		if (typeof($classAnnotations) == "object")
		{
			return $classAnnotations->getMethodsAnnotations();
		}

		return [];
	}

	public function getMethod($className, $methodName)
	{

		$classAnnotations = $this->get($className);

		if (typeof($classAnnotations) == "object")
		{
			$methods = $classAnnotations->getMethodsAnnotations();

			if (typeof($methods) == "array")
			{
				foreach ($methods as $methodKey => $method) {
					if (!(strcasecmp($methodKey, $methodName)))
					{
						return $method;
					}
				}

			}

		}

		return new Collection();
	}

	public function getProperties($className)
	{

		$classAnnotations = $this->get($className);

		if (typeof($classAnnotations) == "object")
		{
			return $classAnnotations->getPropertiesAnnotations();
		}

		return [];
	}

	public function getProperty($className, $propertyName)
	{

		$classAnnotations = $this->get($className);

		if (typeof($classAnnotations) == "object")
		{
			$properties = $classAnnotations->getPropertiesAnnotations();

			if (typeof($properties) == "array")
			{
				if (function() { if(isset($properties[$propertyName])) {$property = $properties[$propertyName]; return $property; } else { return false; } }())
				{
					return $property;
				}

			}

		}

		return new Collection();
	}


}