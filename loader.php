<?php
namespace Phalcon;

use Phalcon\Loader\Exception;
use Phalcon\Events\ManagerInterface;
use Phalcon\Events\EventsAwareInterface;

class Loader implements EventsAwareInterface
{
	protected $_eventsManager = null;
	protected $_foundPath = null;
	protected $_checkedPath = null;
	protected $_classes = [];
	protected $_extensions = ["php"];
	protected $_namespaces = [];
	protected $_directories = [];
	protected $_files = [];
	protected $_registered = false;
	protected $fileCheckingCallback = "is_file";

	public function setFileCheckingCallback($callback = null)
	{
		if (is_callable($callback))
		{
			$this->fileCheckingCallback = $callback;

		}

		return $this;
	}

	public function setEventsManager($eventsManager)
	{
		$this->_eventsManager = $eventsManager;

	}

	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	public function setExtensions($extensions)
	{
		$this->_extensions = $extensions;

		return $this;
	}

	public function getExtensions()
	{
		return $this->_extensions;
	}

	public function registerNamespaces($namespaces, $merge = false)
	{

		$preparedNamespaces = $this->prepareNamespace($namespaces);

		if ($merge)
		{
			foreach ($preparedNamespaces as $name => $paths) {
				if (!(isset($this->_namespaces[$name])))
				{
					$this[$name] = [];

				}
				$this[$name] = array_merge($this->_namespaces[$name], $paths);
			}

		}

		return $this;
	}

	protected function prepareNamespace($namespace)
	{

		$prepared = [];

		foreach ($namespace as $name => $paths) {
			if (typeof($paths) <> "array")
			{
				$localPaths = [$paths];

			}
			$prepared[$name] = $localPaths;
		}

		return $prepared;
	}

	public function getNamespaces()
	{
		return $this->_namespaces;
	}

	public function registerDirs($directories, $merge = false)
	{
		if ($merge)
		{
			$this->_directories = array_merge($this->_directories, $directories);

		}

		return $this;
	}

	public function getDirs()
	{
		return $this->_directories;
	}

	public function registerFiles($files, $merge = false)
	{
		if ($merge)
		{
			$this->_files = array_merge($this->_files, $files);

		}

		return $this;
	}

	public function getFiles()
	{
		return $this->_files;
	}

	public function registerClasses($classes, $merge = false)
	{
		if ($merge)
		{
			$this->_classes = array_merge($this->_classes, $classes);

		}

		return $this;
	}

	public function getClasses()
	{
		return $this->_classes;
	}

	public function register($prepend = false)
	{
		if ($this->_registered === false)
		{
			$this->loadFiles();

			spl_autoload_register([$this, "autoLoad"], true, $prepend);

			$this->_registered = true;

		}

		return $this;
	}

	public function unregister()
	{
		if ($this->_registered === true)
		{
			spl_autoload_unregister([$this, "autoLoad"]);

			$this->_registered = false;

		}

		return $this;
	}

	public function loadFiles()
	{

		$fileCheckingCallback = $this->fileCheckingCallback;

		foreach ($this->_files as $filePath) {
			if (typeof($this->_eventsManager) == "object")
			{
				$this->_checkedPath = $filePath;

				$this->_eventsManager->fire("loader:beforeCheckPath", $this, $filePath);

			}
			if (call_user_func($fileCheckingCallback, $filePath))
			{
				if (typeof($this->_eventsManager) == "object")
				{
					$this->_foundPath = $filePath;

					$this->_eventsManager->fire("loader:pathFound", $this, $filePath);

				}

				require($filePath);

			}
		}

	}

	public function autoLoad($className)
	{

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("loader:beforeCheckClass", $this, $className);

		}

		$classes = $this->_classes;

		if (function() { if(isset($classes[$className])) {$filePath = $classes[$className]; return $filePath; } else { return false; } }())
		{
			if (typeof($eventsManager) == "object")
			{
				$this->_foundPath = $filePath;

				$eventsManager->fire("loader:pathFound", $this, $filePath);

			}

			require($filePath);

			return true;
		}

		$extensions = $this->_extensions;

		$ds = DIRECTORY_SEPARATOR;
		$ns = "\\";

		$namespaces = $this->_namespaces;

		$fileCheckingCallback = $this->fileCheckingCallback;

		foreach ($namespaces as $nsPrefix => $directories) {
			if (!(starts_with($className, $nsPrefix)))
			{
				continue;

			}
			$fileName = substr($className, strlen($nsPrefix . $ns));
			if (!($fileName))
			{
				continue;

			}
			$fileName = str_replace($ns, $ds, $fileName);
			foreach ($directories as $directory) {
				$fixedDirectory = rtrim($directory, $ds) . $ds;
				foreach ($extensions as $extension) {
					$filePath = $fixedDirectory . $fileName . "." . $extension;
					if (typeof($eventsManager) == "object")
					{
						$this->_checkedPath = $filePath;

						$eventsManager->fire("loader:beforeCheckPath", $this);

					}
					if (call_user_func($fileCheckingCallback, $filePath))
					{
						if (typeof($eventsManager) == "object")
						{
							$this->_foundPath = $filePath;

							$eventsManager->fire("loader:pathFound", $this, $filePath);

						}

						require($filePath);

						return true;
					}
				}
			}
		}

		$nsClassName = str_replace($ns, $ds, $className);

		$directories = $this->_directories;

		foreach ($directories as $directory) {
			$fixedDirectory = rtrim($directory, $ds) . $ds;
			foreach ($extensions as $extension) {
				$filePath = $fixedDirectory . $nsClassName . "." . $extension;
				if (typeof($eventsManager) == "object")
				{
					$this->_checkedPath = $filePath;

					$eventsManager->fire("loader:beforeCheckPath", $this, $filePath);

				}
				if (call_user_func($fileCheckingCallback, $filePath))
				{
					if (typeof($eventsManager) == "object")
					{
						$this->_foundPath = $filePath;

						$eventsManager->fire("loader:pathFound", $this, $filePath);

					}

					require($filePath);

					return true;
				}
			}
		}

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("loader:afterCheckClass", $this, $className);

		}

		return false;
	}

	public function getFoundPath()
	{
		return $this->_foundPath;
	}

	public function getCheckedPath()
	{
		return $this->_checkedPath;
	}


}