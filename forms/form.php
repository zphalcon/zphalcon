<?php
namespace Phalcon\Forms;

use Phalcon\Validation;
use Phalcon\ValidationInterface;
use Phalcon\DiInterface;
use Phalcon\FilterInterface;
use Phalcon\Di\Injectable;
use Phalcon\Forms\Exception;
use Phalcon\Forms\ElementInterface;
use Phalcon\Validation\Message\Group;

class Form extends Injectable implements \Countable, \Iterator
{
	protected $_position;
	protected $_entity;
	protected $_options = [];
	protected $_data;
	protected $_elements = [];
	protected $_elementsIndexed;
	protected $_messages;
	protected $_action;
	protected $_validation;

	public function __construct($entity = null, $userOptions = null)
	{
		if (typeof($entity) <> "null")
		{
			if (typeof($entity) <> "object")
			{
				throw new Exception("The base entity is not valid");
			}

			$this->_entity = $entity;

		}

		if (typeof($userOptions) == "array")
		{
			$this->_options = $userOptions;

		}

		if (method_exists($this, "initialize"))
		{
			$this->initialize($entity, $userOptions);

		}

	}

	public function setAction($action)
	{
		$this->_action = $action;

		return $this;
	}

	public function getAction()
	{
		return $this->_action;
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

	public function setEntity($entity)
	{
		$this->_entity = $entity;

		return $this;
	}

	public function getEntity()
	{
		return $this->_entity;
	}

	public function getElements()
	{
		return $this->_elements;
	}

	public function bind($data, $entity, $whitelist = null)
	{

		if (empty($this->_elements))
		{
			throw new Exception("There are no elements in the form");
		}

		$filter = null;

		foreach ($data as $key => $value) {
			if (!(function() { if(isset($this->_elements[$key])) {$element = $this->_elements[$key]; return $element; } else { return false; } }()))
			{
				continue;

			}
			if (typeof($whitelist) == "array")
			{
				if (!(in_array($key, $whitelist)))
				{
					continue;

				}

			}
			$filters = $element->getFilters();
			if ($filters)
			{
				if (typeof($filter) <> "object")
				{
					$dependencyInjector = $this->getDI();
					$filter = $dependencyInjector->getShared("filter");

				}

				$filteredValue = $filter->sanitize($value, $filters);

			}
			$method = "set" . camelize($key);
			if (method_exists($entity, $method))
			{
				$entity->method($filteredValue);

				continue;

			}
			$entity->{$key} = $filteredValue;
		}

		$this->_data = $data;

		return $this;
	}

	public function isValid($data = null, $entity = null)
	{

		if (empty($this->_elements))
		{
			return true;
		}

		if (typeof($data) <> "array")
		{
			$data = $this->_data;

		}

		if (typeof($entity) == "object")
		{
			$this->bind($data, $entity);

		}

		if (method_exists($this, "beforeValidation"))
		{
			if ($this->beforeValidation($data, $entity) === false)
			{
				return false;
			}

		}

		$validationStatus = true;

		$validation = $this->getValidation();

		if (typeof($validation) <> "object" || !($validation instanceof $ValidationInterface))
		{
			$validation = new Validation();

		}

		foreach ($this->_elements as $element) {
			$validators = $element->getValidators();
			if (typeof($validators) <> "array" || count($validators) == 0)
			{
				continue;

			}
			$name = $element->getName();
			foreach ($validators as $validator) {
				$validation->add($name, $validator);
			}
			$filters = $element->getFilters();
			if (typeof($filters) == "array")
			{
				$validation->setFilters($name, $filters);

			}
		}

		$messages = $validation->validate($data, $entity);

		if ($messages->count())
		{
			foreach (iterator($messages) as $elementMessage) {
				$this->get($elementMessage->getField())->appendMessage($elementMessage);
			}

			$messages->rewind();

			$validationStatus = false;

		}

		if (!($validationStatus))
		{
			$this->_messages = $messages;

		}

		if (method_exists($this, "afterValidation"))
		{
			$this->afterValidation($messages);

		}

		return $validationStatus;
	}

	public function getMessages($byItemName = false)
	{

		$messages = $this->_messages;

		if (typeof($messages) == "object" && $messages instanceof $Group)
		{
			if ($byItemName)
			{
				$messagesByItem = [];

				$messages->rewind();

				while ($messages->valid()) {
					$elementMessage = $messages->current();
					$fieldName = $elementMessage->getField();
					if (!(isset($messagesByItem[$fieldName])))
					{
						$messagesByItem[$fieldName] = [];

					}
					$messagesByItem[$fieldName][] = new Group([$elementMessage]);
					$messages->next();
				}

				return $messagesByItem;
			}

			return $messages;
		}

		return new Group();
	}

	public function getMessagesFor($name)
	{
		if ($this->has($name))
		{
			return $this->get($name)->getMessages();
		}

		return new Group();
	}

	public function hasMessagesFor($name)
	{
		return $this->getMessagesFor($name)->count() > 0;
	}

	public function add($element, $position = null, $type = null)
	{

		$name = $element->getName();

		$element->setForm($this);

		if ($position == null || empty($this->_elements))
		{
			$this[$name] = $element;

		}

		return $this;
	}

	public function render($name, $attributes = null)
	{

		if (!(function() { if(isset($this->_elements[$name])) {$element = $this->_elements[$name]; return $element; } else { return false; } }()))
		{
			throw new Exception("Element with ID=" . $name . " is not part of the form");
		}

		return $element->render($attributes);
	}

	public function get($name)
	{

		if (function() { if(isset($this->_elements[$name])) {$element = $this->_elements[$name]; return $element; } else { return false; } }())
		{
			return $element;
		}

		throw new Exception("Element with ID=" . $name . " is not part of the form");
	}

	public function label($name, $attributes = null)
	{

		if (function() { if(isset($this->_elements[$name])) {$element = $this->_elements[$name]; return $element; } else { return false; } }())
		{
			return $element->label($attributes);
		}

		throw new Exception("Element with ID=" . $name . " is not part of the form");
	}

	public function getLabel($name)
	{

		if (!(function() { if(isset($this->_elements[$name])) {$element = $this->_elements[$name]; return $element; } else { return false; } }()))
		{
			throw new Exception("Element with ID=" . $name . " is not part of the form");
		}

		$label = $element->getLabel();

		if (!($label))
		{
			return $name;
		}

		return $label;
	}

	public function getValue($name)
	{

		$entity = $this->_entity;

		$data = $this->_data;

		if (method_exists($this, "getCustomValue"))
		{
			return $this->getCustomValue($name, $entity, $data);
		}

		if (typeof($entity) == "object")
		{
			$method = "get" . camelize($name);

			if (method_exists($entity, $method))
			{
				return $entity->method();
			}

			if (function() { if(isset($entity->$name)) {$value = $entity->$name; return $value; } else { return false; } }())
			{
				return $value;
			}

		}

		if (typeof($data) == "array")
		{
			if (function() { if(isset($data[$name])) {$value = $data[$name]; return $value; } else { return false; } }())
			{
				return $value;
			}

		}

		$forbidden = ["validation" => true, "action" => true, "useroption" => true, "useroptions" => true, "entity" => true, "elements" => true, "messages" => true, "messagesfor" => true, "label" => true, "value" => true, "di" => true, "eventsmanager" => true];

		$internal = strtolower($name);

		if (isset($forbidden[$internal]))
		{
			return null;
		}

		$method = "get" . camelize($name);

		if (method_exists($this, $method))
		{
			return $this->method();
		}

		return null;
	}

	public function has($name)
	{
		return isset($this->_elements[$name]);
	}

	public function remove($name)
	{
		if (isset($this->_elements[$name]))
		{
			unset($this->_elements[$name]);

			return true;
		}

		$this->_elementsIndexed = null;

		return false;
	}

	public function clear($fields = null)
	{

		$data = $this->_data;

		if (is_null($fields))
		{
			$data = [];

		}

		$this->_data = $data;
		$elements = $this->_elements;

		if (typeof($elements) == "array")
		{
			foreach ($elements as $element) {
				if (typeof($fields) <> "array")
				{
					$element->clear();

				}
			}

		}

		return $this;
	}

	public function count()
	{
		return count($this->_elements);
	}

	public function rewind()
	{
		$this->_position = 0;

		if (typeof($this->_elements) == "array")
		{
			$this->_elementsIndexed = array_values($this->_elements);

		}

	}

	public function current()
	{

		if (function() { if(isset($this->_elementsIndexed[$this->_position])) {$element = $this->_elementsIndexed[$this->_position]; return $element; } else { return false; } }())
		{
			return $element;
		}

		return false;
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
		return isset($this->_elementsIndexed[$this->_position]);
	}


}