<?php
namespace Phalcon\Forms\Element;

use Phalcon\Tag\Select;
use Phalcon\Forms\Element;

class Select extends Element
{
	protected $_optionsValues;

	public function __construct($name, $options = null, $attributes = null)
	{
		$this->_optionsValues = $options;

		parent::__construct($name, $attributes);

	}

	public function setOptions($options)
	{
		$this->_optionsValues = $options;

		return $this;
	}

	public function getOptions()
	{
		return $this->_optionsValues;
	}

	public function addOption($option)
	{

		if (typeof($option) == "array")
		{
			foreach ($option as $key => $value) {
				$this[$key] = $value;
			}

		}

		return $this;
	}

	public function render($attributes = null)
	{
		return Select::selectField($this->prepareAttributes($attributes), $this->_optionsValues);
	}


}