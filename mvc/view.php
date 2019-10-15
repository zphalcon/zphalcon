<?php
namespace Phalcon\Mvc;

use Phalcon\DiInterface;
use Phalcon\Di\Injectable;
use Phalcon\Mvc\View\Exception;
use Phalcon\Mvc\ViewInterface;
use Phalcon\Cache\BackendInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Mvc\View\Engine\Php as PhpEngine;

class View extends Injectable implements ViewInterface
{
	const LEVEL_MAIN_LAYOUT = 5;
	const LEVEL_AFTER_TEMPLATE = 4;
	const LEVEL_LAYOUT = 3;
	const LEVEL_BEFORE_TEMPLATE = 2;
	const LEVEL_ACTION_VIEW = 1;
	const LEVEL_NO_RENDER = 0;
	const CACHE_MODE_NONE = 0;
	const CACHE_MODE_INVERSE = 1;

	protected $_options;
	protected $_basePath = "";
	protected $_content = "";
	protected $_renderLevel = 5;
	protected $_currentRenderLevel = 0;
	protected $_disabledLevels;
	protected $_viewParams = [];
	protected $_layout;
	protected $_layoutsDir = "";
	protected $_partialsDir = "";
	protected $_viewsDirs = [];
	protected $_templatesBefore = [];
	protected $_templatesAfter = [];
	protected $_engines = false;
	protected $_registeredEngines;
	protected $_mainView = "index";
	protected $_controllerName;
	protected $_actionName;
	protected $_params;
	protected $_pickView;
	protected $_cache;
	protected $_cacheLevel = 0;
	protected $_activeRenderPaths;
	protected $_disabled = false;

	public function __construct($options = [])
	{
		$this->_options = $options;

	}

	protected final function _isAbsolutePath($path)
	{
		if (PHP_OS == "WINNT")
		{
			return strlen($path) >= 3 && $path[1] == ":" && $path[2] == "\\";
		}

		return strlen($path) >= 1 && $path[0] == "/";
	}

	public function setViewsDir($viewsDir)
	{

		if (typeof($viewsDir) <> "string" && typeof($viewsDir) <> "array")
		{
			throw new Exception("Views directory must be a string or an array");
		}

		$directorySeparator = DIRECTORY_SEPARATOR;

		if (typeof($viewsDir) == "string")
		{
			if (substr($viewsDir, -1) <> $directorySeparator)
			{
				$viewsDir = $viewsDir . $directorySeparator;

			}

			$this->_viewsDirs = $viewsDir;

		}

		return $this;
	}

	public function getViewsDir()
	{
		return $this->_viewsDirs;
	}

	public function setLayoutsDir($layoutsDir)
	{
		$this->_layoutsDir = $layoutsDir;

		return $this;
	}

	public function getLayoutsDir()
	{
		return $this->_layoutsDir;
	}

	public function setPartialsDir($partialsDir)
	{
		$this->_partialsDir = $partialsDir;

		return $this;
	}

	public function getPartialsDir()
	{
		return $this->_partialsDir;
	}

	public function setBasePath($basePath)
	{
		$this->_basePath = $basePath;

		return $this;
	}

	public function getBasePath()
	{
		return $this->_basePath;
	}

	public function setRenderLevel($level)
	{
		$this->_renderLevel = $level;

		return $this;
	}

	public function disableLevel($level)
	{
		if (typeof($level) == "array")
		{
			$this->_disabledLevels = $level;

		}

		return $this;
	}

	public function setMainView($viewPath)
	{
		$this->_mainView = $viewPath;

		return $this;
	}

	public function getMainView()
	{
		return $this->_mainView;
	}

	public function setLayout($layout)
	{
		$this->_layout = $layout;

		return $this;
	}

	public function getLayout()
	{
		return $this->_layout;
	}

	public function setTemplateBefore($templateBefore)
	{
		if (typeof($templateBefore) <> "array")
		{
			$this->_templatesBefore = [$templateBefore];

		}

		return $this;
	}

	public function cleanTemplateBefore()
	{
		$this->_templatesBefore = [];

		return $this;
	}

