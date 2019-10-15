<?php
namespace Phalcon\Db\Adapter\Pdo;

use Phalcon\Db;
use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\Adapter\Pdo as PdoAdapter;
use Phalcon\Application\Exception;
use Phalcon\Db\ReferenceInterface;

class Mysql extends PdoAdapter
{
	protected $_type = "mysql";
	protected $_dialectType = "mysql";

	public function describeColumns($table, $schema = null)
	{

		$oldColumn = null;
		$sizePattern = "#\\(([0-9]+)(?:,\\s*([0-9]+))*\\)#";

		$columns = [];

		foreach ($this->fetchAll($this->_dialect->describeColumns($table, $schema), Db::FETCH_NUM) as $field) {
			$definition = ["bindType" => Column::BIND_PARAM_STR];
			$columnType = $field[1];
			if (memstr($columnType, "enum"))
			{
				$definition["type"] = Column::TYPE_CHAR;

			}
			if (memstr($columnType, "("))
			{
				$matches = null;

				if (preg_match($sizePattern, $columnType, $matches))
				{
					if (function() { if(isset($matches[1])) {$matchOne = $matches[1]; return $matchOne; } else { return false; } }())
					{
						$definition["size"] = (int) $matchOne;

					}

					if (function() { if(isset($matches[2])) {$matchTwo = $matches[2]; return $matchTwo; } else { return false; } }())
					{
						$definition["scale"] = (int) $matchTwo;

					}

				}

			}
			if (memstr($columnType, "unsigned"))
			{
				$definition["unsigned"] = true;

			}
			if ($oldColumn == null)
			{
				$definition["first"] = true;

			}
			if ($field[3] == "PRI")
			{
				$definition["primary"] = true;

			}
			if ($field[2] == "NO")
			{
				$definition["notNull"] = true;

			}
			if ($field[5] == "auto_increment")
			{
				$definition["autoIncrement"] = true;

			}
			if (typeof($field[4]) <> "null")
			{
				$definition["default"] = $field[4];

			}
			$columnName = $field[0];
			$columns = new Column($columnName, $definition);
			$oldColumn = $columnName;
		}

		return $columns;
	}

	public function describeIndexes($table, $schema = null)
	{

		$indexes = [];

		foreach ($this->fetchAll($this->_dialect->describeIndexes($table, $schema), Db::FETCH_ASSOC) as $index) {
			$keyName = $index["Key_name"];
			$indexType = $index["Index_type"];
			if (!(isset($indexes[$keyName])))
			{
				$indexes[$keyName] = [];

			}
			if (!(isset($indexes[$keyName]["columns"])))
			{
				$columns = [];

			}
			$columns = $index["Column_name"];
			$indexes[$keyName] = $columns;
			if ($keyName == "PRIMARY")
			{
				$indexes[$keyName] = "PRIMARY";

			}
		}

		$indexObjects = [];

		foreach ($indexes as $name => $index) {
			$indexObjects[$name] = new Index($name, $index["columns"], $index["type"]);
		}

		return $indexObjects;
	}

	public function describeReferences($table, $schema = null)
	{

		$references = [];

		foreach ($this->fetchAll($this->_dialect->describeReferences($table, $schema), Db::FETCH_NUM) as $reference) {
			$constraintName = $reference[2];
			if (!(isset($references[$constraintName])))
			{
				$referencedSchema = $reference[3];

				$referencedTable = $reference[4];

				$referenceUpdate = $reference[6];

				$referenceDelete = $reference[7];

				$columns = [];

				$referencedColumns = [];

			}
			$columns = $reference[1];
			$referencedColumns = $reference[5];
			$references[$constraintName] = ["referencedSchema" => $referencedSchema, "referencedTable" => $referencedTable, "columns" => $columns, "referencedColumns" => $referencedColumns, "onUpdate" => $referenceUpdate, "onDelete" => $referenceDelete];
		}

		$referenceObjects = [];

		foreach ($references as $name => $arrayReference) {
			$referenceObjects[$name] = new Reference($name, ["referencedSchema" => $arrayReference["referencedSchema"], "referencedTable" => $arrayReference["referencedTable"], "columns" => $arrayReference["columns"], "referencedColumns" => $arrayReference["referencedColumns"], "onUpdate" => $arrayReference["onUpdate"], "onDelete" => $arrayReference["onDelete"]]);
		}

		return $referenceObjects;
	}

	public function addForeignKey($tableName, $schemaName, $reference)
	{

		$foreignKeyCheck = $this->prepare($this->_dialect->getForeignKeyChecks());

		if (!($foreignKeyCheck->execute()))
		{
			throw new Exception("DATABASE PARAMETER 'FOREIGN_KEY_CHECKS' HAS TO BE 1");
		}

		return $this->execute($this->_dialect->addForeignKey($tableName, $schemaName, $reference));
	}


}