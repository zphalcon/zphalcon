<?php
namespace Phalcon\Forms;

use Phalcon\Forms\Form;
use Phalcon\Validation\MessageInterface;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\Message\Group;

interface ElementInterface
{
	public function setForm($form)
	{
	}

	public function getForm()
	{
	}

	public function setName($name)
	{
	}

	public function getName()
	{
	}

	public function setFilters($filters)
	{
	}

	public function addFilter($filter)
	{
	}

	public function getFilters()
	{
	}

	public function addValidators($validators, $merge = true)
	{
	}

	public function addValidator($validator)
	{
	}

	public function getValidators()
	{
	}

	public function prepareAttributes($attributes = null, $useChecked = false)
	{
	}

	public function setAttribute($attribute, $value)
	{
	}

	public function getAttribute($attribute, $defaultValue = null)
	{
	}

	public function setAttributes($attributes)
	{
	}

	public function getAttributes()
	{
	}

	public function setUserOption($option, $value)
	{
	}

	public function getUserOption($option, $defaultValue = null)
	{
	}

	public function setUserOptions($options)
	{
	}

	public function getUserOptions()
	{
	}

	public function setLabel($label)
	{
	}

	public function getLabel()
	{
	}

	public function label()
	{
	}

	public function setDefault($value)
	{
	}

	public function getDefault()
	{
	}

	public function getValue()
	{
	}

	public function getMessages()
	{
	}

	public function hasMessages()
	{
	}

	public function setMessages($group)
	{
	}

	public function appendMessage($message)
	{
	}

	public function clear()
	{
	}

	public function render($attributes = null)
	{
	}


}