	public function setTemplateAfter($templateAfter)
	{
		if (typeof($templateAfter) <> "array")
		{
			$this->_templatesAfter = [$templateAfter];

		}

		return $this;
	}

	public function cleanTemplateAfter()
	{
		$this->_templatesAfter = [];

		return $this;
	}

	public function setParamToView($key, $value)
	{
		$this[$key] = $value;

		return $this;
	}

	public function setVars($params, $merge = true)
	{
		if ($merge)
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

		if (!(function() { if(isset($this->_viewParams[$key])) {$value = $this->_viewParams[$key]; return $value; } else { return false; } }()))
		{
			return null;
		}

		return $value;
	}

	public function getParamsToView()
	{
		return $this->_viewParams;
	}

	public function getControllerName()
	{
		return $this->_controllerName;
	}

	public function getActionName()
	{
		return $this->_actionName;
	}

	deprecated public function getParams()
	{
		return $this->_params;
	}

	public function start()
	{
		ob_start();

		$this->_content = null;

		return $this;
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

	protected function _engineRender($engines, $viewPath, $silence, $mustClean, $cache = null)
	{



		$notExists = true;
		$basePath = $this->_basePath;
		$viewParams = $this->_viewParams;
		$eventsManager = $this->_eventsManager;
		$viewEnginePaths = [];

		foreach ($this->getViewsDirs() as $viewsDir) {
			if (!($this->_isAbsolutePath($viewPath)))
			{
				$viewsDirPath = $basePath . $viewsDir . $viewPath;

			}
			if (typeof($cache) == "object")
			{
				$renderLevel = (int) $this->_renderLevel;
				$cacheLevel = (int) $this->_cacheLevel;

				if ($renderLevel >= $cacheLevel)
				{
					if (!($cache->isStarted()))
					{
						$key = null;
						$lifetime = null;

						$viewOptions = $this->_options;

						if (function() { if(isset($viewOptions["cache"])) {$cacheOptions = $viewOptions["cache"]; return $cacheOptions; } else { return false; } }())
						{
							if (typeof($cacheOptions) == "array")
							{
								$key = $cacheOptions["key"]
								$lifetime = $cacheOptions["lifetime"]
							}

						}

						if ($key === null)
						{
							$key = md5($viewPath);

						}

						$cachedView = $cache->start($key, $lifetime);

						if ($cachedView !== null)
						{
							$this->_content = $cachedView;

							return null;
						}

					}

					if (!($cache->isFresh()))
					{
						return null;
					}

				}

			}
			foreach ($engines as $extension => $engine) {
				$viewEnginePath = $viewsDirPath . $extension;
				if (file_exists($viewEnginePath))
				{
					if (typeof($eventsManager) == "object")
					{
						$this->_activeRenderPaths = [$viewEnginePath];

						if ($eventsManager->fire("view:beforeRenderView", $this, $viewEnginePath) === false)
						{
							continue;

						}

					}

					$engine->render($viewEnginePath, $viewParams, $mustClean);

					$notExists = false;

					if (typeof($eventsManager) == "object")
					{
						$eventsManager->fire("view:afterRenderView", $this);

					}

					break;

				}
				$viewEnginePaths = $viewEnginePath;
			}
		}

		if ($notExists === true)
		{
			if (typeof($eventsManager) == "object")
			{
				$this->_activeRenderPaths = $viewEnginePaths;

				$eventsManager->fire("view:notFoundView", $this, $viewEnginePath);

			}

			if (!($silence))
			{
				throw new Exception("View '" . $viewPath . "' was not found in any of the views directory");
			}

		}

	}

	public function registerEngines($engines)
	{
		$this->_registeredEngines = $engines;

		return $this;
	}

	public function exists($view)
	{

		$basePath = $this->_basePath;
		$engines = $this->_registeredEngines;

		if (typeof($engines) <> "array")
		{
			$engines = [".phtml" => "Phalcon\\Mvc\\View\\Engine\\Php"];
			$this->_registeredEngines = $engines;

		}

		foreach ($this->getViewsDirs() as $viewsDir) {
			foreach ($engines as $extension => $_) {
				if (file_exists($basePath . $viewsDir . $view . $extension))
				{
					return true;
				}
			}
		}

		return false;
	}

	public function render($controllerName, $actionName, $params = null)
	{



		$this->_currentRenderLevel = 0;

		if ($this->_disabled !== false)
		{
			$this->_content = ob_get_contents();

			return false;
		}

		$this->_controllerName = $controllerName;
		$this->_actionName = $actionName;

		if (typeof($params) == "array")
		{
			$this->setVars($params);

		}

		$layoutsDir = $this->_layoutsDir;

		if (!($layoutsDir))
		{
			$layoutsDir = "layouts/";

		}

		$layout = $this->_layout;

		if ($layout)
		{
			$layoutName = $layout;

		}

		$engines = $this->_loadTemplateEngines();

		$pickView = $this->_pickView;

		if ($pickView === null)
		{
			$renderView = $controllerName . "/" . $actionName;

		}

		if ($this->_cacheLevel)
		{
			$cache = $this->getCache();

		}

		$eventsManager = $this->_eventsManager;

		create_symbol_table();

		if (typeof($eventsManager) == "object")
		{
			if ($eventsManager->fire("view:beforeRender", $this) === false)
			{
				return false;
			}

		}

		$this->_content = ob_get_contents();

		$mustClean = true;
		$silence = true;

		$disabledLevels = $this->_disabledLevels;

		$renderLevel = (int) $this->_renderLevel;

		if ($renderLevel)
		{
			if ($renderLevel >= self::LEVEL_ACTION_VIEW)
			{
				if (!(isset($disabledLevels[self::LEVEL_ACTION_VIEW])))
				{
					$this->_currentRenderLevel = self::LEVEL_ACTION_VIEW;

					$this->_engineRender($engines, $renderView, $silence, $mustClean, $cache);

				}

			}

			if ($renderLevel >= self::LEVEL_BEFORE_TEMPLATE)
			{
				if (!(isset($disabledLevels[self::LEVEL_BEFORE_TEMPLATE])))
				{
					$this->_currentRenderLevel = self::LEVEL_BEFORE_TEMPLATE;

					$templatesBefore = $this->_templatesBefore;

					$silence = false;

					foreach ($templatesBefore as $templateBefore) {
						$this->_engineRender($engines, $layoutsDir . $templateBefore, $silence, $mustClean, $cache);
					}

					$silence = true;

				}

			}

			if ($renderLevel >= self::LEVEL_LAYOUT)
			{
				if (!(isset($disabledLevels[self::LEVEL_LAYOUT])))
				{
					$this->_currentRenderLevel = self::LEVEL_LAYOUT;

					$this->_engineRender($engines, $layoutsDir . $layoutName, $silence, $mustClean, $cache);

				}

			}

			if ($renderLevel >= self::LEVEL_AFTER_TEMPLATE)
			{
				if (!(isset($disabledLevels[self::LEVEL_AFTER_TEMPLATE])))
				{
					$this->_currentRenderLevel = self::LEVEL_AFTER_TEMPLATE;

					$templatesAfter = $this->_templatesAfter;

					$silence = false;

					foreach ($templatesAfter as $templateAfter) {
						$this->_engineRender($engines, $layoutsDir . $templateAfter, $silence, $mustClean, $cache);
					}

					$silence = true;

				}

			}

			if ($renderLevel >= self::LEVEL_MAIN_LAYOUT)
			{
				if (!(isset($disabledLevels[self::LEVEL_MAIN_LAYOUT])))
				{
					$this->_currentRenderLevel = self::LEVEL_MAIN_LAYOUT;

					$this->_engineRender($engines, $this->_mainView, $silence, $mustClean, $cache);

				}

			}

			$this->_currentRenderLevel = 0;

			if (typeof($cache) == "object")
			{
				if ($cache->isStarted() && $cache->isFresh())
				{
					$cache->save();

				}

			}

		}

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("view:afterRender", $this);

		}

		return $this;
	}

