<?php
namespace Phalcon\Mvc\View;

use Phalcon\DiInterface;
use Phalcon\Mvc\ViewBaseInterface;

interface EngineInterface
{
	public function getContent()
	{
	}

	public function partial($partialPath, $params = null)
	{
	}

	public function render($path, $params, $mustClean = false)
	{
	}


}