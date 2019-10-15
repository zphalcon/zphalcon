<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\ModelInterface;

interface BehaviorInterface
{
	public function notify($type, $model)
	{
	}

	public function missingMethod($model, $method, $arguments = null)
	{
	}


}