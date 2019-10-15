<?php
namespace Phalcon\Di;

use Phalcon\Di;
use Phalcon\DiInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Di\Exception;
use Phalcon\Session\BagInterface;
abstract 
class Injectable implements InjectionAwareInterface, EventsAwareInterface
{
	protected $_dependencyInjector;
	protected $_eventsManager;

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = Di::getDefault();

		}

		return $dependencyInjector;
	}

	public function setEventsManager($eventsManager)
	{
		$this->_eventsManager = $eventsManager;

	}

	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	public function __get($propertyName)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			$dependencyInjector = \Phalcon\Di::getDefault();

			if (typeof($dependencyInjector) <> "object")
			{
				throw new Exception("A dependency injection object is required to access the application services");
			}

		}

		if ($dependencyInjector->has($propertyName))
		{
			$service = $dependencyInjector->getShared($propertyName);

			$this->{$propertyName} = $service;

			return $service;
		}

		if ($propertyName == "di")
		{
			$this->{di} = $dependencyInjector;

			return $dependencyInjector;
		}

		if ($propertyName == "persistent")
		{
			$persistent = $dependencyInjector->get("sessionBag", [get_class($this)]);
			$this->{persistent} = $persistent;

			return $persistent;
		}

		trigger_error("Access to undefined property " . $propertyName);

		return null;
	}


}