<?php
namespace Phalcon\Assets;


class Resource implements ResourceInterface
{
	protected $_type;
	protected $_path;
	protected $_local;
	protected $_filter;
	protected $_attributes;
	protected $_sourcePath;
	protected $_targetPath;
	protected $_targetUri;

	public function __construct($type, $path, $local = true, $filter = true, $attributes = null)
	{
		$this->_type = $type;
		$this->_path = $path;
		$this->_local = $local;
		$this->_filter = $filter;

		if (typeof($attributes) == "array")
		{
			$this->_attributes = $attributes;

		}

	}

	public function setType($type)
	{
		$this->_type = $type;

		return $this;
	}

	public function setPath($path)
	{
		$this->_path = $path;

		return $this;
	}

	public function setLocal($local)
	{
		$this->_local = $local;

		return $this;
	}

	public function setFilter($filter)
	{
		$this->_filter = $filter;

		return $this;
	}

	public function setAttributes($attributes)
	{
		$this->_attributes = $attributes;

		return $this;
	}

	public function setTargetUri($targetUri)
	{
		$this->_targetUri = $targetUri;

		return $this;
	}

	public function setSourcePath($sourcePath)
	{
		$this->_sourcePath = $sourcePath;

		return $this;
	}

	public function setTargetPath($targetPath)
	{
		$this->_targetPath = $targetPath;

		return $this;
	}

	public function getContent($basePath = null)
	{

		$sourcePath = $this->_sourcePath;

		if (empty($sourcePath))
		{
			$sourcePath = $this->_path;

		}

		$completePath = $basePath . $sourcePath;

		if ($this->_local)
		{
			if (!(file_exists($completePath)))
			{
				throw new Exception("Resource's content for '" . $completePath . "' cannot be read");
			}

		}

		$content = file_get_contents($completePath);

		if ($content === false)
		{
			throw new Exception("Resource's content for '" . $completePath . "' cannot be read");
		}

		return $content;
	}

	public function getRealTargetUri()
	{

		$targetUri = $this->_targetUri;

		if (empty($targetUri))
		{
			$targetUri = $this->_path;

		}

		return $targetUri;
	}

	public function getRealSourcePath($basePath = null)
	{

		$sourcePath = $this->_sourcePath;

		if (empty($sourcePath))
		{
			$sourcePath = $this->_path;

		}

		if ($this->_local)
		{
			return realpath($basePath . $sourcePath);
		}

		return $sourcePath;
	}

	public function getRealTargetPath($basePath = null)
	{

		$targetPath = $this->_targetPath;

		if (empty($targetPath))
		{
			$targetPath = $this->_path;

		}

		if ($this->_local)
		{
			$completePath = $basePath . $targetPath;

			if (file_exists($completePath))
			{
				return realpath($completePath);
			}

			return $completePath;
		}

		return $targetPath;
	}

	public function getResourceKey()
	{

		$key = $this->getType() . ":" . $this->getPath();

		return md5($key);
	}


}