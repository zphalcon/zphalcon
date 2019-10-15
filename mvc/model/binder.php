<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\Controller\BindModelInterface;
use Phalcon\Mvc\Model\Binder\BindableInterface;
use Phalcon\Cache\BackendInterface;

class Binder implements BinderInterface
{
	protected $boundModels = [];
	protected $cache;
	protected $internalCache = [];
	protected $originalValues = [];

	public function __construct($cache = null)
	{
		$this->cache = $cache;

	}

	public function setCache($cache)
	{
		$this->cache = $cache;

		return $this;
	}

	public function getCache()
	{
		return $this->cache;
	}

	public function bindToHandler($handler, $params, $cacheKey, $methodName = null)
	{

		$this->originalValues = [];

		if ($handler instanceof $\Closure || $methodName <> null)
		{
			$this->boundModels = [];

			$paramsCache = $this->getParamsFromCache($cacheKey);

			if (typeof($paramsCache) == "array")
			{
				foreach ($paramsCache as $paramKey => $className) {
					$paramValue = $params[$paramKey];
					$boundModel = $this->findBoundModel($paramValue, $className);
					$this[$paramKey] = $paramValue;
					$params[$paramKey] = $boundModel;
					$this[$paramKey] = $boundModel;
				}

				return $params;
			}

			return $this->getParamsFromReflection($handler, $params, $cacheKey, $methodName);
		}

		throw new Exception("You must specify methodName for handler or pass Closure as handler");
	}

	protected function findBoundModel($paramValue, $className)
	{
		return $className::findFirst($paramValue);
	}

	protected function getParamsFromCache($cacheKey)
	{

		if (function() { if(isset($this->internalCache[$cacheKey])) {$internalParams = $this->internalCache[$cacheKey]; return $internalParams; } else { return false; } }())
		{
			return $internalParams;
		}

		$cache = $this->cache;

		if ($cache <> null && $cache->exists($cacheKey))
		{
			$internalParams = $cache->get($cacheKey);

			$this[$cacheKey] = $internalParams;

			return $internalParams;
		}

		return null;
	}

	protected function getParamsFromReflection($handler, $params, $cacheKey, $methodName)
	{

		$paramsCache = [];

		if ($methodName <> null)
		{
			$reflection = new \ReflectionMethod($handler, $methodName);

		}

		$cache = $this->cache;

		$methodParams = $reflection->getParameters();

		$paramsKeys = array_keys($params);

		foreach ($methodParams as $paramKey => $methodParam) {
			$reflectionClass = $methodParam->getClass();
			if (!($reflectionClass))
			{
				continue;

			}
			$className = $reflectionClass->getName();
			if (!(isset($params[$paramKey])))
			{
				$paramKey = $paramsKeys[$paramKey];

			}
			$boundModel = null;
			$paramValue = $params[$paramKey];
			if ($className == "Phalcon\\Mvc\\Model")
			{
				if ($realClasses == null)
				{
					if ($handler instanceof $BindModelInterface)
					{
						$handlerClass = get_class($handler);

						$realClasses = $handlerClass::getModelName();

					}

				}

				if (typeof($realClasses) == "array")
				{
					if (function() { if(isset($realClasses[$paramKey])) {$className = $realClasses[$paramKey]; return $className; } else { return false; } }())
					{
						$boundModel = $this->findBoundModel($paramValue, $className);

					}

				}

			}
			if ($boundModel <> null)
			{
				$this[$paramKey] = $paramValue;

				$params[$paramKey] = $boundModel;

				$this[$paramKey] = $boundModel;

				$paramsCache[$paramKey] = $className;

			}
		}

		if ($cache <> null)
		{
			$cache->save($cacheKey, $paramsCache);

		}

		$this[$cacheKey] = $paramsCache;

		return $params;
	}


}