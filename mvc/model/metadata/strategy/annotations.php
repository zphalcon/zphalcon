<?php
namespace Phalcon\Mvc\Model\MetaData\Strategy;

use Phalcon\DiInterface;
use Phalcon\Db\Column;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\MetaData\StrategyInterface;
use Phalcon\Mvc\Model\Exception;

class Annotations implements StrategyInterface
{
	public final function getMetaData($model, $dependencyInjector)
	{



		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("The dependency injector is invalid");
		}

		$annotations = $dependencyInjector->get("annotations");

		$className = get_class($model);
		$reflection = $annotations->get($className);

		if (typeof($reflection) <> "object")
		{
			throw new Exception("No annotations were found in class " . $className);
		}

		$propertiesAnnotations = $reflection->getPropertiesAnnotations();

		if (!(count($propertiesAnnotations)))
		{
			throw new Exception("No properties with annotations were found in class " . $className);
		}

		$attributes = [];
		$primaryKeys = [];
		$nonPrimaryKeys = [];
		$numericTyped = [];
		$notNull = [];
		$fieldTypes = [];
		$fieldBindTypes = [];
		$identityField = false;
		$skipOnInsert = [];
		$skipOnUpdate = [];
		$defaultValues = [];
		$emptyStringValues = [];

		foreach ($propertiesAnnotations as $property => $propAnnotations) {
			if (!($propAnnotations->has("Column")))
			{
				continue;

			}
			$columnAnnotation = $propAnnotations->get("Column");
			$columnName = $columnAnnotation->getNamedParameter("column");
			if (empty($columnName))
			{
				$columnName = $property;

			}
			$feature = $columnAnnotation->getNamedParameter("type");
			switch ($feature) {
				case "biginteger":
					$fieldTypes[$columnName] = Column::TYPE_BIGINTEGER;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_INT;
					$numericTyped[$columnName] = true;
					break;
				case "integer":
					$fieldTypes[$columnName] = Column::TYPE_INTEGER;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_INT;
					$numericTyped[$columnName] = true;
					break;
				case "decimal":
					$fieldTypes[$columnName] = Column::TYPE_DECIMAL;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_DECIMAL;
					$numericTyped[$columnName] = true;
					break;
				case "float":
					$fieldTypes[$columnName] = Column::TYPE_FLOAT;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_DECIMAL;
					$numericTyped[$columnName] = true;
					break;
				case "double":
					$fieldTypes[$columnName] = Column::TYPE_DOUBLE;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_DECIMAL;
					$numericTyped[$columnName] = true;
					break;
				case "boolean":
					$fieldTypes[$columnName] = Column::TYPE_BOOLEAN;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_BOOL;
					break;
				case "date":
					$fieldTypes[$columnName] = Column::TYPE_DATE;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_STR;
					break;
				case "datetime":
					$fieldTypes[$columnName] = Column::TYPE_DATETIME;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_STR;
					break;
				case "timestamp":
					$fieldTypes[$columnName] = Column::TYPE_TIMESTAMP;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_STR;
					break;
				case "text":
					$fieldTypes[$columnName] = Column::TYPE_TEXT;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_STR;
					break;
				case "char":
					$fieldTypes[$columnName] = Column::TYPE_CHAR;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_STR;
					break;
				case "json":
					$fieldTypes[$columnName] = Column::TYPE_JSON;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_STR;
					break;
				case "tinyblob":
					$fieldTypes[$columnName] = Column::TYPE_TINYBLOB;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_BLOB;
					break;
				case "blob":
					$fieldTypes[$columnName] = Column::TYPE_BLOB;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_BLOB;
					break;
				case "mediumblob":
					$fieldTypes[$columnName] = Column::TYPE_MEDIUMBLOB;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_BLOB;
					break;
				case "longblob":
					$fieldTypes[$columnName] = Column::TYPE_LONGBLOB;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_BLOB;
					break;
				default:
					$fieldTypes[$columnName] = Column::TYPE_VARCHAR;
					$fieldBindTypes[$columnName] = Column::BIND_PARAM_STR;

			}
			if ($propAnnotations->has("Primary"))
			{
				$primaryKeys = $columnName;

			}
			if ($propAnnotations->has("Identity"))
			{
				$identityField = $columnName;

			}
			if ($columnAnnotation->getNamedParameter("skip_on_insert"))
			{
				$skipOnInsert = $columnName;

			}
			if ($columnAnnotation->getNamedParameter("skip_on_update"))
			{
				$skipOnUpdate = $columnName;

			}
			if ($columnAnnotation->getNamedParameter("allow_empty_string"))
			{
				$emptyStringValues = $columnName;

			}
			if (!($columnAnnotation->getNamedParameter("nullable")))
			{
				$notNull = $columnName;

			}
			$defaultValue = $columnAnnotation->getNamedParameter("default");
			if ($defaultValue !== null || $columnAnnotation->getNamedParameter("nullable"))
			{
				$defaultValues[$columnName] = $defaultValue;

			}
			$attributes = $columnName;
		}

		return [MetaData::MODELS_ATTRIBUTES => $attributes, MetaData::MODELS_PRIMARY_KEY => $primaryKeys, MetaData::MODELS_NON_PRIMARY_KEY => $nonPrimaryKeys, MetaData::MODELS_NOT_NULL => $notNull, MetaData::MODELS_DATA_TYPES => $fieldTypes, MetaData::MODELS_DATA_TYPES_NUMERIC => $numericTyped, MetaData::MODELS_IDENTITY_COLUMN => $identityField, MetaData::MODELS_DATA_TYPES_BIND => $fieldBindTypes, MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT => $skipOnInsert, MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE => $skipOnUpdate, MetaData::MODELS_DEFAULT_VALUES => $defaultValues, MetaData::MODELS_EMPTY_STRING_VALUES => $emptyStringValues];
	}

	public final function getColumnMaps($model, $dependencyInjector)
	{



		if (typeof($dependencyInjector) <> "object")
		{
			throw new Exception("The dependency injector is invalid");
		}

		$annotations = $dependencyInjector->get("annotations");

		$className = get_class($model);
		$reflection = $annotations->get($className);

		if (typeof($reflection) <> "object")
		{
			throw new Exception("No annotations were found in class " . $className);
		}

		$propertiesAnnotations = $reflection->getPropertiesAnnotations();

		if (!(count($propertiesAnnotations)))
		{
			throw new Exception("No properties with annotations were found in class " . $className);
		}

		$orderedColumnMap = [];
		$reversedColumnMap = [];
		$hasReversedColumn = false;

		foreach ($propertiesAnnotations as $property => $propAnnotations) {
			if (!($propAnnotations->has("Column")))
			{
				continue;

			}
			$columnAnnotation = $propAnnotations->get("Column");
			$columnName = $columnAnnotation->getNamedParameter("column");
			if (empty($columnName))
			{
				$columnName = $property;

			}
			$orderedColumnMap[$columnName] = $property;
			$reversedColumnMap[$property] = $columnName;
			if (!($hasReversedColumn) && $columnName <> $property)
			{
				$hasReversedColumn = true;

			}
		}

		if (!($hasReversedColumn))
		{
			return [null, null];
		}

		return [$orderedColumnMap, $reversedColumnMap];
	}


}