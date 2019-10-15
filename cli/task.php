<?php
namespace Phalcon\Cli;

use Phalcon\Di\Injectable;

class Task extends Injectable implements TaskInterface
{
	public final function __construct()
	{
		if (method_exists($this, "onConstruct"))
		{
			$this->onConstruct();

		}

	}


}