<?php
namespace Phalcon;

use Phalcon\Config;
use Phalcon\Di\Service;
use Phalcon\DiInterface;
use Phalcon\Di\Exception;
use Phalcon\Config\Adapter\Php;
use Phalcon\Config\Adapter\Yaml;
use Phalcon\Di\ServiceInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Di\ServiceProviderInterface;

class Di implements DiInterface
{
	protected $_services;
	protected $_sharedInstances;
	protected $_freshInstance = false;
	protected $_eventsManager;
	protected static $_default;

	public function __construct()
	{

		$di = self::_default;

		if (!($di))
		{
			self::_default = $this;

		}

	}

	public function setInternalEventsManager($eventsManager)
	{
		$this->_eventsManager = $eventsManager;

	}

	public function getInternalEventsManager()
	{
		return $this->_eventsManager;
	}

	public function set($name, $definition, $shared = false)
	{

		$service = new Service($name, $definition, $shared);
		$this[$name] = $service;

		return $service;
	}

	public function setShared($name, $definition)
	{
		return $this->set($name, $definition, true);
	}

	public function remove($name)
	{
		unset($this->_services[$name]);

		unset($this->_sharedInstances[$name]);

	}

	public function attempt($name, $definition, $shared = false)
	{

		if (!(isset($this->_services[$name])))
		{
			$service = new Service($name, $definition, $shared);
			$this[$name] = $service;

			return $service;
		}

		return false;
	}

	public function setRaw($name, $rawDefinition)
	{
		$this[$name] = $rawDefinition;

		return $rawDefinition;
	}

	public function getRaw($name)
	{

		if (function() { if(isset($this->_services[$name])) {$service = $this->_services[$name]; return $service; } else { return false; } }())
		{
			return $service->getDefinition();
		}

		throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
	}

	public function getService($name)
	{

		if (function() { if(isset($this->_services[$name])) {$service = $this->_services[$name]; return $service; } else { return false; } }())
		{
			return $service;
		}

		throw new Exception("Service '" . $name . "' wasn't found in the dependency injection container");
	}

	public function get($name, $parameters = null)
	{

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			$instance = $eventsManager->fire("di:beforeServiceResolve", $this, ["name" => $name, "parameters" => $parameters]);

		}

		if (typeof($instance) <> "object")
		{
			if (function() { if(isset($this->_services[$name])) {$service = $this->_services[$name]; return $service; } else { return false; } }())
			{
				$instance = $service->resolve($parameters, $this);

			}

		}

		if (typeof($instance) == "object")
		{
			if ($instance instanceof $InjectionAwareInterface)
			{
				$instance->setDI($this);

			}

		}

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("di:afterServiceResolve", $this, ["name" => $name, "parameters" => $parameters, "instance" => $instance]);

		}

		return $instance;
	}

	public function getShared($name, $parameters = null)
	{

		if (function() { if(isset($this->_sharedInstances[$name])) {$instance = $this->_sharedInstances[$name]; return $instance; } else { return false; } }())
		{
			$this->_freshInstance = false;

		}

		return $instance;
	}

	public function has($name)
	{
		return isset($this->_services[$name]);
	}

	public function wasFreshInstance()
	{
		return $this->_freshInstance;
	}

	public function getServices()
	{
		return $this->_services;
	}

	public function offsetExists($name)
	{
		return $this->has($name);
	}

	public function offsetSet($name, $definition)
	{
		$this->setShared($name, $definition);

		return true;
	}

	public function offsetGet($name)
	{
		return $this->getShared($name);
	}

	public function offsetUnset($name)
	{
		return false;
	}

	public function __call($method, $arguments = null)
	{

		if (starts_with($method, "get"))
		{
			$services = $this->_services;
			$possibleService = lcfirst(substr($method, 3));

			if (isset($services[$possibleService]))
			{
				if (count($arguments))
				{
					$instance = $this->get($possibleService, $arguments);

				}

				return $instance;
			}

		}

		if (starts_with($method, "set"))
		{
			if (function() { if(isset($arguments[0])) {$definition = $arguments[0]; return $definition; } else { return false; } }())
			{
				$this->set(lcfirst(substr($method, 3)), $definition);

				return null;
			}

		}

		throw new Exception("Call to undefined method or service '" . $method . "'");
	}

	public function register($provider)
	{
		$provider->register($this);

	}

	public static function setDefault($dependencyInjector)
	{
		self::_default = $dependencyInjector;

	}

	public static function getDefault()
	{
		return self::_default;
	}

	public static function reset()
	{
		self::_default = null;

	}

	public function loadFromYaml($filePath, $callbacks = null)
	{

		$services = new Yaml($filePath, $callbacks);

		$this->loadFromConfig($services);

	}

	public function loadFromPhp($filePath)
	{

		$services = new Php($filePath);

		$this->loadFromConfig($services);

	}

	protected function loadFromConfig($config)
	{

		$services = $config->toArray();

		foreach ($services as $name => $service) {
			$this->set($name, $service, isset($service["shared"]) && $service["shared"]);
		}

	}


}