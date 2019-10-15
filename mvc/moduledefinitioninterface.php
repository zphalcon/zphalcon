<?php
namespace Phalcon\Mvc;

use Phalcon\DiInterface;

interface ModuleDefinitionInterface
{
	public function registerAutoloaders($dependencyInjector = null)
	{
	}

	public function registerServices($dependencyInjector)
	{
	}


}