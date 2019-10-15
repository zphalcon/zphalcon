<?php
namespace Phalcon;

use Phalcon\Application\Exception;
use Phalcon\DiInterface;
use Phalcon\Di\Injectable;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
abstract 
class Application extends Injectable implements EventsAwareInterface
{
	protected $_eventsManager;
	protected $_dependencyInjector;
	protected $_defaultModule;
	protected $_modules = [];

	public function __construct($dependencyInjector = null)
	{
		if (typeof($dependencyInjector) == "object")
		{
			$this->_dependencyInjector = $dependencyInjector;

		}

	}

	public function setEventsManager($eventsManager)
	{
		$this->_eventsManager = $eventsManager;

		return $this;
	}

	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	public function registerModules($modules, $merge = false)
	{
		if ($merge)
		{
			$this->_modules = array_merge($this->_modules, $modules);

		}

		return $this;
	}

	public function getModules()
	{
		return $this->_modules;
	}

	public function getModule($name)
	{

		if (!(function() { if(isset($this->_modules[$name])) {$module = $this->_modules[$name]; return $module; } else { return false; } }()))
		{
			throw new Exception("Module '" . $name . "' isn't registered in the application container");
		}

		return $module;
	}

	public function setDefaultModule($defaultModule)
	{
		$this->_defaultModule = $defaultModule;

		return $this;
	}

	public function getDefaultModule()
	{
		return $this->_defaultModule;
	}

	abstract public function handle()


}