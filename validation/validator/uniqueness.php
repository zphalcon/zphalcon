<?php
namespace Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\CombinedFieldsValidator;
use Phalcon\Validation\Exception;
use Phalcon\Validation\Message;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\CollectionInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Collection;

class Uniqueness extends CombinedFieldsValidator
{
	private $columnMap = null;

	public function validate($validation, $field)
	{

		if (!($this->isUniqueness($validation, $field)))
		{
			$label = $this->getOption("label");
			$message = $this->getOption("message");

			if (empty($label))
			{
				$label = $validation->getLabel($field);

			}

			if (empty($message))
			{
				$message = $validation->getDefaultMessage("Uniqueness");

			}

			$validation->appendMessage(new Message(strtr($message, [":field" => $label]), $field, "Uniqueness", $this->getOption("code")));

			return false;
		}

		return true;
	}

	protected function isUniqueness($validation, $field)
	{

		if (typeof($field) <> "array")
		{
			$singleField = $field;
			$field = [];

			$field = $singleField;

		}

		$values = [];
		$convert = $this->getOption("convert");

		foreach ($field as $singleField) {
			$values[$singleField] = $validation->getValue($singleField);
		}

		if ($convert <> null)
		{
			$values = convert($values);

			if (!(is_array($values)))
			{
				throw new Exception("Value conversion must return an array");
			}

		}

		$record = $this->getOption("model");

		if (empty($record) || typeof($record) <> "object")
		{
			$record = $validation->getEntity();

			if (empty($record))
			{
				throw new Exception("Model of record must be set to property \"model\"");
			}

		}

		$isModel = $record instanceof $ModelInterface;
		$isDocument = $record instanceof $CollectionInterface;

		if ($isModel)
		{
			$params = $this->isUniquenessModel($record, $field, $values);

		}

		$className = get_class($record);

		return $className::count($params) == 0;
	}

	protected function getColumnNameReal($record, $field)
	{
		if (globals_get("orm.column_renaming") && !($this->columnMap))
		{
			$this->columnMap = $record->getDI()->getShared("modelsMetadata")->getColumnMap($record);

		}

		if (typeof($this->columnMap) == "array" && isset($this->columnMap[$field]))
		{
			return $this->columnMap[$field];
		}

		return $field;
	}

	protected function isUniquenessModel($record, $field, $values)
	{

		$exceptConditions = [];
		$index = 0;
		$params = ["conditions" => [], "bind" => []];
		$except = $this->getOption("except");

		foreach ($field as $singleField) {
			$fieldExcept = null;
			$notInValues = [];
			$value = $values[$singleField];
			$attribute = $this->getOption("attribute", $singleField);
			$attribute = $this->getColumnNameReal($record, $attribute);
			if ($value <> null)
			{
				$params["conditions"][] = $attribute . " = ?" . $index;

				$params["bind"][] = $value;

				$index++;

			}
			if ($except)
			{
				if (typeof($except) == "array" && array_keys($except) !== range(0, count($except) - 1))
				{
					foreach ($except as $singleField => $fieldExcept) {
						$attribute = $this->getColumnNameReal($record, $this->getOption("attribute", $singleField));
						if (typeof($fieldExcept) == "array")
						{
							foreach ($fieldExcept as $singleExcept) {
								$notInValues = "?" . $index;
								$params["bind"][] = $singleExcept;
								$index++;
							}

							$exceptConditions = $attribute . " NOT IN (" . join(",", $notInValues) . ")";

						}
					}

				}

			}
		}

		if ($record->getDirtyState() == Model::DIRTY_STATE_PERSISTENT)
		{
			$metaData = $record->getDI()->getShared("modelsMetadata");

			foreach ($metaData->getPrimaryKeyAttributes($record) as $primaryField) {
				$params["conditions"][] = $this->getColumnNameReal($record, $primaryField) . " <> ?" . $index;
				$params["bind"][] = $record->readAttribute($this->getColumnNameReal($record, $primaryField));
				$index++;
			}

		}

		if (!(empty($exceptConditions)))
		{
			$params["conditions"][] = "(" . join(" OR ", $exceptConditions) . ")";

		}

		$params["conditions"] = join(" AND ", $params["conditions"]);

		return $params;
	}

	protected function isUniquenessCollection($record, $field, $values)
	{

		$exceptConditions = [];

		$params = ["conditions" => []];

		foreach ($field as $singleField) {
			$fieldExcept = null;
			$notInValues = [];
			$value = $values[$singleField];
			$except = $this->getOption("except");
			if ($value <> null)
			{
				$params["conditions"] = $value;

			}
			if ($except)
			{
				if (typeof($except) == "array" && count($field) > 1)
				{
					if (isset($except[$singleField]))
					{
						$fieldExcept = $except[$singleField];

					}

				}

				if ($fieldExcept <> null)
				{
					if (typeof($fieldExcept) == "array")
					{
						foreach ($fieldExcept as $singleExcept) {
							$notInValues = $singleExcept;
						}


						$exceptConditions[$singleField] = $arrayValue;

					}

				}

			}
		}

		if ($record->getDirtyState() == Collection::DIRTY_STATE_PERSISTENT)
		{

			$params["conditions"] = $arrayValue;

		}

		if (!(empty($exceptConditions)))
		{
			$params["conditions"] = [$exceptConditions];

		}

		return $params;
	}


}