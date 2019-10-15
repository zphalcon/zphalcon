<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Cache\BackendInterface;

interface BinderInterface
{
	public function getBoundModels()
	{
	}

	public function getCache()
	{
	}

	public function setCache($cache)
	{
	}

	public function bindToHandler($handler, $params, $cacheKey, $methodName = null)
	{
	}


}