	public function pick($renderView)
	{

		if (typeof($renderView) == "array")
		{
			$pickView = $renderView;

		}

		$this->_pickView = $pickView;

		return $this;
	}

	public function getPartial($partialPath, $params = null)
	{
		ob_start();

		$this->partial($partialPath, $params);

		return ob_get_clean();
	}

	public function partial($partialPath, $params = null)
	{

		if (typeof($params) == "array")
		{
			$viewParams = $this->_viewParams;

			$this->_viewParams = array_merge($viewParams, $params);

			create_symbol_table();

		}

		$this->_engineRender($this->_loadTemplateEngines(), $this->_partialsDir . $partialPath, false, false);

		if (typeof($params) == "array")
		{
			$this->_viewParams = $viewParams;

		}

	}

	public function getRender($controllerName, $actionName, $params = null, $configCallback = null)
	{

		$view = clone $this;

		$view->reset();

		if (typeof($params) == "array")
		{
			$view->setVars($params);

		}

		if (typeof($configCallback) == "object")
		{
			call_user_func_array($configCallback, [$view]);

		}

		$view->start();

		$view->render($controllerName, $actionName);

		ob_end_clean();

		return $view->getContent();
	}

	public function finish()
	{
		ob_end_clean();

		return $this;
	}

	protected function _createCache()
	{

		$dependencyInjector = $this->_dependencyInjector;

		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("A dependency injector container is required to obtain the view cache services");
		}

