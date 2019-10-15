<?php
namespace Phalcon\Db\Dialect;

use Phalcon\Db\Dialect;
use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\ReferenceInterface;
use Phalcon\Db\DialectInterface;

class Mysql extends Dialect
{
	protected $_escapeChar = "`";

	public function getColumnDefinition($column)
	{

		$columnSql = "";

		$type = $column->getType();

		if (typeof($type) == "string")
		{
			$columnSql .= $type;

			$type = $column->getTypeReference();

		}

		switch ($type) {
			case Column::TYPE_INTEGER:
				if (empty($columnSql))
				{
					$columnSql .= "INT";

				}
				$columnSql .= "(" . $column->getSize() . ")";
				if ($column->isUnsigned())
				{
					$columnSql .= " UNSIGNED";

				}
				break;

			case Column::TYPE_DATE:
				if (empty($columnSql))
				{
					$columnSql .= "DATE";

				}
				break;

			case Column::TYPE_VARCHAR:
				if (empty($columnSql))
				{
					$columnSql .= "VARCHAR";

				}
				$columnSql .= "(" . $column->getSize() . ")";
				break;

			case Column::TYPE_DECIMAL:
				if (empty($columnSql))
				{
					$columnSql .= "DECIMAL";

				}
				$columnSql .= "(" . $column->getSize() . "," . $column->getScale() . ")";
				if ($column->isUnsigned())
				{
					$columnSql .= " UNSIGNED";

				}
				break;

			case Column::TYPE_DATETIME:
				if (empty($columnSql))
				{
					$columnSql .= "DATETIME";

				}
				break;

			case Column::TYPE_TIMESTAMP:
				if (empty($columnSql))
				{
					$columnSql .= "TIMESTAMP";

				}
				break;

			case Column::TYPE_CHAR:
				if (empty($columnSql))
				{
					$columnSql .= "CHAR";

				}
				$columnSql .= "(" . $column->getSize() . ")";
				break;

			case Column::TYPE_TEXT:
				if (empty($columnSql))
				{
					$columnSql .= "TEXT";

				}
				break;

			case Column::TYPE_BOOLEAN:
				if (empty($columnSql))
				{
					$columnSql .= "TINYINT(1)";

				}
				break;

			case Column::TYPE_FLOAT:
				if (empty($columnSql))
				{
					$columnSql .= "FLOAT";

				}
				$size = $column->getSize();
				if ($size)
				{
					$scale = $column->getScale();

					if ($scale)
					{
						$columnSql .= "(" . $size . "," . $scale . ")";

					}

				}
				if ($column->isUnsigned())
				{
					$columnSql .= " UNSIGNED";

				}
				break;

			case Column::TYPE_DOUBLE:
				if (empty($columnSql))
				{
					$columnSql .= "DOUBLE";

				}
				$size = $column->getSize();
				if ($size)
				{
					$scale = $column->getScale();
					$columnSql .= "(" . $size;

					if ($scale)
					{
						$columnSql .= "," . $scale . ")";

					}

				}
				if ($column->isUnsigned())
				{
					$columnSql .= " UNSIGNED";

				}
				break;

			case Column::TYPE_BIGINTEGER:
				if (empty($columnSql))
				{
					$columnSql .= "BIGINT";

				}
				$scale = $column->getSize();
				if ($scale)
				{
					$columnSql .= "(" . $column->getSize() . ")";

				}
				if ($column->isUnsigned())
				{
					$columnSql .= " UNSIGNED";

				}
				break;

			case Column::TYPE_TINYBLOB:
				if (empty($columnSql))
				{
					$columnSql .= "TINYBLOB";

				}
				break;

			case Column::TYPE_BLOB:
				if (empty($columnSql))
				{
					$columnSql .= "BLOB";

				}
				break;

			case Column::TYPE_MEDIUMBLOB:
				if (empty($columnSql))
				{
					$columnSql .= "MEDIUMBLOB";

				}
				break;

			case Column::TYPE_LONGBLOB:
				if (empty($columnSql))
				{
					$columnSql .= "LONGBLOB";

				}
				break;

			default:
				if (empty($columnSql))
				{
					throw new Exception("Unrecognized MySQL data type at column " . $column->getName());
				}
				$typeValues = $column->getTypeValues();
				if (!(empty($typeValues)))
				{
					if (typeof($typeValues) == "array")
					{

						$valueSql = "";

						foreach ($typeValues as $value) {
							$valueSql .= "\"" . addcslashes($value, "\"") . "\", ";
						}

						$columnSql .= "(" . substr($valueSql, 0, -2) . ")";

					}

				}


		}

		return $columnSql;
	}

