<?php
namespace Phalcon\Di\Service;

use Phalcon\DiInterface;
use Phalcon\Di\Exception;

class Builder
{
	private function _buildParameter($dependencyInjector, $position, $argument)
	{

		if (!(function() { if(isset($argument["type"])) {$type = $argument["type"]; return $type; } else { return false; } }()))
		{
			throw new Exception("Argument at position " . $position . " must have a type");
		}

		switch ($type) {
			case "service":
				if (!(function() { if(isset($argument["name"])) {$name = $argument["name"]; return $name; } else { return false; } }()))
				{
					throw new Exception("Service 'name' is required in parameter on position " . $position);
				}
				if (typeof($dependencyInjector) <> "object")
				{
					throw new Exception("The dependency injector container is not valid");
				}
				return $dependencyInjector->get($name);			case "parameter":
				if (!(function() { if(isset($argument["value"])) {$value = $argument["value"]; return $value; } else { return false; } }()))
				{
					throw new Exception("Service 'value' is required in parameter on position " . $position);
				}
				return $value;			case "instance":
				if (!(function() { if(isset($argument["className"])) {$name = $argument["className"]; return $name; } else { return false; } }()))
				{
					throw new Exception("Service 'className' is required in parameter on position " . $position);
				}
				if (typeof($dependencyInjector) <> "object")
				{
					throw new Exception("The dependency injector container is not valid");
				}
				if (function() { if(isset($argument["arguments"])) {$instanceArguments = $argument["arguments"]; return $instanceArguments; } else { return false; } }())
				{
					return $dependencyInjector->get($name, $instanceArguments);
				}
				return $dependencyInjector->get($name);			default:
				throw new Exception("Unknown service type in parameter on position " . $position);
		}

	}

	private function _buildParameters($dependencyInjector, $arguments)
	{

		$buildArguments = [];

		foreach ($arguments as $position => $argument) {
			$buildArguments = $this->_buildParameter($dependencyInjector, $position, $argument);
		}

		return $buildArguments;
	}

	public function build($dependencyInjector, $definition, $parameters = null)
	{

		if (!(function() { if(isset($definition["className"])) {$className = $definition["className"]; return $className; } else { return false; } }()))
		{
			throw new Exception("Invalid service definition. Missing 'className' parameter");
		}

		if (typeof($parameters) == "array")
		{
			if (count($parameters))
			{
				$instance = create_instance_params($className, $parameters);

			}

		}

		if (function() { if(isset($definition["calls"])) {$paramCalls = $definition["calls"]; return $paramCalls; } else { return false; } }())
		{
			if (typeof($instance) <> "object")
			{
				throw new Exception("The definition has setter injection parameters but the constructor didn't return an instance");
			}

			if (typeof($paramCalls) <> "array")
			{
				throw new Exception("Setter injection parameters must be an array");
			}

			foreach ($paramCalls as $methodPosition => $method) {
				if (typeof($method) <> "array")
				{
					throw new Exception("Method call must be an array on position " . $methodPosition);
				}
				if (!(function() { if(isset($method["method"])) {$methodName = $method["method"]; return $methodName; } else { return false; } }()))
				{
					throw new Exception("The method name is required on position " . $methodPosition);
				}
				$methodCall = [$instance, $methodName];
				if (function() { if(isset($method["arguments"])) {$arguments = $method["arguments"]; return $arguments; } else { return false; } }())
				{
					if (typeof($arguments) <> "array")
					{
						throw new Exception("Call arguments must be an array " . $methodPosition);
					}

					if (count($arguments))
					{
						call_user_func_array($methodCall, $this->_buildParameters($dependencyInjector, $arguments));

						continue;

					}

				}
				call_user_func($methodCall);
			}

		}

		if (function() { if(isset($definition["properties"])) {$paramCalls = $definition["properties"]; return $paramCalls; } else { return false; } }())
		{
			if (typeof($instance) <> "object")
			{
				throw new Exception("The definition has properties injection parameters but the constructor didn't return an instance");
			}

			if (typeof($paramCalls) <> "array")
			{
				throw new Exception("Setter injection parameters must be an array");
			}

			foreach ($paramCalls as $propertyPosition => $property) {
				if (typeof($property) <> "array")
				{
					throw new Exception("Property must be an array on position " . $propertyPosition);
				}
				if (!(function() { if(isset($property["name"])) {$propertyName = $property["name"]; return $propertyName; } else { return false; } }()))
				{
					throw new Exception("The property name is required on position " . $propertyPosition);
				}
				if (!(function() { if(isset($property["value"])) {$propertyValue = $property["value"]; return $propertyValue; } else { return false; } }()))
				{
					throw new Exception("The property value is required on position " . $propertyPosition);
				}
				$instance->{$propertyName} = $this->_buildParameter($dependencyInjector, $propertyPosition, $propertyValue);
			}

		}

		return $instance;
	}


}