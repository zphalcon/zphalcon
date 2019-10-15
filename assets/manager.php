<?php
namespace Phalcon\Assets;

use Phalcon\Tag;
use Phalcon\Assets\Resource;
use Phalcon\Assets\Collection;
use Phalcon\Assets\Exception;
use Phalcon\Assets\Resource\Js as ResourceJs;
use Phalcon\Assets\Resource\Css as ResourceCss;
use Phalcon\Assets\Inline\Css as InlineCss;
use Phalcon\Assets\Inline\Js as InlineJs;

class Manager
{
	protected $_options;
	protected $_collections;
	protected $_implicitOutput = true;

	public function __construct($options = null)
	{
		if (typeof($options) == "array")
		{
			$this->_options = $options;

		}

	}

	public function setOptions($options)
	{
		$this->_options = $options;

		return $this;
	}

	public function getOptions()
	{
		return $this->_options;
	}

	public function useImplicitOutput($implicitOutput)
	{
		$this->_implicitOutput = $implicitOutput;

		return $this;
	}

	public function addCss($path, $local = true, $filter = true, $attributes = null)
	{
		$this->addResourceByType("css", new ResourceCss($path, $local, $filter, $attributes));

		return $this;
	}

	public function addInlineCss($content, $filter = true, $attributes = null)
	{
		$this->addInlineCodeByType("css", new InlineCss($content, $filter, $attributes));

		return $this;
	}

	public function addJs($path, $local = true, $filter = true, $attributes = null)
	{
		$this->addResourceByType("js", new ResourceJs($path, $local, $filter, $attributes));

		return $this;
	}

	public function addInlineJs($content, $filter = true, $attributes = null)
	{
		$this->addInlineCodeByType("js", new InlineJs($content, $filter, $attributes));

		return $this;
	}

	public function addResourceByType($type, $resource)
	{

		if (!(function() { if(isset($this->_collections[$type])) {$collection = $this->_collections[$type]; return $collection; } else { return false; } }()))
		{
			$collection = new Collection();

			$this[$type] = $collection;

		}

		$collection->add($resource);

		return $this;
	}

	public function addInlineCodeByType($type, $code)
	{

		if (!(function() { if(isset($this->_collections[$type])) {$collection = $this->_collections[$type]; return $collection; } else { return false; } }()))
		{
			$collection = new Collection();

			$this[$type] = $collection;

		}

		$collection->addInline($code);

		return $this;
	}

	public function addResource($resource)
	{
		$this->addResourceByType($resource->getType(), $resource);

		return $this;
	}

	public function addInlineCode($code)
	{
		$this->addInlineCodeByType($code->getType(), $code);

		return $this;
	}

	public function set($id, $collection)
	{
		$this[$id] = $collection;

		return $this;
	}

	public function get($id)
	{

		if (!(function() { if(isset($this->_collections[$id])) {$collection = $this->_collections[$id]; return $collection; } else { return false; } }()))
		{
			throw new Exception("The collection does not exist in the manager");
		}

		return $collection;
	}

	public function getCss()
	{

		if (!(function() { if(isset($this->_collections["css"])) {$collection = $this->_collections["css"]; return $collection; } else { return false; } }()))
		{
			return new Collection();
		}

		return $collection;
	}

	public function getJs()
	{

		if (!(function() { if(isset($this->_collections["js"])) {$collection = $this->_collections["js"]; return $collection; } else { return false; } }()))
		{
			return new Collection();
		}

		return $collection;
	}

	public function collection($name)
	{

		if (!(function() { if(isset($this->_collections[$name])) {$collection = $this->_collections[$name]; return $collection; } else { return false; } }()))
		{
			$collection = new Collection();

			$this[$name] = $collection;

		}

		return $collection;
	}

	public function collectionResourcesByType($resources, $type)
	{

		foreach ($resources as $resource) {
			if ($resource->getType() == $type)
			{
				$filtered = $resource;

			}
		}

		return $filtered;
	}

