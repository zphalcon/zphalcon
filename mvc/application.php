<?php
namespace Phalcon\Mvc;

use Phalcon\Application as BaseApplication;
use Phalcon\DiInterface;
use Phalcon\Mvc\ViewInterface;
use Phalcon\Mvc\RouterInterface;
use Phalcon\Http\ResponseInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Mvc\DispatcherInterface;
use Phalcon\Mvc\Application\Exception;
use Phalcon\Mvc\Router\RouteInterface;
use Phalcon\Mvc\ModuleDefinitionInterface;

class Application extends BaseApplication
{
	protected $_implicitView = true;
	protected $_sendHeaders = true;
	protected $_sendCookies = true;

	public function sendHeadersOnHandleRequest($sendHeaders)
	{
		$this->_sendHeaders = $sendHeaders;

		return $this;
	}

	public function sendCookiesOnHandleRequest($sendCookies)
	{
		$this->_sendCookies = $sendCookies;

		return $this;
	}

	public function useImplicitView($implicitView)
	{
		$this->_implicitView = $implicitView;

		return $this;
	}

	public function handle($uri = null)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection object is required to access internal services");
		}

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			if ($eventsManager->fire("application:boot", $this) === false)
			{
				return false;
			}

		}

		$router = $dependencyInjector->getShared("router");

		$router->handle($uri);

		$matchedRoute = $router->getMatchedRoute();

		if (typeof($matchedRoute) == "object")
		{
			$match = $matchedRoute->getMatch();

			if ($match !== null)
			{
				if ($match instanceof $\Closure)
				{
					$match = \Closure::bind($match, $dependencyInjector);

				}

				$possibleResponse = call_user_func_array($match, $router->getParams());

				if (typeof($possibleResponse) == "string")
				{
					$response = $dependencyInjector->getShared("response");

					$response->setContent($possibleResponse);

					return $response;
				}

				if (typeof($possibleResponse) == "object")
				{
					if ($possibleResponse instanceof $ResponseInterface)
					{
						$possibleResponse->sendHeaders();

						$possibleResponse->sendCookies();

						return $possibleResponse;
					}

				}

			}

		}

		$moduleName = $router->getModuleName();

		if (!($moduleName))
		{
			$moduleName = $this->_defaultModule;

		}

		$moduleObject = null;

		if ($moduleName)
		{
			if (typeof($eventsManager) == "object")
			{
				if ($eventsManager->fire("application:beforeStartModule", $this, $moduleName) === false)
				{
					return false;
				}

			}

			$module = $this->getModule($moduleName);

			if (typeof($module) <> "array" && typeof($module) <> "object")
			{
				throw new Exception("Invalid module definition");
			}

			if (typeof($module) == "array")
			{
				if (!(function() { if(isset($module["className"])) {$className = $module["className"]; return $className; } else { return false; } }()))
				{
					$className = "Module";

				}

				if (function() { if(isset($module["path"])) {$path = $module["path"]; return $path; } else { return false; } }())
				{
					if (!(class_exists($className, false)))
					{
						if (!(file_exists($path)))
						{
							throw new Exception("Module definition path '" . $path . "' doesn't exist");
						}

						require($path);

					}

				}

				$moduleObject = $dependencyInjector->get($className);

				$moduleObject->registerAutoloaders($dependencyInjector);

				$moduleObject->registerServices($dependencyInjector);

			}

			if (typeof($eventsManager) == "object")
			{
				$eventsManager->fire("application:afterStartModule", $this, $moduleObject);

			}

		}

		$implicitView = $this->_implicitView;

		if ($implicitView === true)
		{
			$view = $dependencyInjector->getShared("view");

		}

		$dispatcher = $dependencyInjector->getShared("dispatcher");

		$dispatcher->setModuleName($router->getModuleName());

		$dispatcher->setNamespaceName($router->getNamespaceName());

		$dispatcher->setControllerName($router->getControllerName());

		$dispatcher->setActionName($router->getActionName());

		$dispatcher->setParams($router->getParams());

		if ($implicitView === true)
		{
			$view->start();

		}

		if (typeof($eventsManager) == "object")
		{
			if ($eventsManager->fire("application:beforeHandleRequest", $this, $dispatcher) === false)
			{
				return false;
			}

		}

		$controller = $dispatcher->dispatch();

		$possibleResponse = $dispatcher->getReturnedValue();

		if (typeof($possibleResponse) == "boolean" && $possibleResponse === false)
		{
			$response = $dependencyInjector->getShared("response");

		}

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("application:beforeSendResponse", $this, $response);

		}

		if ($this->_sendHeaders)
		{
			$response->sendHeaders();

		}

		if ($this->_sendCookies)
		{
			$response->sendCookies();

		}

		return $response;
	}


}