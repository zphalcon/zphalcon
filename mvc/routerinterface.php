<?php
namespace Phalcon\Mvc;

use Phalcon\Mvc\Router\RouteInterface;
use Phalcon\Mvc\Router\GroupInterface;

interface RouterInterface
{
	public function setDefaultModule($moduleName)
	{
	}

	public function setDefaultController($controllerName)
	{
	}

	public function setDefaultAction($actionName)
	{
	}

	public function setDefaults($defaults)
	{
	}

	public function handle($uri = null)
	{
	}

	public function add($pattern, $paths = null, $httpMethods = null)
	{
	}

	public function addGet($pattern, $paths = null)
	{
	}

	public function addPost($pattern, $paths = null)
	{
	}

	public function addPut($pattern, $paths = null)
	{
	}

	public function addPatch($pattern, $paths = null)
	{
	}

	public function addDelete($pattern, $paths = null)
	{
	}

	public function addOptions($pattern, $paths = null)
	{
	}

	public function addHead($pattern, $paths = null)
	{
	}

	public function addPurge($pattern, $paths = null)
	{
	}

	public function addTrace($pattern, $paths = null)
	{
	}

	public function addConnect($pattern, $paths = null)
	{
	}

	public function mount($group)
	{
	}

	public function clear()
	{
	}

	public function getModuleName()
	{
	}

	public function getNamespaceName()
	{
	}

	public function getControllerName()
	{
	}

	public function getActionName()
	{
	}

	public function getParams()
	{
	}

	public function getMatchedRoute()
	{
	}

	public function getMatches()
	{
	}

	public function wasMatched()
	{
	}

	public function getRoutes()
	{
	}

	public function getRouteById($id)
	{
	}

	public function getRouteByName($name)
	{
	}


}