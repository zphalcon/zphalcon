<?php
namespace Phalcon\Annotations;

use Phalcon\Annotations\Reflection;
use Phalcon\Annotations\Collection;
use Phalcon\Annotations\ReaderInterface;

interface AdapterInterface
{
	public function setReader($reader)
	{
	}

	public function getReader()
	{
	}

	public function get($className)
	{
	}

	public function getMethods($className)
	{
	}

	public function getMethod($className, $methodName)
	{
	}

	public function getProperties($className)
	{
	}

	public function getProperty($className, $propertyName)
	{
	}


}