	public function output($collection, $callback, $type)
	{

		$useImplicitOutput = $this->_implicitOutput;

		$output = "";

		$resources = $this->collectionResourcesByType($collection->getResources(), $type);

		$filters = $collection->getFilters();

		$prefix = $collection->getPrefix();

		$typeCss = "css";

		if (count($filters))
		{
			$options = $this->_options;

			if (typeof($options) == "array")
			{
				$sourceBasePath = $options["sourceBasePath"]
				$targetBasePath = $options["targetBasePath"]
			}

			$collectionSourcePath = $collection->getSourcePath();

			if ($collectionSourcePath)
			{
				$completeSourcePath = $sourceBasePath . $collectionSourcePath;

			}

			$collectionTargetPath = $collection->getTargetPath();

			if ($collectionTargetPath)
			{
				$completeTargetPath = $targetBasePath . $collectionTargetPath;

			}

			$filteredJoinedContent = "";

			$join = $collection->getJoin();

			if ($join)
			{
				if (!($completeTargetPath))
				{
					throw new Exception("Path '" . $completeTargetPath . "' is not a valid target path (1)");
				}

				if (is_dir($completeTargetPath))
				{
					throw new Exception("Path '" . $completeTargetPath . "' is not a valid target path (2), is dir.");
				}

			}

		}

		foreach ($resources as $resource) {
			$filterNeeded = false;
			$type = $resource->getType();
			$local = $resource->getLocal();
			if (count($filters))
			{
				if ($local)
				{
					$sourcePath = $resource->getRealSourcePath($completeSourcePath);

					if (!($sourcePath))
					{
						$sourcePath = $resource->getPath();

						throw new Exception("Resource '" . $sourcePath . "' does not have a valid source path");
					}

				}

				$targetPath = $resource->getRealTargetPath($completeTargetPath);

				if (!($targetPath))
				{
					throw new Exception("Resource '" . $sourcePath . "' does not have a valid target path");
				}

				if ($local)
				{
					if ($targetPath == $sourcePath)
					{
						throw new Exception("Resource '" . $targetPath . "' have the same source and target paths");
					}

					if (file_exists($targetPath))
					{
						if (compare_mtime($targetPath, $sourcePath))
						{
							$filterNeeded = true;

						}

					}

				}

			}
			if ($filterNeeded == true)
			{
				$content = $resource->getContent($completeSourcePath);

				$mustFilter = $resource->getFilter();

				if ($mustFilter == true)
				{
					foreach ($filters as $filter) {
						if (typeof($filter) <> "object")
						{
							throw new Exception("Filter is invalid");
						}
						$filteredContent = $filter->filter($content);
						$content = $filteredContent;
					}

					if ($join == true)
					{
						if ($type == $typeCss)
						{
							$filteredJoinedContent .= $filteredContent;

						}

					}

				}

				if (!($join))
				{
					file_put_contents($targetPath, $filteredContent);

				}

			}
			if (!($join))
			{
				$path = $resource->getRealTargetUri();

				if ($prefix)
				{
					$prefixedPath = $prefix . $path;

				}

				$attributes = $resource->getAttributes();

				$local = true;

				$parameters = [];

				if (typeof($attributes) == "array")
				{
					$attributes[0] = $prefixedPath;

					$parameters = $attributes;

				}

				$parameters = $local;

				$html = call_user_func_array($callback, $parameters);

				if ($useImplicitOutput == true)
				{
					echo($html);

				}

			}
		}

		if (count($filters))
		{
			if ($join == true)
			{
				file_put_contents($completeTargetPath, $filteredJoinedContent);

				$targetUri = $collection->getTargetUri();

				if ($prefix)
				{
					$prefixedPath = $prefix . $targetUri;

				}

				$attributes = $collection->getAttributes();

				$local = $collection->getTargetLocal();

				$parameters = [];

				if (typeof($attributes) == "array")
				{
					$attributes[0] = $prefixedPath;

					$parameters = $attributes;

				}

				$parameters = $local;

				$html = call_user_func_array($callback, $parameters);

				if ($useImplicitOutput == true)
				{
					echo($html);

				}

			}

		}

		return $output;
	}

	public function outputInline($collection, $type)
	{

		$output = "";
		$html = "";
		$joinedContent = "";

		$codes = $collection->getCodes();
		$filters = $collection->getFilters();
		$join = $collection->getJoin();

		if (count($codes))
		{
			foreach ($codes as $code) {
				$attributes = $code->getAttributes();
				$content = $code->getContent();
				foreach ($filters as $filter) {
					if (typeof($filter) <> "object")
					{
						throw new Exception("Filter is invalid");
					}
					$content = $filter->filter($content);
				}
				if ($join)
				{
					$joinedContent .= $content;

				}
			}

			if ($join)
			{
				$html .= Tag::tagHtml($type, $attributes, false, true) . $joinedContent . Tag::tagHtmlClose($type, true);

			}

			if ($this->_implicitOutput == true)
			{
				echo($html);

			}

		}

		return $output;
	}

	public function outputCss($collectionName = null)
	{

		if (!($collectionName))
		{
			$collection = $this->getCss();

		}

		return $this->output($collection, ["Phalcon\\Tag", "stylesheetLink"], "css");
	}

	public function outputInlineCss($collectionName = null)
	{

		if (!($collectionName))
		{
			$collection = $this->getCss();

		}

		return $this->outputInline($collection, "style");
	}

	public function outputJs($collectionName = null)
	{

		if (!($collectionName))
		{
			$collection = $this->getJs();

		}

		return $this->output($collection, ["Phalcon\\Tag", "javascriptInclude"], "js");
	}

	public function outputInlineJs($collectionName = null)
	{

		if (!($collectionName))
		{
			$collection = $this->getJs();

		}

		return $this->outputInline($collection, "script");
	}

	public function getCollections()
	{
		return $this->_collections;
	}

	public function exists($id)
	{
		return isset($this->_collections[$id]);
	}


}