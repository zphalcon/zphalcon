<?php
namespace Phalcon\Db\Dialect;

use Phalcon\Db\Dialect;
use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\ColumnInterface;
use Phalcon\Db\ReferenceInterface;
use Phalcon\Db\DialectInterface;

class Postgresql extends Dialect
{
	protected $_escapeChar = "\"";

	public function getColumnDefinition($column)
	{

		$size = $column->getSize();

		$columnType = $column->getType();

		$columnSql = "";

		if (typeof($columnType) == "string")
		{
			$columnSql .= $columnType;

			$columnType = $column->getTypeReference();

		}

		switch ($columnType) {
			case Column::TYPE_INTEGER:
				if (empty($columnSql))
				{
					if ($column->isAutoIncrement())
					{
						$columnSql .= "SERIAL";

					}

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
					$columnSql .= "CHARACTER VARYING";

				}
				$columnSql .= "(" . $size . ")";
				break;

			case Column::TYPE_DECIMAL:
				if (empty($columnSql))
				{
					$columnSql .= "NUMERIC";

				}
				$columnSql .= "(" . $size . "," . $column->getScale() . ")";
				break;

			case Column::TYPE_DATETIME:
				if (empty($columnSql))
				{
					$columnSql .= "TIMESTAMP";

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
				$columnSql .= "(" . $size . ")";
				break;

			case Column::TYPE_TEXT:
				if (empty($columnSql))
				{
					$columnSql .= "TEXT";

				}
				break;

			case Column::TYPE_FLOAT:
				if (empty($columnSql))
				{
					$columnSql .= "FLOAT";

				}
				break;

			case Column::TYPE_BIGINTEGER:
				if (empty($columnSql))
				{
					if ($column->isAutoIncrement())
					{
						$columnSql .= "BIGSERIAL";

					}

				}
				break;

			case Column::TYPE_JSON:
				if (empty($columnSql))
				{
					$columnSql .= "JSON";

				}
				break;

			case Column::TYPE_JSONB:
				if (empty($columnSql))
				{
					$columnSql .= "JSONB";

				}
				break;

			case Column::TYPE_BOOLEAN:
				if (empty($columnSql))
				{
					$columnSql .= "BOOLEAN";

				}
				break;

			default:
				if (empty($columnSql))
				{
					throw new Exception("Unrecognized PostgreSQL data type at column " . $column->getName());
				}
				$typeValues = $column->getTypeValues();
				if (!(empty($typeValues)))
				{
					if (typeof($typeValues) == "array")
					{

						$valueSql = "";

						foreach ($typeValues as $value) {
							$valueSql .= "'" . addcslashes($value, "\'") . "', ";
						}

						$columnSql .= "(" . substr($valueSql, 0, -2) . ")";

					}

				}


		}

		return $columnSql;
	}

	public function addColumn($tableName, $schemaName, $column)
	{

		$columnDefinition = $this->getColumnDefinition($column);

		$sql = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " ADD COLUMN ";

		$sql .= "\"" . $column->getName() . "\" " . $columnDefinition;

		if ($column->hasDefault())
		{
			$sql .= " DEFAULT " . $this->_castDefault($column);

		}

		if ($column->isNotNull())
		{
			$sql .= " NOT NULL";

		}

		return $sql;
	}

	public function modifyColumn($tableName, $schemaName, $column, $currentColumn = null)
	{

		$columnDefinition = $this->getColumnDefinition($column);
		$sqlAlterTable = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName);

		if (typeof($currentColumn) <> "object")
		{
			$currentColumn = $column;

		}

		if ($column->getName() !== $currentColumn->getName())
		{
			$sql .= $sqlAlterTable . " RENAME COLUMN \"" . $currentColumn->getName() . "\" TO \"" . $column->getName() . "\";";

		}

		if ($column->getType() !== $currentColumn->getType())
		{
			$sql .= $sqlAlterTable . " ALTER COLUMN \"" . $column->getName() . "\" TYPE " . $columnDefinition . ";";

		}

		if ($column->isNotNull() !== $currentColumn->isNotNull())
		{
			if ($column->isNotNull())
			{
				$sql .= $sqlAlterTable . " ALTER COLUMN \"" . $column->getName() . "\" SET NOT NULL;";

			}

		}

		if ($column->getDefault() !== $currentColumn->getDefault())
		{
			if (empty($column->getDefault()) && !(empty($currentColumn->getDefault())))
			{
				$sql .= $sqlAlterTable . " ALTER COLUMN \"" . $column->getName() . "\" DROP DEFAULT;";

			}

			if ($column->hasDefault())
			{
				$defaultValue = $this->_castDefault($column);

				if (memstr(strtoupper($columnDefinition), "BOOLEAN"))
				{
					$sql .= " ALTER COLUMN \"" . $column->getName() . "\" SET DEFAULT " . $defaultValue;

				}

			}

		}

		return $sql;
	}

