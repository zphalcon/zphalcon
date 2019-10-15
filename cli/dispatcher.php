<?php
namespace Phalcon\Cli;

use Phalcon\FilterInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Cli\Dispatcher\Exception;
use Phalcon\Dispatcher as CliDispatcher;

class Dispatcher extends CliDispatcher implements DispatcherInterface
{
	protected $_handlerSuffix = "Task";
	protected $_defaultHandler = "main";
	protected $_defaultAction = "main";
	protected $_options = [];

	public function setTaskSuffix($taskSuffix)
	{
		$this->_handlerSuffix = $taskSuffix;

	}

	public function setDefaultTask($taskName)
	{
		$this->_defaultHandler = $taskName;

	}

	public function setTaskName($taskName)
	{
		$this->_handlerName = $taskName;

	}

	public function getTaskName()
	{
		return $this->_handlerName;
	}

	protected function _throwDispatchException($message, $exceptionCode = 0)
	{

		$exception = new Exception($message, $exceptionCode);

		if ($this->_handleException($exception) === false)
		{
			return false;
		}

		throw $exception;
	}

	protected function _handleException($exception)
	{

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			if ($eventsManager->fire("dispatch:beforeException", $this, $exception) === false)
			{
				return false;
			}

		}

	}

	public function getLastTask()
	{
		return $this->_lastHandler;
	}

	public function getActiveTask()
	{
		return $this->_activeHandler;
	}

	public function setOptions($options)
	{
		$this->_options = $options;

	}

	public function getOptions()
	{
		return $this->_options;
	}

	public function getOption($option, $filters = null, $defaultValue = null)
	{

		$options = $this->_options;

		if (!(function() { if(isset($options[$option])) {$optionValue = $options[$option]; return $optionValue; } else { return false; } }()))
		{
			return $defaultValue;
		}

		if ($filters === null)
		{
			return $optionValue;
		}

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			$this->_throwDispatchException("A dependency injection object is required to access the 'filter' service", CliDispatcher::EXCEPTION_NO_DI);

		}

		$filter = $dependencyInjector->getShared("filter");

		return $filter->sanitize($optionValue, $filters);
	}

	public function hasOption($option)
	{
		return isset($this->_options[$option]);
	}

	public function callActionMethod($handler, $actionMethod, $params = [])
	{

		$options = $this->_options;

		return call_user_func_array([$handler, $actionMethod], [$params, $options]);
	}


}