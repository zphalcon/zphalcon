<?php
namespace Phalcon\Cli;

use Phalcon\Application as BaseApplication;
use Phalcon\DiInterface;
use Phalcon\Cli\Router\Route;
use Phalcon\Events\ManagerInterface;
use Phalcon\Cli\Console\Exception;

class Console extends BaseApplication
{
	protected $_arguments = [];
	protected $_options = [];

	deprecated public function addModules($modules)
	{
		return $this->registerModules($modules, true);
	}

	public function handle($arguments = null)
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection object is required to access internal services");
		}

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			if ($eventsManager->fire("console:boot", $this) === false)
			{
				return false;
			}

		}

		$router = $dependencyInjector->getShared("router");

		if (!(count($arguments)) && $this->_arguments)
		{
			$router->handle($this->_arguments);

		}

		$moduleName = $router->getModuleName();

		if (!($moduleName))
		{
			$moduleName = $this->_defaultModule;

		}

		if ($moduleName)
		{
			if (typeof($eventsManager) == "object")
			{
				if ($eventsManager->fire("console:beforeStartModule", $this, $moduleName) === false)
				{
					return false;
				}

			}

			$modules = $this->_modules;

			if (!(isset($modules[$moduleName])))
			{
				throw new Exception("Module '" . $moduleName . "' isn't registered in the console container");
			}

			$module = $modules[$moduleName];

			if (typeof($module) <> "array")
			{
				throw new Exception("Invalid module definition path");
			}

			if (function() { if(isset($module["path"])) {$path = $module["path"]; return $path; } else { return false; } }())
			{
				if (!(file_exists($path)))
				{
					throw new Exception("Module definition path '" . $path . "' doesn't exist");
				}

				require($path);

			}

			if (!(function() { if(isset($module["className"])) {$className = $module["className"]; return $className; } else { return false; } }()))
			{
				$className = "Module";

			}

			$moduleObject = $dependencyInjector->get($className);

			$moduleObject->registerAutoloaders();

			$moduleObject->registerServices($dependencyInjector);

			if (typeof($eventsManager) == "object")
			{
				if ($eventsManager->fire("console:afterStartModule", $this, $moduleObject) === false)
				{
					return false;
				}

			}

		}

		$dispatcher = $dependencyInjector->getShared("dispatcher");

		$dispatcher->setTaskName($router->getTaskName());

		$dispatcher->setActionName($router->getActionName());

		$dispatcher->setParams($router->getParams());

		$dispatcher->setOptions($this->_options);

		if (typeof($eventsManager) == "object")
		{
			if ($eventsManager->fire("console:beforeHandleTask", $this, $dispatcher) === false)
			{
				return false;
			}

		}

		$task = $dispatcher->dispatch();

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("console:afterHandleTask", $this, $task);

		}

		return $task;
	}

	public function setArgument($arguments = null, $str = true, $shift = true)
	{

		$args = [];
		$opts = [];
		$handleArgs = [];

		if ($shift && count($arguments))
		{
			array_shift($arguments);

		}

		foreach ($arguments as $arg) {
			if (typeof($arg) == "string")
			{
				if (strncmp($arg, "--", 2) == 0)
				{
					$pos = strpos($arg, "=");

					if ($pos)
					{
						$opts[trim(substr($arg, 2, $pos - 2))] = trim(substr($arg, $pos + 1));

					}

				}

			}
		}

		if ($str)
		{
			$this->_arguments = implode(Route::getDelimiter(), $args);

		}

		$this->_options = $opts;

		return $this;
	}


}