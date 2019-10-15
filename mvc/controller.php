<?php
namespace Phalcon\Mvc;

use Phalcon\Di\Injectable;
abstract 
class Controller extends Injectable implements ControllerInterface
{
	public final function __construct()
	{
		if (method_exists($this, "onConstruct"))
		{
			$this->onConstruct();

		}

	}


}