<?php
namespace Phalcon\Mvc\View;

use Phalcon\Di\Injectable;
use Phalcon\Mvc\View\Exception;
use Phalcon\Mvc\ViewBaseInterface;
use Phalcon\Cache\BackendInterface;
use Phalcon\Mvc\View\EngineInterface;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;

class Simple extends Injectable implements ViewBaseInterface
{
	protected $_options;
	protected $_viewsDir;
	protected $_partialsDir;
	protected $_viewParams;
	protected $_engines = false;
	protected $_registeredEngines;
	protected $_activeRenderPath;
	protected $_content;
	protected $_cache = false;
	protected $_cacheOptions;

	public function __construct($options = [])
	{
		$this->_options = $options;

	}

	public function setViewsDir($viewsDir)
	{
		$this->_viewsDir = $viewsDir;

	}

	public function getViewsDir()
	{
		return $this->_viewsDir;
	}

	public function registerEngines($engines)
	{
		$this->_registeredEngines = $engines;

	}

	protected function _loadTemplateEngines()
	{

		$engines = $this->_engines;

		if ($engines === false)
		{
			$dependencyInjector = $this->_dependencyInjector;

			$engines = [];

			$registeredEngines = $this->_registeredEngines;

			if (typeof($registeredEngines) <> "array")
			{
				$engines[".phtml"] = new PhpEngine($this, $dependencyInjector);

			}

			$this->_engines = $engines;

		}

		return $engines;
	}

	protected final function _internalRender($path, $params)
	{

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			$this->_activeRenderPath = $path;

		}

		if (typeof($eventsManager) == "object")
		{
			if ($eventsManager->fire("view:beforeRender", $this) === false)
			{
				return null;
			}

		}

		$notExists = true;
		$mustClean = true;

		$viewsDirPath = $this->_viewsDir . $path;

		$engines = $this->_loadTemplateEngines();

		foreach ($engines as $extension => $engine) {
			if (file_exists($viewsDirPath . $extension))
			{
				$viewEnginePath = $viewsDirPath . $extension;

			}
			if ($viewEnginePath)
			{
				if (typeof($eventsManager) == "object")
				{
					if ($eventsManager->fire("view:beforeRenderView", $this, $viewEnginePath) === false)
					{
						continue;

					}

				}

				$engine->render($viewEnginePath, $params, $mustClean);

				$notExists = false;

				if (typeof($eventsManager) == "object")
				{
					$eventsManager->fire("view:afterRenderView", $this);

				}

				break;

			}
		}

		if ($notExists === true)
		{
			throw new Exception("View '" . $viewsDirPath . "' was not found in the views directory");
		}

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("view:afterRender", $this);

		}

	}

	public function render($path, $params = null)
	{

		$cache = $this->getCache();

		if (typeof($cache) == "object")
		{
			if ($cache->isStarted() === false)
			{
				$key = null;
				$lifetime = null;

				$cacheOptions = $this->_cacheOptions;

				if (typeof($cacheOptions) == "array")
				{
					$key = $cacheOptions["key"]
					$lifetime = $cacheOptions["lifetime"]
				}

				if ($key === null)
				{
					$key = md5($path);

				}

				$content = $cache->start($key, $lifetime);

				if ($content !== null)
				{
					$this->_content = $content;

					return $content;
				}

			}

		}

		create_symbol_table();

		ob_start();

		$viewParams = $this->_viewParams;

		if (typeof($params) == "array")
		{
			if (typeof($viewParams) == "array")
			{
				$mergedParams = array_merge($viewParams, $params);

			}

		}

		$this->_internalRender($path, $mergedParams);

		if (typeof($cache) == "object")
		{
			if ($cache->isStarted() && $cache->isFresh())
			{
				$cache->save();

			}

		}

		ob_end_clean();

		return $this->_content;
	}

	public function partial($partialPath, $params = null)
	{

		ob_start();

		if (typeof($params) == "array")
		{
			$viewParams = $this->_viewParams;

			if (typeof($viewParams) == "array")
			{
				$mergedParams = array_merge($viewParams, $params);

			}

			create_symbol_table();

		}

		$this->_internalRender($partialPath, $mergedParams);

		if (typeof($params) == "array")
		{
			$this->_viewParams = $viewParams;

		}

		ob_end_clean();

		echo($this->_content);

	}

	public function setCacheOptions($options)
	{
		$this->_cacheOptions = $options;

		return $this;
	}

	public function getCacheOptions()
	{
		return $this->_cacheOptions;
	}

	protected function _createCache()
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injector container is required to obtain the view cache services");
		}

		$cacheService = "viewCache";

		$cacheOptions = $this->_cacheOptions;

		if (typeof($cacheOptions) == "array")
		{
			if (isset($cacheOptions["service"]))
			{
				$cacheService = $cacheOptions["service"]
			}

		}

		$viewCache = $dependencyInjector->getShared($cacheService);

		if (typeof($viewCache) <> "object")
		{
			throw new Exception("The injected caching service is invalid");
		}

		return $viewCache;
	}

	public function getCache()
	{
		if ($this->_cache && typeof($this->_cache) <> "object")
		{
			$this->_cache = $this->_createCache();

		}

		return $this->_cache;
	}

	public function cache($options = true)
	{
		if (typeof($options) == "array")
		{
			$this->_cache = true;
			$this->_cacheOptions = $options;

		}

		return $this;
	}

	public function setParamToView($key, $value)
	{
		$this[$key] = $value;

		return $this;
	}

	public function setVars($params, $merge = true)
	{
		if ($merge && typeof($this->_viewParams) == "array")
		{
			$this->_viewParams = array_merge($this->_viewParams, $params);

		}

		return $this;
	}

	public function setVar($key, $value)
	{
		$this[$key] = $value;

		return $this;
	}

	public function getVar($key)
	{

		if (function() { if(isset($this->_viewParams[$key])) {$value = $this->_viewParams[$key]; return $value; } else { return false; } }())
		{
			return $value;
		}

		return null;
	}

	public function getParamsToView()
	{
		return $this->_viewParams;
	}

	public function setContent($content)
	{
		$this->_content = $content;

		return $this;
	}

	public function getContent()
	{
		return $this->_content;
	}

	public function getActiveRenderPath()
	{
		return $this->_activeRenderPath;
	}

	public function __set($key, $value)
	{
		$this[$key] = $value;

	}

	public function __get($key)
	{

		if (function() { if(isset($this->_viewParams[$key])) {$value = $this->_viewParams[$key]; return $value; } else { return false; } }())
		{
			return $value;
		}

		return null;
	}


}