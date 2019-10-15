<?php
namespace Phalcon\Mvc;

use Phalcon\Mvc\DispatcherInterface;
use Phalcon\Mvc\Dispatcher\Exception;
use Phalcon\Events\ManagerInterface;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\ControllerInterface;
use Phalcon\Dispatcher as BaseDispatcher;

class Dispatcher extends BaseDispatcher implements DispatcherInterface
{
	protected $_handlerSuffix = "Controller";
	protected $_defaultHandler = "index";
	protected $_defaultAction = "index";

	public function setControllerSuffix($controllerSuffix)
	{
		$this->_handlerSuffix = $controllerSuffix;

	}

	public function setDefaultController($controllerName)
	{
		$this->_defaultHandler = $controllerName;

	}

	public function setControllerName($controllerName)
	{
		$this->_handlerName = $controllerName;

	}

	public function getControllerName()
	{
		return $this->_handlerName;
	}

	public function getPreviousNamespaceName()
	{
		return $this->_previousNamespaceName;
	}

	public function getPreviousControllerName()
	{
		return $this->_previousHandlerName;
	}

	public function getPreviousActionName()
	{
		return $this->_previousActionName;
	}

	protected function _throwDispatchException($message, $exceptionCode = 0)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection container is required to access the 'response' service", BaseDispatcher::EXCEPTION_NO_DI);
		}

		$response = $dependencyInjector->getShared("response");

		$response->setStatusCode(404, "Not Found");

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

	public function forward($forward)
	{

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("dispatch:beforeForward", $this, $forward);

		}

		parent::forward($forward);

	}

	public function getControllerClass()
	{
		return $this->getHandlerClass();
	}

	public function getLastController()
	{
		return $this->_lastHandler;
	}

	public function getActiveController()
	{
		return $this->_activeHandler;
	}


}