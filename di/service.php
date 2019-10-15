<?php
namespace Phalcon\Di;

use Phalcon\DiInterface;
use Phalcon\Di\Exception;
use Phalcon\Di\ServiceInterface;
use Phalcon\Di\Service\Builder;

class Service implements ServiceInterface
{
	protected $_name;
	protected $_definition;
	protected $_shared = false;
	protected $_resolved = false;
	protected $_sharedInstance;

	public final function __construct($name, $definition, $shared = false)
	{
		$this->_name = $name;
		$this->_definition = $definition;
		$this->_shared = $shared;

	}

	public function getName()
	{
		return $this->_name;
	}

	public function setShared($shared)
	{
		$this->_shared = $shared;

	}

	public function isShared()
	{
		return $this->_shared;
	}

	public function setSharedInstance($sharedInstance)
	{
		$this->_sharedInstance = $sharedInstance;

	}

	public function setDefinition($definition)
	{
		$this->_definition = $definition;

	}

	public function getDefinition()
	{
		return $this->_definition;
	}

	public function resolve($parameters = null, $dependencyInjector = null)
	{


		$shared = $this->_shared;

		if ($shared)
		{
			$sharedInstance = $this->_sharedInstance;

			if ($sharedInstance !== null)
			{
				return $sharedInstance;
			}

		}

		$found = true;
		$instance = null;

		$definition = $this->_definition;

		if (typeof($definition) == "string")
		{
			if (class_exists($definition))
			{
				if (typeof($parameters) == "array")
				{
					if (count($parameters))
					{
						$instance = create_instance_params($definition, $parameters);

					}

				}

			}

		}

		if ($found === false)
		{
			throw new Exception("Service '" . $this->_name . "' cannot be resolved");
		}

		if ($shared)
		{
			$this->_sharedInstance = $instance;

		}

		$this->_resolved = true;

		return $instance;
	}

	public function setParameter($position, $parameter)
	{

		$definition = $this->_definition;

		if (typeof($definition) <> "array")
		{
			throw new Exception("Definition must be an array to update its parameters");
		}

		if (function() { if(isset($definition["arguments"])) {$arguments = $definition["arguments"]; return $arguments; } else { return false; } }())
		{
			$arguments[$position] = $parameter;

		}

		$definition["arguments"] = $arguments;

		$this->_definition = $definition;

		return $this;
	}

	public function getParameter($position)
	{

		$definition = $this->_definition;

		if (typeof($definition) <> "array")
		{
			throw new Exception("Definition must be an array to obtain its parameters");
		}

		if (function() { if(isset($definition["arguments"])) {$arguments = $definition["arguments"]; return $arguments; } else { return false; } }())
		{
			if (function() { if(isset($arguments[$position])) {$parameter = $arguments[$position]; return $parameter; } else { return false; } }())
			{
				return $parameter;
			}

		}

		return null;
	}

	public function isResolved()
	{
		return $this->_resolved;
	}

	public static function __set_state($attributes)
	{

		if (!(function() { if(isset($attributes["_name"])) {$name = $attributes["_name"]; return $name; } else { return false; } }()))
		{
			throw new Exception("The attribute '_name' is required");
		}

		if (!(function() { if(isset($attributes["_definition"])) {$definition = $attributes["_definition"]; return $definition; } else { return false; } }()))
		{
			throw new Exception("The attribute '_definition' is required");
		}

		if (!(function() { if(isset($attributes["_shared"])) {$shared = $attributes["_shared"]; return $shared; } else { return false; } }()))
		{
			throw new Exception("The attribute '_shared' is required");
		}

		return new self($name, $definition, $shared);
	}


}