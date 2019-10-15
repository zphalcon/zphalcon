<?php
namespace Phalcon\Assets;

use Phalcon\Assets\Resource;
use Phalcon\Assets\FilterInterface;
use Phalcon\Assets\Inline;
use Phalcon\Assets\Resource\Css as ResourceCss;
use Phalcon\Assets\Resource\Js as ResourceJs;
use Phalcon\Assets\Inline\Js as InlineJs;
use Phalcon\Assets\Inline\Css as InlineCss;

class Collection implements \Countable, \Iterator
{
	protected $_prefix;
	protected $_local = true;
	protected $_resources = [];
	protected $_codes = [];
	protected $_position;
	protected $_filters = [];
	protected $_attributes = [];
	protected $_join = true;
	protected $_targetUri;
	protected $_targetPath;
	protected $_targetLocal = true;
	protected $_sourcePath;
	protected $_includedResources;

	public function __construct()
	{
		$this->_includedResources = [];

	}

	public function add($resource)
	{
		$this->addResource($resource);

		return $this;
	}

	public function addInline($code)
	{
		$this->addResource($code);

		return $this;
	}

	public function has($resource)
	{

		$key = $resource->getResourceKey();
		$resources = $this->_includedResources;

		return in_array($key, $resources);
	}

	public function addCss($path, $local = null, $filter = true, $attributes = null)
	{

		if (typeof($local) == "boolean")
		{
			$collectionLocal = $local;

		}

		if (typeof($attributes) == "array")
		{
			$collectionAttributes = $attributes;

		}

		$this->add(new ResourceCss($path, $collectionLocal, $filter, $collectionAttributes));

		return $this;
	}

	public function addInlineCss($content, $filter = true, $attributes = null)
	{

		if (typeof($attributes) == "array")
		{
			$collectionAttributes = $attributes;

		}

		$this->_codes[] = new InlineCss($content, $filter, $collectionAttributes);

		return $this;
	}

	public function addJs($path, $local = null, $filter = true, $attributes = null)
	{

		if (typeof($local) == "boolean")
		{
			$collectionLocal = $local;

		}

		if (typeof($attributes) == "array")
		{
			$collectionAttributes = $attributes;

		}

		$this->add(new ResourceJs($path, $collectionLocal, $filter, $collectionAttributes));

		return $this;
	}

	public function addInlineJs($content, $filter = true, $attributes = null)
	{

		if (typeof($attributes) == "array")
		{
			$collectionAttributes = $attributes;

		}

		$this->_codes[] = new InlineJs($content, $filter, $collectionAttributes);

		return $this;
	}

	public function count()
	{
		return count($this->_resources);
	}

	public function rewind()
	{
		$this->_position = 0;

	}

	public function current()
	{
		return $this->_resources[$this->_position];
	}

	public function key()
	{
		return $this->_position;
	}

	public function next()
	{
		$this->_position++;
	}

	public function valid()
	{
		return isset($this->_resources[$this->_position]);
	}

	public function setTargetPath($targetPath)
	{
		$this->_targetPath = $targetPath;

		return $this;
	}

	public function setSourcePath($sourcePath)
	{
		$this->_sourcePath = $sourcePath;

		return $this;
	}

	public function setTargetUri($targetUri)
	{
		$this->_targetUri = $targetUri;

		return $this;
	}

	public function setPrefix($prefix)
	{
		$this->_prefix = $prefix;

		return $this;
	}

	public function setLocal($local)
	{
		$this->_local = $local;

		return $this;
	}

	public function setAttributes($attributes)
	{
		$this->_attributes = $attributes;

		return $this;
	}

	public function setFilters($filters)
	{
		$this->_filters = $filters;

		return $this;
	}

	public function setTargetLocal($targetLocal)
	{
		$this->_targetLocal = $targetLocal;

		return $this;
	}

	public function join($join)
	{
		$this->_join = $join;

		return $this;
	}

	public function getRealTargetPath($basePath)
	{

		$targetPath = $this->_targetPath;

		$completePath = $basePath . $targetPath;

		if (file_exists($completePath))
		{
			return realPath($completePath);
		}

		return $completePath;
	}

	public function addFilter($filter)
	{
		$this->_filters[] = $filter;

		return $this;
	}

	protected final function addResource($resource)
	{
		if (!($this->has($resource)))
		{
			if ($resource instanceof $Resource)
			{
				$this->_resources[] = $resource;

			}

			$this->_includedResources[] = $resource->getResourceKey();

			return true;
		}

		return false;
	}


}