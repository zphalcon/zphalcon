<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Mvc\Model\RelationInterface;

class Relation implements RelationInterface
{
	const BELONGS_TO = 0;
	const HAS_ONE = 1;
	const HAS_MANY = 2;
	const HAS_ONE_THROUGH = 3;
	const HAS_MANY_THROUGH = 4;
	const NO_ACTION = 0;
	const ACTION_RESTRICT = 1;
	const ACTION_CASCADE = 2;

	protected $_type;
	protected $_referencedModel;
	protected $_fields;
	protected $_referencedFields;
	protected $_intermediateModel;
	protected $_intermediateFields;
	protected $_intermediateReferencedFields;
	protected $_options;

	public function __construct($type, $referencedModel, $fields, $referencedFields, $options = null)
	{
		$this->_type = $type;
		$this->_referencedModel = $referencedModel;
		$this->_fields = $fields;
		$this->_referencedFields = $referencedFields;
		$this->_options = $options;

	}

	public function setIntermediateRelation($intermediateFields, $intermediateModel, $intermediateReferencedFields)
	{
		$this->_intermediateFields = $intermediateFields;
		$this->_intermediateModel = $intermediateModel;
		$this->_intermediateReferencedFields = $intermediateReferencedFields;

	}

	public function getType()
	{
		return $this->_type;
	}

	public function getReferencedModel()
	{
		return $this->_referencedModel;
	}

	public function getFields()
	{
		return $this->_fields;
	}

	public function getReferencedFields()
	{
		return $this->_referencedFields;
	}

	public function getOptions()
	{
		return $this->_options;
	}

	public function getOption($name)
	{

		if (function() { if(isset($this->_options[$name])) {$option = $this->_options[$name]; return $option; } else { return false; } }())
		{
			return $option;
		}

		return null;
	}

	public function isForeignKey()
	{
		return isset($this->_options["foreignKey"]);
	}

	public function getForeignKey()
	{

		$options = $this->_options;

		if (typeof($options) == "array")
		{
			if (function() { if(isset($options["foreignKey"])) {$foreignKey = $options["foreignKey"]; return $foreignKey; } else { return false; } }())
			{
				if ($foreignKey)
				{
					return $foreignKey;
				}

			}

		}

		return false;
	}

	public function getParams()
	{

		$options = $this->_options;

		if (typeof($options) == "array")
		{
			if (function() { if(isset($options["params"])) {$params = $options["params"]; return $params; } else { return false; } }())
			{
				if ($params)
				{
					return $params;
				}

			}

		}

		return false;
	}

	public function isThrough()
	{

		$type = $this->_type;

		return $type == self::HAS_ONE_THROUGH || $type == self::HAS_MANY_THROUGH;
	}

	public function isReusable()
	{

		$options = $this->_options;

		if (typeof($options) == "array")
		{
			if (function() { if(isset($options["reusable"])) {$reusable = $options["reusable"]; return $reusable; } else { return false; } }())
			{
				return $reusable;
			}

		}

		return false;
	}

	public function getIntermediateFields()
	{
		return $this->_intermediateFields;
	}

	public function getIntermediateModel()
	{
		return $this->_intermediateModel;
	}

	public function getIntermediateReferencedFields()
	{
		return $this->_intermediateReferencedFields;
	}


}