	public function dropColumn($tableName, $schemaName, $columnName)
	{
		return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " DROP COLUMN \"" . $columnName . "\"";
	}

	public function addIndex($tableName, $schemaName, $index)
	{

		if ($index->getName() === "PRIMARY")
		{
			return $this->addPrimaryKey($tableName, $schemaName, $index);
		}

		$sql = "CREATE";

		$indexType = $index->getType();

		if (!(empty($indexType)))
		{
			$sql .= " " . $indexType;

		}

		$sql .= " INDEX \"" . $index->getName() . "\" ON " . $this->prepareTable($tableName, $schemaName);

		$sql .= " (" . $this->getColumnList($index->getColumns()) . ")";

		return $sql;
	}

	public function dropIndex($tableName, $schemaName, $indexName)
	{
		return "DROP INDEX \"" . $indexName . "\"";
	}

	public function addPrimaryKey($tableName, $schemaName, $index)
	{
		return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " ADD CONSTRAINT \"PRIMARY\" PRIMARY KEY (" . $this->getColumnList($index->getColumns()) . ")";
	}

	public function dropPrimaryKey($tableName, $schemaName)
	{
		return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " DROP CONSTRAINT \"PRIMARY\"";
	}

	public function addForeignKey($tableName, $schemaName, $reference)
	{

		$sql = "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " ADD";

		if ($reference->getName())
		{
			$sql .= " CONSTRAINT \"" . $reference->getName() . "\"";

		}

		$sql .= " FOREIGN KEY (" . $this->getColumnList($reference->getColumns()) . ")" . " REFERENCES \"" . $reference->getReferencedTable() . "\" (" . $this->getColumnList($reference->getReferencedColumns()) . ")";

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
		return "ALTER TABLE " . $this->prepareTable($tableName, $schemaName) . " DROP CONSTRAINT \"" . $referenceName . "\"";
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

		$primaryColumns = [];

		foreach ($columns as $column) {
			$columnDefinition = $this->getColumnDefinition($column);
			$columnLine = "\"" . $column->getName() . "\" " . $columnDefinition;
			if ($column->hasDefault())
			{
				$columnLine .= " DEFAULT " . $this->_castDefault($column);

			}
			if ($column->isNotNull())
			{
				$columnLine .= " NOT NULL";

			}
			if ($column->isPrimary())
			{
				$primaryColumns = $column->getName();

			}
			$createLines = $columnLine;
		}

		if (!(empty($primaryColumns)))
		{
			$createLines = "PRIMARY KEY (" . $this->getColumnList($primaryColumns) . ")";

		}

		$indexSqlAfterCreate = "";

		if (function() { if(isset($definition["indexes"])) {$indexes = $definition["indexes"]; return $indexes; } else { return false; } }())
		{
			foreach ($indexes as $index) {
				$indexName = $index->getName();
				$indexType = $index->getType();
				$indexSql = "";
				if ($indexName == "PRIMARY")
				{
					$indexSql = "CONSTRAINT \"PRIMARY\" PRIMARY KEY (" . $this->getColumnList($index->getColumns()) . ")";

				}
				if (!(empty($indexSql)))
				{
					$createLines = $indexSql;

				}
			}

		}

		if (function() { if(isset($definition["references"])) {$references = $definition["references"]; return $references; } else { return false; } }())
		{
			foreach ($references as $reference) {
				$referenceSql = "CONSTRAINT \"" . $reference->getName() . "\" FOREIGN KEY (" . $this->getColumnList($reference->getColumns()) . ") REFERENCES ";
				$referenceSql .= $this->prepareTable($reference->getReferencedTable(), $schemaName);
				$referenceSql .= " (" . $this->getColumnList($reference->getReferencedColumns()) . ")";
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

		$sql .= ";" . $indexSqlAfterCreate;

		return $sql;
	}

	public function truncateTable($tableName, $schemaName)
	{

		if ($schemaName)
		{
			$table = $schemaName . "." . $tableName;

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
			return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM information_schema.tables WHERE table_schema = '" . $schemaName . "' AND table_name='" . $tableName . "'";
		}

		return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM information_schema.tables WHERE table_schema = 'public' AND table_name='" . $tableName . "'";
	}

	public function viewExists($viewName, $schemaName = null)
	{
		if ($schemaName)
		{
			return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM pg_views WHERE viewname='" . $viewName . "' AND schemaname='" . $schemaName . "'";
		}

		return "SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM pg_views WHERE viewname='" . $viewName . "' AND schemaname='public'";
	}

	public function describeColumns($table, $schema = null)
	{
		if ($schema)
		{
			return "SELECT DISTINCT c.column_name AS Field, c.data_type AS Type, c.character_maximum_length AS Size, c.numeric_precision AS NumericSize, c.numeric_scale AS NumericScale, c.is_nullable AS Null, CASE WHEN pkc.column_name NOTNULL THEN 'PRI' ELSE '' END AS Key, CASE WHEN c.data_type LIKE '%int%' AND c.column_default LIKE '%nextval%' THEN 'auto_increment' ELSE '' END AS Extra, c.ordinal_position AS Position, c.column_default FROM information_schema.columns c LEFT JOIN ( SELECT kcu.column_name, kcu.table_name, kcu.table_schema FROM information_schema.table_constraints tc INNER JOIN information_schema.key_column_usage kcu on (kcu.constraint_name = tc.constraint_name and kcu.table_name=tc.table_name and kcu.table_schema=tc.table_schema) WHERE tc.constraint_type='PRIMARY KEY') pkc ON (c.column_name=pkc.column_name AND c.table_schema = pkc.table_schema AND c.table_name=pkc.table_name) WHERE c.table_schema='" . $schema . "' AND c.table_name='" . $table . "' ORDER BY c.ordinal_position";
		}

		return "SELECT DISTINCT c.column_name AS Field, c.data_type AS Type, c.character_maximum_length AS Size, c.numeric_precision AS NumericSize, c.numeric_scale AS NumericScale, c.is_nullable AS Null, CASE WHEN pkc.column_name NOTNULL THEN 'PRI' ELSE '' END AS Key, CASE WHEN c.data_type LIKE '%int%' AND c.column_default LIKE '%nextval%' THEN 'auto_increment' ELSE '' END AS Extra, c.ordinal_position AS Position, c.column_default FROM information_schema.columns c LEFT JOIN ( SELECT kcu.column_name, kcu.table_name, kcu.table_schema FROM information_schema.table_constraints tc INNER JOIN information_schema.key_column_usage kcu on (kcu.constraint_name = tc.constraint_name and kcu.table_name=tc.table_name and kcu.table_schema=tc.table_schema) WHERE tc.constraint_type='PRIMARY KEY') pkc ON (c.column_name=pkc.column_name AND c.table_schema = pkc.table_schema AND c.table_name=pkc.table_name) WHERE c.table_schema='public' AND c.table_name='" . $table . "' ORDER BY c.ordinal_position";
	}

	public function listTables($schemaName = null)
	{
		if ($schemaName)
		{
			return "SELECT table_name FROM information_schema.tables WHERE table_schema = '" . $schemaName . "' ORDER BY table_name";
		}

		return "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name";
	}

	public function listViews($schemaName = null)
	{
		if ($schemaName)
		{
			return "SELECT viewname AS view_name FROM pg_views WHERE schemaname = '" . $schemaName . "' ORDER BY view_name";
		}

		return "SELECT viewname AS view_name FROM pg_views WHERE schemaname = 'public' ORDER BY view_name";
	}

	public function describeIndexes($table, $schema = null)
	{
		return "SELECT 0 as c0, t.relname as table_name, i.relname as key_name, 3 as c3, a.attname as column_name FROM pg_class t, pg_class i, pg_index ix, pg_attribute a WHERE t.oid = ix.indrelid AND i.oid = ix.indexrelid AND a.attrelid = t.oid AND a.attnum = ANY(ix.indkey) AND t.relkind = 'r' AND t.relname = '" . $table . "' ORDER BY t.relname, i.relname;";
	}

	public function describeReferences($table, $schema = null)
	{

		if ($schema)
		{
			$sql .= "tc.table_schema = '" . $schema . "' AND tc.table_name='" . $table . "'";

		}

		return $sql;
	}

	public function tableOptions($table, $schema = null)
	{
		return "";
	}

	protected function _castDefault($column)
	{

		$defaultValue = $column->getDefault();
		$columnDefinition = $this->getColumnDefinition($column);
		$columnType = $column->getType();

		if (memstr(strtoupper($columnDefinition), "BOOLEAN"))
		{
			return $defaultValue;
		}

		if (memstr(strtoupper($defaultValue), "CURRENT_TIMESTAMP"))
		{
			return "CURRENT_TIMESTAMP";
		}

		if ($columnType === Column::TYPE_INTEGER || $columnType === Column::TYPE_BIGINTEGER || $columnType === Column::TYPE_DECIMAL || $columnType === Column::TYPE_FLOAT || $columnType === Column::TYPE_DOUBLE)
		{
			$preparedValue = (string) $defaultValue;

		}

		return $preparedValue;
	}

	protected function _getTableOptions($definition)
	{
		return "";
	}

	public function sharedLock($sqlQuery)
	{
		return $sqlQuery;
	}


}