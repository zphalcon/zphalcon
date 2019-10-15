<?php
namespace Phalcon\Mvc;

use Phalcon\Mvc\ControllerInterface;
use Phalcon\DispatcherInterface as DispatcherInterfaceBase;

interface DispatcherInterface extends DispatcherInterfaceBase
{
	public function setControllerSuffix($controllerSuffix)
	{
	}

	public function setDefaultController($controllerName)
	{
	}

	public function setControllerName($controllerName)
	{
	}

	public function getControllerName()
	{
	}

	public function getLastController()
	{
	}

	public function getActiveController()
	{
	}


}