<?php
namespace Phalcon\Assets;


class Inline implements ResourceInterface
{
	protected $_type;
	protected $_content;
	protected $_filter;
	protected $_attributes;

	public function __construct($type, $content, $filter = true, $attributes = null)
	{
		$this->_type = $type;
		$this->_content = $content;
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

	public function getAttributes()
	{
		return $this->_attributes;
	}

	public function getResourceKey()
	{

		$key = $this->getType() . ":" . $this->getContent();

		return md5($key);
	}


}