	public function addColumn($tableName, $schemaName, $column)
	{

		$sql = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " ADD `" . $column->getName() . "` " . $this->getColumnDefinition($column);

		if ($column->hasDefault())
		{
			$defaultValue = $column->getDefault();

			if (memstr(strtoupper($defaultValue), "CURRENT_TIMESTAMP"))
			{
				$sql .= " DEFAULT CURRENT_TIMESTAMP";

			}

		}

		if ($column->isNotNull())
		{
			$sql .= " NOT NULL";

		}

		if ($column->isAutoIncrement())
		{
			$sql .= " AUTO_INCREMENT";

		}

		if ($column->isFirst())
		{
			$sql .= " FIRST";

		}

		return $sql;
	}

	public function modifyColumn($tableName, $schemaName, $column, $currentColumn = null)
	{

		$columnDefinition = $this->getColumnDefinition($column);
		$sql = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName);

		if (typeof($currentColumn) <> "object")
		{
			$currentColumn = $column;

		}

		if ($column->getName() !== $currentColumn->getName())
		{
			$sql .= " CHANGE COLUMN `" . $currentColumn->getName() . "` `" . $column->getName() . "` " . $columnDefinition;

		}

		if ($column->hasDefault())
		{
			$defaultValue = $column->getDefault();

			if (memstr(strtoupper($defaultValue), "CURRENT_TIMESTAMP"))
			{
				$sql .= " DEFAULT CURRENT_TIMESTAMP";

			}

		}

		if ($column->isNotNull())
		{
			$sql .= " NOT NULL";

		}

		if ($column->isAutoIncrement())
		{
			$sql .= " AUTO_INCREMENT";

		}

		if ($column->isFirst())
		{
			$sql .= " FIRST";

		}

