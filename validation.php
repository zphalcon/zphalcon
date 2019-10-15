<?php
namespace Phalcon;

use Phalcon\Di\Injectable;
use Phalcon\ValidationInterface;
use Phalcon\Validation\Exception;
use Phalcon\Validation\Message\Group;
use Phalcon\Validation\MessageInterface;
use Phalcon\Validation\ValidatorInterface;
use Phalcon\Validation\CombinedFieldsValidator;

class Validation extends Injectable implements ValidationInterface
{
	protected $_data;
	protected $_entity;
	protected $_validators = [];
	protected $_combinedFieldsValidators = [];
	protected $_filters = [];
	protected $_messages;
	protected $_defaultMessages;
	protected $_labels = [];
	protected $_values;

	public function __construct($validators = null)
	{
		if (count($validators))
		{
			$this->_validators = array_filter($validators, function($element) {
		return typeof($element[0]) <> "array" || !($element[1] instanceof $CombinedFieldsValidator);
});

			$this->_combinedFieldsValidators = array_filter($validators, function($element) {
		return typeof($element[0]) == "array" && $element[1] instanceof $CombinedFieldsValidator;
});

		}

		$this->setDefaultMessages();

		if (method_exists($this, "initialize"))
		{
			$this->initialize();

		}

	}

	public function validate($data = null, $entity = null)
	{

		$validators = $this->_validators;

		$combinedFieldsValidators = $this->_combinedFieldsValidators;

		if (typeof($validators) <> "array")
		{
			throw new Exception("There are no validators to validate");
		}

		$this->_values = null;

		$messages = new Group();

		if ($entity !== null)
		{
			$this->setEntity($entity);

		}

		if (method_exists($this, "beforeValidation"))
		{
			$status = $this->beforeValidation($data, $entity, $messages);

			if ($status === false)
			{
				return $status;
			}

		}

		$this->_messages = $messages;

		if ($data !== null)
		{
			if (typeof($data) == "array" || typeof($data) == "object")
			{
				$this->_data = $data;

			}

		}

		foreach ($validators as $scope) {
			if (typeof($scope) <> "array")
			{
				throw new Exception("The validator scope is not valid");
			}
			$field = $scope[0];
			$validator = $scope[1];
			if (typeof($validator) <> "object")
			{
				throw new Exception("One of the validators is not valid");
			}
			if ($this->preChecking($field, $validator))
			{
				continue;

			}
			if ($validator->validate($this, $field) === false)
			{
				if ($validator->getOption("cancelOnFail"))
				{
					break;

				}

			}
		}

		foreach ($combinedFieldsValidators as $scope) {
			if (typeof($scope) <> "array")
			{
				throw new Exception("The validator scope is not valid");
			}
			$field = $scope[0];
			$validator = $scope[1];
			if (typeof($validator) <> "object")
			{
				throw new Exception("One of the validators is not valid");
			}
			if ($this->preChecking($field, $validator))
			{
				continue;

			}
			if ($validator->validate($this, $field) === false)
			{
				if ($validator->getOption("cancelOnFail"))
				{
					break;

				}

			}
		}

		if (method_exists($this, "afterValidation"))
		{
			$this->afterValidation($data, $entity, $this->_messages);

		}

		return $this->_messages;
	}

	public function add($field, $validator)
	{

		if (typeof($field) == "array")
		{
			if ($validator instanceof $CombinedFieldsValidator)
			{
				$this->_combinedFieldsValidators[] = [$field, $validator];

			}

		}

		return $this;
	}

	public function rule($field, $validator)
	{
		return $this->add($field, $validator);
	}

	public function rules($field, $validators)
	{

		foreach ($validators as $validator) {
			if ($validator instanceof $ValidatorInterface)
			{
				$this->add($field, $validator);

			}
		}

		return $this;
	}

	public function setFilters($field, $filters)
	{

		if (typeof($field) == "array")
		{
			foreach ($field as $singleField) {
				$this[$singleField] = $filters;
			}

		}

		return $this;
	}

	public function getFilters($field = null)
	{

		$filters = $this->_filters;

		if ($field === null || $field === "")
		{
			return $filters;
		}

		if (!(function() { if(isset($filters[$field])) {$fieldFilters = $filters[$field]; return $fieldFilters; } else { return false; } }()))
		{
			return null;
		}

		return $fieldFilters;
	}

	public function getValidators()
	{
		return $this->_validators;
	}

	public function setEntity($entity)
	{
		if (typeof($entity) <> "object")
		{
			throw new Exception("Entity must be an object");
		}

		$this->_entity = $entity;

	}

