<?php
namespace Phalcon\Di;

use Phalcon\DiInterface;

interface InjectionAwareInterface
{
	public function setDI($dependencyInjector)
	{
	}

	public function getDI()
	{
	}


}