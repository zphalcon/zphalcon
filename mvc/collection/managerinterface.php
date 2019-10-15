<?php
namespace Phalcon\Mvc\Collection;

use Phalcon\Db\AdapterInterface;
use Phalcon\Mvc\CollectionInterface;
use Phalcon\Mvc\Collection\BehaviorInterface;
use Phalcon\Events\ManagerInterface as EventsManagerInterface;

interface ManagerInterface
{
	public function setCustomEventsManager($model, $eventsManager)
	{
	}

	public function getCustomEventsManager($model)
	{
	}

	public function initialize($model)
	{
	}

	public function isInitialized($modelName)
	{
	}

	public function getLastInitialized()
	{
	}

	public function setConnectionService($model, $connectionService)
	{
	}

	public function useImplicitObjectIds($model, $useImplicitObjectIds)
	{
	}

	public function isUsingImplicitObjectIds($model)
	{
	}

	public function getConnection($model)
	{
	}

	public function notifyEvent($eventName, $model)
	{
	}

	public function addBehavior($model, $behavior)
	{
	}


}