<?php
namespace Phalcon\Db\Dialect;

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\Dialect;
use Phalcon\Db\DialectInterface;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\ReferenceInterface;

class Sqlite extends Dialect
{
	protected $_escapeChar = "\"";

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
					$columnSql .= "INTEGER";

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
					$columnSql .= "NUMERIC";

				}
				$columnSql .= "(" . $column->getSize() . "," . $column->getScale() . ")";
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
					$columnSql .= "CHARACTER";

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
					$columnSql .= "TINYINT";

				}
				break;

			case Column::TYPE_FLOAT:
				if (empty($columnSql))
				{
					$columnSql .= "FLOAT";

				}
				break;

			case Column::TYPE_DOUBLE:
				if (empty($columnSql))
				{
					$columnSql .= "DOUBLE";

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
					throw new Exception("Unrecognized SQLite data type at column " . $column->getName());
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

		$sql = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " ADD COLUMN ";

		$sql .= "\"" . $column->getName() . "\" " . $this->getColumnDefinition($column);

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

		if ($column->isAutoincrement())
		{
			$sql .= " PRIMARY KEY AUTOINCREMENT";

		}

		return $sql;
	}

	public function modifyColumn($tableName, $schemaName, $column, $currentColumn = null)
	{
		throw new Exception("Altering a DB column is not supported by SQLite");
	}

	public function dropColumn($tableName, $schemaName, $columnName)
	{
		throw new Exception("Dropping DB column is not supported by SQLite");
	}

	public function addIndex($tableName, $schemaName, $index)
	{

		$indexType = $index->getType();

		if (!(empty($indexType)))
		{
			$sql = "CREATE " . $indexType . " INDEX \"";

		}

		if ($schemaName)
		{
			$sql .= $schemaName . "\".\"" . $index->getName() . "\" ON \"" . $tableName . "\" (";

		}

		$sql .= $this->getColumnList($index->getColumns()) . ")";

		return $sql;
	}

	public function dropIndex($tableName, $schemaName, $indexName)
	{
		if ($schemaName)
		{
			return "DROP INDEX \"" . $schemaName . "\".\"" . $indexName . "\"";
		}

		return "DROP INDEX \"" . $indexName . "\"";
	}

	public function addPrimaryKey($tableName, $schemaName, $index)
	{
		throw new Exception("Adding a primary key after table has been created is not supported by SQLite");
	}

	public function dropPrimaryKey($tableName, $schemaName)
	{
		throw new Exception("Removing a primary key after table has been created is not supported by SQLite");
	}

	public function addForeignKey($tableName, $schemaName, $reference)
	{
		throw new Exception("Adding a foreign key constraint to an existing table is not supported by SQLite");
	}

	public function dropForeignKey($tableName, $schemaName, $referenceName)
	{
		throw new Exception("Dropping a foreign key constraint is not supported by SQLite");
	}

	public function createTable($tableName, $schemaName, $definition)
	{

		$table = $this->prepareTable($tableName, $schemaName);

		$temporary = false;

		if (function() { if(isset($definition["options"])) {$options = $definition["options"]; return $options; } else { return false; } }())
		{
			$temporary = $options["temporary"]
		}

		if (!(function() { if(isset($definition["columns"])) {$columns = $definition["columns"]; return $columns; } else { return false; } }()))
		{
			throw new Exception("The index 'columns' is required in the definition array");
		}

		if ($temporary)
		{
			$sql = "CREATE TEMPORARY TABLE " . $table . " (\n\t";

		}

		$hasPrimary = false;

		$createLines = [];

		foreach ($columns as $column) {
			$columnLine = "`" . $column->getName() . "` " . $this->getColumnDefinition($column);
			if ($column->isPrimary() && !($hasPrimary))
			{
				$columnLine .= " PRIMARY KEY";

				$hasPrimary = true;

			}
			if ($column->isAutoIncrement() && $hasPrimary)
			{
				$columnLine .= " AUTOINCREMENT";

			}
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
			$createLines = $columnLine;
		}

		if (function() { if(isset($definition["indexes"])) {$indexes = $definition["indexes"]; return $indexes; } else { return false; } }())
		{
			foreach ($indexes as $index) {
				$indexName = $index->getName();
				$indexType = $index->getType();
				if ($indexName == "PRIMARY" && !($hasPrimary))
				{
					$createLines = "PRIMARY KEY (" . $this->getColumnList($index->getColumns()) . ")";

				}
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

		return $sql;
	}

	public function truncateTable($tableName, $schemaName)
	{

		if ($schemaName)
		{
			$table = $schemaName . "\".\"" . $tableName;

		}

		$sql = "DELETE FROM \"" . $table . "\"";

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
			return "DROP VIEW IF EXISTS " . $view;
		}

		return "DROP VIEW " . $view;
	}

	public function tableExists($tableName, $schemaName = null)
	{
		return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM sqlite_master WHERE type='table' AND tbl_name='" . $tableName . "'";
	}

	public function viewExists($viewName, $schemaName = null)
	{
		return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM sqlite_master WHERE type='view' AND tbl_name='" . $viewName . "'";
	}

	public function describeColumns($table, $schema = null)
	{
		return "PRAGMA table_info('" . $table . "')";
	}

	public function listTables($schemaName = null)
	{
		return "SELECT tbl_name FROM sqlite_master WHERE type = 'table' ORDER BY tbl_name";
	}

	public function listViews($schemaName = null)
	{
		return "SELECT tbl_name FROM sqlite_master WHERE type = 'view' ORDER BY tbl_name";
	}

	public function listIndexesSql($table, $schema = null, $keyName = null)
	{

		$sql = "SELECT sql FROM sqlite_master WHERE type = 'index' AND tbl_name = " . $this->escape($table) . " COLLATE NOCASE";

		if ($keyName)
		{
			$sql .= " AND name = " . $this->escape($keyName) . " COLLATE NOCASE";

		}

		return $sql;
	}

	public function describeIndexes($table, $schema = null)
	{
		return "PRAGMA index_list('" . $table . "')";
	}

	public function describeIndex($index)
	{
		return "PRAGMA index_info('" . $index . "')";
	}

	public function describeReferences($table, $schema = null)
	{
		return "PRAGMA foreign_key_list('" . $table . "')";
	}

	public function tableOptions($table, $schema = null)
	{
		return "";
	}

	public function sharedLock($sqlQuery)
	{
		return $sqlQuery;
	}


}