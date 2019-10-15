<?php
namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\ModelInterface;
use Phalcon\DiInterface;

interface StrategyInterface
{
	public function getMetaData($model, $dependencyInjector)
	{
	}

	public function getColumnMaps($model, $dependencyInjector)
	{
	}


}