<?php
namespace Phalcon;

use Phalcon\Di\Injectable;
use Phalcon\Validation\Exception;
use Phalcon\Validation\MessageInterface;
use Phalcon\Validation\Message\Group;
use Phalcon\Validation\ValidatorInterface;

interface ValidationInterface
{
	public function validate($data = null, $entity = null)
	{
	}

	public function add($field, $validator)
	{
	}

	public function rule($field, $validator)
	{
	}

	public function rules($field, $validators)
	{
	}

	public function setFilters($field, $filters)
	{
	}

	public function getFilters($field = null)
	{
	}

	public function getValidators()
	{
	}

	public function getEntity()
	{
	}

	public function setDefaultMessages($messages = [])
	{
	}

	public function getDefaultMessage($type)
	{
	}

	public function getMessages()
	{
	}

	public function setLabels($labels)
	{
	}

	public function getLabel($field)
	{
	}

	public function appendMessage($message)
	{
	}

	public function bind($entity, $data)
	{
	}

	public function getValue($field)
	{
	}


}