		$cacheService = "viewCache";

		$viewOptions = $this->_options;

		if (function() { if(isset($viewOptions["cache"])) {$cacheOptions = $viewOptions["cache"]; return $cacheOptions; } else { return false; } }())
		{
			if (isset($cacheOptions["service"]))
			{
				$cacheService = $cacheOptions["service"];

			}

		}

		$viewCache = $dependencyInjector->getShared($cacheService);

		if (typeof($viewCache) <> "object")
		{
			throw new Exception("The injected caching service is invalid");
		}

		return $viewCache;
	}

	public function isCaching()
	{
		return $this->_cacheLevel > 0;
	}

	public function getCache()
	{
		if (!($this->_cache) || typeof($this->_cache) <> "object")
		{
			$this->_cache = $this->_createCache();

		}

		return $this->_cache;
	}

	public function cache($options = true)
	{

		if (typeof($options) == "array")
		{
			$viewOptions = $this->_options;

			if (typeof($viewOptions) <> "array")
			{
				$viewOptions = [];

			}

			if (!(function() { if(isset($viewOptions["cache"])) {$cacheOptions = $viewOptions["cache"]; return $cacheOptions; } else { return false; } }()))
			{
				$cacheOptions = [];

			}

			foreach ($options as $key => $value) {
				$cacheOptions[$key] = $value;
			}

			if (function() { if(isset($cacheOptions["level"])) {$cacheLevel = $cacheOptions["level"]; return $cacheLevel; } else { return false; } }())
			{
				$this->_cacheLevel = $cacheLevel;

			}

			$viewOptions["cache"] = $cacheOptions;

			$this->_options = $viewOptions;

		}

		return $this;
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


		$viewsDirsCount = count($this->getViewsDirs());
		$activeRenderPath = $this->_activeRenderPaths;

		if ($viewsDirsCount === 1)
		{
			if (typeof($activeRenderPath) == "array")
			{
				if (count($activeRenderPath))
				{
					$activeRenderPath = $activeRenderPath[0];

				}

			}

		}

		if (typeof($activeRenderPath) == "null")
		{
			$activeRenderPath = "";

		}

		return $activeRenderPath;
	}

	public function disable()
	{
		$this->_disabled = true;

		return $this;
	}

	public function enable()
	{
		$this->_disabled = false;

		return $this;
	}

	public function reset()
	{
		$this->_disabled = false;
		$this->_engines = false;
		$this->_cache = null;
		$this->_renderLevel = self::LEVEL_MAIN_LAYOUT;
		$this->_cacheLevel = self::LEVEL_NO_RENDER;
		$this->_content = null;
		$this->_templatesBefore = [];
		$this->_templatesAfter = [];

		return $this;
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

	public function isDisabled()
	{
		return $this->_disabled;
	}

	public function __isset($key)
	{
		return isset($this->_viewParams[$key]);
	}

	protected function getViewsDirs()
	{
		if (typeof($this->_viewsDirs) === "string")
		{
			return [$this->_viewsDirs];
		}

		return $this->_viewsDirs;
	}


}