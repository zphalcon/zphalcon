<?php
namespace Phalcon\Acl;

use Phalcon\Events\ManagerInterface;
use Phalcon\Events\EventsAwareInterface;
abstract 
class Adapter implements AdapterInterface, EventsAwareInterface
{
	protected $_eventsManager;
	protected $_defaultAccess = true;
	protected $_accessGranted = false;
	protected $_activeRole;
	protected $_activeResource;
	protected $_activeAccess;

	public function setEventsManager($eventsManager)
	{
		$this->_eventsManager = $eventsManager;

	}

	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	public function setDefaultAction($defaultAccess)
	{
		$this->_defaultAccess = $defaultAccess;

	}

	public function getDefaultAction()
	{
		return $this->_defaultAccess;
	}


}