<?php
namespace Phalcon\Mvc\Router;

use Phalcon\DiInterface;
use Phalcon\Mvc\Router;
use Phalcon\Annotations\Annotation;
use Phalcon\Mvc\Router\Exception;

class Annotations extends Router
{
	protected $_handlers = [];
	protected $_controllerSuffix = "Controller";
	protected $_actionSuffix = "Action";
	protected $_routePrefix;

	public function addResource($handler, $prefix = null)
	{
		$this->_handlers[] = [$prefix, $handler];

		return $this;
	}

	public function addModuleResource($module, $handler, $prefix = null)
	{
		$this->_handlers[] = [$prefix, $handler, $module];

		return $this;
	}

	public function handle($uri = null)
	{

		if (!($uri))
		{
			$realUri = $this->getRewriteUri();

		}

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injection container is required to access the 'annotations' service");
		}

		$annotationsService = $dependencyInjector->getShared("annotations");

		$handlers = $this->_handlers;

		$controllerSuffix = $this->_controllerSuffix;

		foreach ($handlers as $scope) {
			if (typeof($scope) <> "array")
			{
				continue;

			}
			$prefix = $scope[0];
			if (!(empty($prefix)) && !(starts_with($realUri, $prefix)))
			{
				continue;

			}
			$handler = $scope[1];
			if (memstr($handler, "\\"))
			{
				$controllerName = get_class_ns($handler);
				$namespaceName = get_ns_class($handler);

			}
			$this->_routePrefix = null;
			$moduleName = $scope[2]			$sufixed = $controllerName . $controllerSuffix;
			if ($namespaceName !== null)
			{
				$sufixed = $namespaceName . "\\" . $sufixed;

			}
			$handlerAnnotations = $annotationsService->get($sufixed);
			if (typeof($handlerAnnotations) <> "object")
			{
				continue;

			}
			$classAnnotations = $handlerAnnotations->getClassAnnotations();
			if (typeof($classAnnotations) == "object")
			{
				$annotations = $classAnnotations->getAnnotations();

				if (typeof($annotations) == "array")
				{
					foreach ($annotations as $annotation) {
						$this->processControllerAnnotation($controllerName, $annotation);
					}

				}

			}
			$methodAnnotations = $handlerAnnotations->getMethodsAnnotations();
			if (typeof($methodAnnotations) == "array")
			{
				$lowerControllerName = uncamelize($controllerName);

				foreach ($methodAnnotations as $method => $collection) {
					if (typeof($collection) == "object")
					{
						foreach ($collection->getAnnotations() as $annotation) {
							$this->processActionAnnotation($moduleName, $namespaceName, $lowerControllerName, $method, $annotation);
						}

					}
				}

			}
		}

		parent::handle($realUri);

	}

	public function processControllerAnnotation($handler, $annotation)
	{
		if ($annotation->getName() == "RoutePrefix")
		{
			$this->_routePrefix = $annotation->getArgument(0);

		}

	}

	public function processActionAnnotation($module, $namespaceName, $controller, $action, $annotation)
	{

		$isRoute = false;
		$methods = null;
		$name = $annotation->getName();

		switch ($name) {
			case "Route":
				$isRoute = true;
				break;
			case "Get":
				$isRoute = true;
				$methods = "GET";
				break;
			case "Post":
				$isRoute = true;
				$methods = "POST";
				break;
			case "Put":
				$isRoute = true;
				$methods = "PUT";
				break;
			case "Patch":
				$isRoute = true;
				$methods = "PATCH";
				break;
			case "Delete":
				$isRoute = true;
				$methods = "DELETE";
				break;
			case "Options":
				$isRoute = true;
				$methods = "OPTIONS";
				break;

		}

		if ($isRoute === true)
		{
			$actionName = strtolower(str_replace($this->_actionSuffix, "", $action));
			$routePrefix = $this->_routePrefix;

			$paths = $annotation->getNamedArgument("paths");

			if (typeof($paths) <> "array")
			{
				$paths = [];

			}

			if (!(empty($module)))
			{
				$paths["module"] = $module;

			}

			if (!(empty($namespaceName)))
			{
				$paths["namespace"] = $namespaceName;

			}

			$paths["controller"] = $controller;
			$paths["action"] = $actionName;

			$value = $annotation->getArgument(0);

			if (typeof($value) !== "null")
			{
				if ($value <> "/")
				{
					$uri = $routePrefix . $value;

				}

			}

			$route = $this->add($uri, $paths);

			if ($methods !== null)
			{
				$route->via($methods);

			}

			$converts = $annotation->getNamedArgument("converts");

			if (typeof($converts) == "array")
			{
				foreach ($converts as $param => $convert) {
					$route->convert($param, $convert);
				}

			}

			$converts = $annotation->getNamedArgument("conversors");

			if (typeof($converts) == "array")
			{
				foreach ($converts as $conversorParam => $convert) {
					$route->convert($conversorParam, $convert);
				}

			}

			$beforeMatch = $annotation->getNamedArgument("beforeMatch");

			if (typeof($beforeMatch) == "array" || typeof($beforeMatch) == "string")
			{
				$route->beforeMatch($beforeMatch);

			}

			$routeName = $annotation->getNamedArgument("name");

			if (typeof($routeName) == "string")
			{
				$route->setName($routeName);

			}

			return true;
		}

	}

	public function setControllerSuffix($controllerSuffix)
	{
		$this->_controllerSuffix = $controllerSuffix;

	}

	public function setActionSuffix($actionSuffix)
	{
		$this->_actionSuffix = $actionSuffix;

	}

	public function getResources()
	{
		return $this->_handlers;
	}


}