		return $sql;
	}

	public function dropColumn($tableName, $schemaName, $columnName)
	{
		return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " DROP COLUMN `" . $columnName . "`";
	}

	public function addIndex($tableName, $schemaName, $index)
	{

		$sql = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName);

		$indexType = $index->getType();

		if (!(empty($indexType)))
		{
			$sql .= " ADD " . $indexType . " INDEX ";

		}

		$sql .= "`" . $index->getName() . "` (" . $this->getColumnList($index->getColumns()) . ")";

		return $sql;
	}

	public function dropIndex($tableName, $schemaName, $indexName)
	{
		return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " DROP INDEX `" . $indexName . "`";
	}

	public function addPrimaryKey($tableName, $schemaName, $index)
	{
		return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " ADD PRIMARY KEY (" . $this->getColumnList($index->getColumns()) . ")";
	}

	public function dropPrimaryKey($tableName, $schemaName)
	{
		return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " DROP PRIMARY KEY";
	}

	public function addForeignKey($tableName, $schemaName, $reference)
	{

		$sql = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " ADD";

		if ($reference->getName())
		{
			$sql .= " CONSTRAINT `" . $reference->getName() . "`";

		}

		$sql .= " FOREIGN KEY (" . $this->getColumnList($reference->getColumns()) . ") REFERENCES " . $this->prepareTable($reference->getReferencedTable(), $reference->getReferencedSchema()) . "(" . $this->getColumnList($reference->getReferencedColumns()) . ")";

		$onDelete = $reference->getOnDelete();

		if (!(empty($onDelete)))
		{
			$sql .= " ON DELETE " . $onDelete;

		}

		$onUpdate = $reference->getOnUpdate();

		if (!(empty($onUpdate)))
		{
			$sql .= " ON UPDATE " . $onUpdate;

		}

		return $sql;
	}

	public function dropForeignKey($tableName, $schemaName, $referenceName)
	{
		return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " DROP FOREIGN KEY `" . $referenceName . "`";
	}

	public function createTable($tableName, $schemaName, $definition)
	{

		if (!(function() { if(isset($definition["columns"])) {$columns = $definition["columns"]; return $columns; } else { return false; } }()))
		{
			throw new Exception("The index 'columns' is required in the definition array");
		}

		$table = $this->prepareTable($tableName, $schemaName);

		$temporary = false;

		if (function() { if(isset($definition["options"])) {$options = $definition["options"]; return $options; } else { return false; } }())
		{
			$temporary = $options["temporary"]
		}

		if ($temporary)
		{
			$sql = "CREATE TEMPORARY TABLE " . $table . " (\n\t";

		}

		$createLines = [];

		foreach ($columns as $column) {
			$columnLine = "`" . $column->getName() . "` " . $this->getColumnDefinition($column);
			if ($column->hasDefault())
			{
				$defaultValue = $column->getDefault();

				if (memstr(strtoupper($defaultValue), "CURRENT_TIMESTAMP"))
				{
					$columnLine .= " DEFAULT CURRENT_TIMESTAMP";

				}

			}
			if ($column->isNotNull())
			{
				$columnLine .= " NOT NULL";

			}
			if ($column->isAutoIncrement())
			{
				$columnLine .= " AUTO_INCREMENT";

			}
			if ($column->isPrimary())
			{
				$columnLine .= " PRIMARY KEY";

			}
			$createLines = $columnLine;
		}

		if (function() { if(isset($definition["indexes"])) {$indexes = $definition["indexes"]; return $indexes; } else { return false; } }())
		{
			foreach ($indexes as $index) {
				$indexName = $index->getName();
				$indexType = $index->getType();
				if ($indexName == "PRIMARY")
				{
					$indexSql = "PRIMARY KEY (" . $this->getColumnList($index->getColumns()) . ")";

				}
				$createLines = $indexSql;
			}

		}

		if (function() { if(isset($definition["references"])) {$references = $definition["references"]; return $references; } else { return false; } }())
		{
			foreach ($references as $reference) {
				$referenceSql = "CONSTRAINT `" . $reference->getName() . "` FOREIGN KEY (" . $this->getColumnList($reference->getColumns()) . ")" . " REFERENCES `" . $reference->getReferencedTable() . "`(" . $this->getColumnList($reference->getReferencedColumns()) . ")";
				$onDelete = $reference->getOnDelete();
				if (!(empty($onDelete)))
				{
					$referenceSql .= " ON DELETE " . $onDelete;

				}
				$onUpdate = $reference->getOnUpdate();
				if (!(empty($onUpdate)))
				{
					$referenceSql .= " ON UPDATE " . $onUpdate;

				}
				$createLines = $referenceSql;
			}

		}

		$sql .= join(",\n\t", $createLines) . "\n)";

		if (isset($definition["options"]))
		{
			$sql .= " " . $this->_getTableOptions($definition);

		}

		return $sql;
	}

	public function truncateTable($tableName, $schemaName)
	{

		if ($schemaName)
		{
			$table = "`" . $schemaName . "`.`" . $tableName . "`";

		}

		$sql = "TRUNCATE TABLE " . $table;

		return $sql;
	}

	public function dropTable($tableName, $schemaName = null, $ifExists = true)
	{

		$table = $this->prepareTable($tableName, $schemaName);

		if ($ifExists)
		{
			$sql = "DROP TABLE IF EXISTS " . $table;

		}

		return $sql;
	}

	public function createView($viewName, $definition, $schemaName = null)
	{

		if (!(function() { if(isset($definition["sql"])) {$viewSql = $definition["sql"]; return $viewSql; } else { return false; } }()))
		{
			throw new Exception("The index 'sql' is required in the definition array");
		}

		return "CREATE VIEW " . $this->prepareTable($viewName, $schemaName) . " AS " . $viewSql;
	}

	public function dropView($viewName, $schemaName = null, $ifExists = true)
	{

		$view = $this->prepareTable($viewName, $schemaName);

		if ($ifExists)
		{
			$sql = "DROP VIEW IF EXISTS " . $view;

		}

		return $sql;
	}

	public function tableExists($tableName, $schemaName = null)
	{
		if ($schemaName)
		{
			return "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME`= '" . $tableName . "' AND `TABLE_SCHEMA` = '" . $schemaName . "'";
		}

		return "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_NAME` = '" . $tableName . "' AND `TABLE_SCHEMA` = DATABASE()";
	}

	public function viewExists($viewName, $schemaName = null)
	{
		if ($schemaName)
		{
			return "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`VIEWS` WHERE `TABLE_NAME`= '" . $viewName . "' AND `TABLE_SCHEMA`='" . $schemaName . "'";
		}

		return "SELECT IF(COUNT(*) > 0, 1, 0) FROM `INFORMATION_SCHEMA`.`VIEWS` WHERE `TABLE_NAME`='" . $viewName . "' AND `TABLE_SCHEMA` = DATABASE()";
	}

	public function describeColumns($table, $schema = null)
	{
		return "DESCRIBE " . $this->prepareTable($table, $schema);
	}

	public function listTables($schemaName = null)
	{
		if ($schemaName)
		{
			return "SHOW TABLES FROM `" . $schemaName . "`";
		}

		return "SHOW TABLES";
	}

	public function listViews($schemaName = null)
	{
		if ($schemaName)
		{
			return "SELECT `TABLE_NAME` AS view_name FROM `INFORMATION_SCHEMA`.`VIEWS` WHERE `TABLE_SCHEMA` = '" . $schemaName . "' ORDER BY view_name";
		}

		return "SELECT `TABLE_NAME` AS view_name FROM `INFORMATION_SCHEMA`.`VIEWS` WHERE `TABLE_SCHEMA` = DATABASE() ORDER BY view_name";
	}

	public function describeIndexes($table, $schema = null)
	{
		return "SHOW INDEXES FROM " . $this->prepareTable($table, $schema);
	}

	public function describeReferences($table, $schema = null)
	{

		if ($schema)
		{
			$sql .= "KCU.CONSTRAINT_SCHEMA = '" . $schema . "' AND KCU.TABLE_NAME = '" . $table . "'";

		}

		return $sql;
	}

	public function tableOptions($table, $schema = null)
	{

		if ($schema)
		{
			return $sql . "TABLES.TABLE_SCHEMA = '" . $schema . "' AND TABLES.TABLE_NAME = '" . $table . "'";
		}

		return $sql . "TABLES.TABLE_SCHEMA = DATABASE() AND TABLES.TABLE_NAME = '" . $table . "'";
	}

	protected function _getTableOptions($definition)
	{

		if (function() { if(isset($definition["options"])) {$options = $definition["options"]; return $options; } else { return false; } }())
		{
			$tableOptions = [];

			if (function() { if(isset($options["ENGINE"])) {$engine = $options["ENGINE"]; return $engine; } else { return false; } }())
			{
				if ($engine)
				{
					$tableOptions = "ENGINE=" . $engine;

				}

			}

			if (function() { if(isset($options["AUTO_INCREMENT"])) {$autoIncrement = $options["AUTO_INCREMENT"]; return $autoIncrement; } else { return false; } }())
			{
				if ($autoIncrement)
				{
					$tableOptions = "AUTO_INCREMENT=" . $autoIncrement;

				}

			}

			if (function() { if(isset($options["TABLE_COLLATION"])) {$tableCollation = $options["TABLE_COLLATION"]; return $tableCollation; } else { return false; } }())
			{
				if ($tableCollation)
				{
					$collationParts = explode("_", $tableCollation);
					$tableOptions = "DEFAULT CHARSET=" . $collationParts[0];
					$tableOptions = "COLLATE=" . $tableCollation;

				}

			}

			if (count($tableOptions))
			{
				return join(" ", $tableOptions);
			}

		}

		return "";
	}

	public function getForeignKeyChecks()
	{

		$sql = "SELECT @@foreign_key_checks";

		return $sql;
	}

	public function sharedLock($sqlQuery)
	{
		return $sqlQuery . " LOCK IN SHARE MODE";
	}


}