	public function getEntity()
	{
		return $this->_entity;
	}

	public function setDefaultMessages($messages = [])
	{

		$defaultMessages = ["Alnum" => "Field :field must contain only letters and numbers", "Alpha" => "Field :field must contain only letters", "Between" => "Field :field must be within the range of :min to :max", "Confirmation" => "Field :field must be the same as :with", "Digit" => "Field :field must be numeric", "Email" => "Field :field must be an email address", "ExclusionIn" => "Field :field must not be a part of list: :domain", "FileEmpty" => "Field :field must not be empty", "FileIniSize" => "File :field exceeds the maximum file size", "FileMaxResolution" => "File :field must not exceed :max resolution", "FileMinResolution" => "File :field must be at least :min resolution", "FileSize" => "File :field exceeds the size of :max", "FileType" => "File :field must be of type: :types", "FileValid" => "Field :field is not valid", "Identical" => "Field :field does not have the expected value", "InclusionIn" => "Field :field must be a part of list: :domain", "Numericality" => "Field :field does not have a valid numeric format", "PresenceOf" => "Field :field is required", "Regex" => "Field :field does not match the required format", "TooLong" => "Field :field must not exceed :max characters long", "TooShort" => "Field :field must be at least :min characters long", "Uniqueness" => "Field :field must be unique", "Url" => "Field :field must be a url", "CreditCard" => "Field :field is not valid for a credit card number", "Date" => "Field :field is not a valid date"];

		$this->_defaultMessages = array_merge($defaultMessages, $messages);

		return $this->_defaultMessages;
	}

	public function getDefaultMessage($type)
	{

		if (function() { if(isset($this->_defaultMessages[$type])) {$defaultMessage = $this->_defaultMessages[$type]; return $defaultMessage; } else { return false; } }())
		{
			return $defaultMessage;
		}

		return "";
	}

	public function getMessages()
	{
		return $this->_messages;
	}

	public function setLabels($labels)
	{
		$this->_labels = $labels;

	}

	public function getLabel($field)
	{

		$labels = $this->_labels;

		if (typeof($field) == "array")
		{
			return join(", ", $field);
		}

		if (function() { if(isset($labels[$field])) {$value = $labels[$field]; return $value; } else { return false; } }())
		{
			return $value;
		}

		return $field;
	}

	public function appendMessage($message)
	{

		$messages = $this->_messages;

		if (typeof($messages) <> "object")
		{
			$messages = new Group();

		}

		$messages->appendMessage($message);

		$this->_messages = $messages;

		return $this;
	}

	public function bind($entity, $data)
	{
		if (typeof($entity) <> "object")
		{
			throw new Exception("Entity must be an object");
		}

		if (typeof($data) <> "array" && typeof($data) <> "object")
		{
			throw new Exception("Data to validate must be an array or object");
		}

		$this->_entity = $entity;
		$this->_data = $data;

		return $this;
	}

	public function getValue($field)
	{

		$entity = $this->_entity;

		if (typeof($entity) == "object")
		{
			$camelizedField = camelize($field);

			$method = "get" . $camelizedField;

			if (method_exists($entity, $method))
			{
				$value = $entity->method();

			}

		}

		if (typeof($value) == "null")
		{
			return null;
		}

		$filters = $this->_filters;

		if (function() { if(isset($filters[$field])) {$fieldFilters = $filters[$field]; return $fieldFilters; } else { return false; } }())
		{
			if ($fieldFilters)
			{
				$dependencyInjector = $this->getDI();

				if (typeof($dependencyInjector) <> "object")
				{
					$dependencyInjector = Di::getDefault();

					if (typeof($dependencyInjector) <> "object")
					{
						throw new Exception("A dependency injector is required to obtain the 'filter' service");
					}

				}

				$filterService = $dependencyInjector->getShared("filter");

				if (typeof($filterService) <> "object")
				{
					throw new Exception("Returned 'filter' service is invalid");
				}

				$value = $filterService->sanitize($value, $fieldFilters);

				if (typeof($entity) == "object")
				{
					$method = "set" . $camelizedField;

					if (method_exists($entity, $method))
					{
						$entity->method($value);

					}

				}

				return $value;
			}

		}

		if (typeof($entity) <> "object")
		{
			$this[$field] = $value;

		}

		return $value;
	}

	protected function preChecking($field, $validator)
	{

		if (typeof($field) == "array")
		{
			foreach ($field as $singleField) {
				$result = $this->preChecking($singleField, $validator);
				if ($result)
				{
					return $result;
				}
			}

		}

		return false;
	}


}