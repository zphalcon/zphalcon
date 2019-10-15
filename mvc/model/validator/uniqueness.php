<?php
namespace Phalcon\Mvc\Model\Validator;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\Validator;

class Uniqueness extends Validator
{
	public function validate($record)
	{

		$dependencyInjector = $record->getDI();

		$metaData = $dependencyInjector->getShared("modelsMetadata");

		$bindTypes = [];

		$bindDataTypes = $metaData->getBindTypes($record);

		if (globals_get("orm.column_renaming"))
		{
			$columnMap = $metaData->getReverseColumnMap($record);

		}

		$conditions = [];

		$bindParams = [];

		$number = 0;

		$field = $this->getOption("field");

		if (typeof($field) == "array")
		{
			foreach ($field as $composeField) {
				if (typeof($columnMap) == "array")
				{
					if (!(function() { if(isset($columnMap[$composeField])) {$columnField = $columnMap[$composeField]; return $columnField; } else { return false; } }()))
					{
						throw new Exception("Column '" . $composeField . "' isn't part of the column map");
					}

				}
				if (!(function() { if(isset($bindDataTypes[$columnField])) {$bindType = $bindDataTypes[$columnField]; return $bindType; } else { return false; } }()))
				{
					throw new Exception("Column '" . $columnField . "' isn't part of the table columns");
				}
				$conditions = "[" . $composeField . "] = ?" . $number;
				$bindParams = $record->readAttribute($composeField);
				$bindTypes = $bindType;
				$number++;
			}

		}

		if ($record->getOperationMade() == Model::OP_UPDATE)
		{
			if (globals_get("orm.column_renaming"))
			{
				$columnMap = $metaData->getColumnMap($record);

			}

			foreach ($metaData->getPrimaryKeyAttributes($record) as $primaryField) {
				if (!(function() { if(isset($bindDataTypes[$primaryField])) {$bindType = $bindDataTypes[$primaryField]; return $bindType; } else { return false; } }()))
				{
					throw new Exception("Column '" . $primaryField . "' isn't part of the table columns");
				}
				if (typeof($columnMap) == "array")
				{
					if (!(function() { if(isset($columnMap[$primaryField])) {$attributeField = $columnMap[$primaryField]; return $attributeField; } else { return false; } }()))
					{
						throw new Exception("Column '" . $primaryField . "' isn't part of the column map");
					}

				}
				$conditions = "[" . $attributeField . "] <> ?" . $number;
				$bindParams = $record->readAttribute($primaryField);
				$bindTypes = $bindType;
				$number++;
			}

		}

		$params = [];

		$params["di"] = $dependencyInjector;

		$params["conditions"] = join(" AND ", $conditions);

		$params["bind"] = $bindParams;

		$params["bindTypes"] = $bindTypes;

		$className = get_class($record);

		if ($className::count($params) <> 0)
		{
			$message = $this->getOption("message");

			if (typeof($field) == "array")
			{
				$replacePairs = [":fields" => join(", ", $field)];

				if (empty($message))
				{
					$message = "Value of fields: :fields are already present in another record";

				}

			}

			$this->appendMessage(strtr($message, $replacePairs), $field, "Unique");

			return false;
		}

		return true;
	}


}