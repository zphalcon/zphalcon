<?php
namespace Phalcon\Mvc\Model\MetaData\Strategy;

use Phalcon\DiInterface;
use Phalcon\Db\Column;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\MetaData\StrategyInterface;

class Introspection implements StrategyInterface
{
	public final function getMetaData($model, $dependencyInjector)
	{

		$schema = $model->getSchema();
		$table = $model->getSource();

		$readConnection = $model->getReadConnection();

		if (!($readConnection->tableExists($table, $schema)))
		{
			if ($schema)
			{
				$completeTable = $schema . "'.'" . $table;

			}

			throw new Exception("Table '" . $completeTable . "' doesn't exist in database when dumping meta-data for " . get_class($model));
		}

		$columns = $readConnection->describeColumns($table, $schema);

		if (!(count($columns)))
		{
			if ($schema)
			{
				$completeTable = $schema . "'.'" . $table;

			}

			throw new Exception("Cannot obtain table columns for the mapped source '" . $completeTable . "' used in model " . get_class($model));
		}

		$attributes = [];

		$primaryKeys = [];

		$nonPrimaryKeys = [];

		$numericTyped = [];

		$notNull = [];

		$fieldTypes = [];

		$fieldBindTypes = [];

		$automaticDefault = [];

		$identityField = false;

		$defaultValues = [];

		$emptyStringValues = [];

		foreach ($columns as $column) {
			$fieldName = $column->getName();
			$attributes = $fieldName;
			if ($column->isPrimary() === true)
			{
				$primaryKeys = $fieldName;

			}
			if ($column->isNumeric() === true)
			{
				$numericTyped[$fieldName] = true;

			}
			if ($column->isNotNull() === true)
			{
				$notNull = $fieldName;

			}
			if ($column->isAutoIncrement() === true)
			{
				$identityField = $fieldName;

			}
			$fieldTypes[$fieldName] = $column->getType();
			$fieldBindTypes[$fieldName] = $column->getBindType();
			$defaultValue = $column->getDefault();
			if ($defaultValue !== null || $column->isNotNull() === false)
			{
				if (!($column->isAutoIncrement()))
				{
					$defaultValues[$fieldName] = $defaultValue;

				}

			}
		}

		return [MetaData::MODELS_ATTRIBUTES => $attributes, MetaData::MODELS_PRIMARY_KEY => $primaryKeys, MetaData::MODELS_NON_PRIMARY_KEY => $nonPrimaryKeys, MetaData::MODELS_NOT_NULL => $notNull, MetaData::MODELS_DATA_TYPES => $fieldTypes, MetaData::MODELS_DATA_TYPES_NUMERIC => $numericTyped, MetaData::MODELS_IDENTITY_COLUMN => $identityField, MetaData::MODELS_DATA_TYPES_BIND => $fieldBindTypes, MetaData::MODELS_AUTOMATIC_DEFAULT_INSERT => $automaticDefault, MetaData::MODELS_AUTOMATIC_DEFAULT_UPDATE => $automaticDefault, MetaData::MODELS_DEFAULT_VALUES => $defaultValues, MetaData::MODELS_EMPTY_STRING_VALUES => $emptyStringValues];
	}

	public final function getColumnMaps($model, $dependencyInjector)
	{

		$orderedColumnMap = null;

		$reversedColumnMap = null;

		if (method_exists($model, "columnMap"))
		{
			$userColumnMap = $model->columnMap();

			if (typeof($userColumnMap) <> "array")
			{
				throw new Exception("columnMap() not returned an array");
			}

			$reversedColumnMap = [];
			$orderedColumnMap = $userColumnMap;

			foreach ($userColumnMap as $name => $userName) {
				$reversedColumnMap[$userName] = $name;
			}

		}

		return [$orderedColumnMap, $reversedColumnMap];
	}


}