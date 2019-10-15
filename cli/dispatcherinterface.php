<?php
namespace Phalcon\Cli;

use Phalcon\DispatcherInterface as DispatcherInterfaceBase;

interface DispatcherInterface extends DispatcherInterfaceBase
{
	public function setTaskSuffix($taskSuffix)
	{
	}

	public function setDefaultTask($taskName)
	{
	}

	public function setTaskName($taskName)
	{
	}

	public function getTaskName()
	{
	}

	public function getLastTask()
	{
	}

	public function getActiveTask()
	{
	}


}