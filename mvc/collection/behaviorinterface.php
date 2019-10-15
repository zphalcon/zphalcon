<?php
namespace Phalcon\Mvc\Collection;

use Phalcon\Mvc\CollectionInterface;

interface BehaviorInterface
{
	public function notify($type, $collection)
	{
	}

	public function missingMethod($collection, $method, $arguments = null)
	{
	}


}