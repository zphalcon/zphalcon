<?php
namespace Phalcon\Forms;

use Phalcon\Tag;
use Phalcon\Forms\Exception;
use Phalcon\Validation\Message;
use Phalcon\Validation\MessageInterface;
use Phalcon\Validation\Message\Group;
use Phalcon\Validation\ValidatorInterface;
abstract 
class Element implements ElementInterface
{
	protected $_form;
	protected $_name;
	protected $_value;
	protected $_label;
	protected $_attributes;
	protected $_validators = [];
	protected $_filters;
	protected $_options;
	protected $_messages;

	public function __construct($name, $attributes = null)
	{
		$name = trim($name);

		if (empty($name))
		{
			throw new \InvalidArgumentException("Form element name is required");
		}

		$this->_name = $name;

		if (typeof($attributes) == "array")
		{
			$this->_attributes = $attributes;

		}

		$this->_messages = new Group();

	}

	public function setForm($form)
	{
		$this->_form = $form;

		return $this;
	}

	public function getForm()
	{
		return $this->_form;
	}

	public function setName($name)
	{
		$this->_name = $name;

		return $this;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function setFilters($filters)
	{
		if (typeof($filters) <> "string" && typeof($filters) <> "array")
		{
			throw new Exception("Wrong filter type added");
		}

		$this->_filters = $filters;

		return $this;
	}

	public function addFilter($filter)
	{

		$filters = $this->_filters;

		if (typeof($filters) == "array")
		{
			$this->_filters[] = $filter;

		}

		return $this;
	}

	public function getFilters()
	{
		return $this->_filters;
	}

	public function addValidators($validators, $merge = true)
	{

		if ($merge)
		{
			$currentValidators = $this->_validators;

			if (typeof($currentValidators) == "array")
			{
				$mergedValidators = array_merge($currentValidators, $validators);

			}

		}

		$this->_validators = $mergedValidators;

		return $this;
	}

	public function addValidator($validator)
	{
		$this->_validators[] = $validator;

		return $this;
	}

	public function getValidators()
	{
		return $this->_validators;
	}

	public function prepareAttributes($attributes = null, $useChecked = false)
	{

		$name = $this->_name;

		if (typeof($attributes) <> "array")
		{
			$widgetAttributes = [];

		}

		$widgetAttributes[0] = $name;

		$defaultAttributes = $this->_attributes;

		if (typeof($defaultAttributes) == "array")
		{
			$mergedAttributes = array_merge($defaultAttributes, $widgetAttributes);

		}

		$value = $this->getValue();

		if ($value !== null)
		{
			if ($useChecked)
			{
				if (function() { if(isset($mergedAttributes["value"])) {$currentValue = $mergedAttributes["value"]; return $currentValue; } else { return false; } }())
				{
					if ($currentValue == $value)
					{
						$mergedAttributes["checked"] = "checked";

					}

				}

			}

		}

		return $mergedAttributes;
	}

	public function setAttribute($attribute, $value)
	{
		$this[$attribute] = $value;

		return $this;
	}

	public function getAttribute($attribute, $defaultValue = null)
	{

		$attributes = $this->_attributes;

		if (function() { if(isset($attributes[$attribute])) {$value = $attributes[$attribute]; return $value; } else { return false; } }())
		{
			return $value;
		}

		return $defaultValue;
	}

	public function setAttributes($attributes)
	{
		$this->_attributes = $attributes;

		return $this;
	}

	public function getAttributes()
	{

		$attributes = $this->_attributes;

		if (typeof($attributes) <> "array")
		{
			return [];
		}

		return $attributes;
	}

	public function setUserOption($option, $value)
	{
		$this[$option] = $value;

		return $this;
	}

	public function getUserOption($option, $defaultValue = null)
	{

		if (function() { if(isset($this->_options[$option])) {$value = $this->_options[$option]; return $value; } else { return false; } }())
		{
			return $value;
		}

		return $defaultValue;
	}

	public function setUserOptions($options)
	{
		$this->_options = $options;

		return $this;
	}

	public function getUserOptions()
	{
		return $this->_options;
	}

	public function setLabel($label)
	{
		$this->_label = $label;

		return $this;
	}

	public function getLabel()
	{
		return $this->_label;
	}

	public function label($attributes = null)
	{

		$internalAttributes = $this->getAttributes();

		if (!(function() { if(isset($internalAttributes["id"])) {$name = $internalAttributes["id"]; return $name; } else { return false; } }()))
		{
			$name = $this->_name;

		}

		if (typeof($attributes) == "array")
		{
			if (!(isset($attributes["for"])))
			{
				$attributes["for"] = $name;

			}

		}

		$code = Tag::renderAttributes("<label", $attributes);

		$label = $this->_label;

		if ($label || is_numeric($label))
		{
			$code .= ">" . $label . "</label>";

		}

		return $code;
	}

	public function setDefault($value)
	{
		$this->_value = $value;

		return $this;
	}

	public function getDefault()
	{
		return $this->_value;
	}

	public function getValue()
	{

		$name = $this->_name;
		$value = null;

		$form = $this->_form;

		if (typeof($form) == "object")
		{
			$value = $form->getValue($name);

			if (typeof($value) == "null" && Tag::hasValue($name))
			{
				$value = Tag::getValue($name);

			}

		}

		if (typeof($value) == "null")
		{
			$value = $this->_value;

		}

		return $value;
	}

	public function getMessages()
	{
		return $this->_messages;
	}

	public function hasMessages()
	{
		return count($this->_messages) > 0;
	}

	public function setMessages($group)
	{
		$this->_messages = $group;

		return $this;
	}

	public function appendMessage($message)
	{
		$this->_messages->appendMessage($message);

		return $this;
	}

	public function clear()
	{
		Tag::setDefault($this->_name, null);

		return $this;
	}

	public function __toString()
	{
		return $this->render();
	}


}