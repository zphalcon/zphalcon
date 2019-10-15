<?php
namespace Phalcon\Db\Adapter\Pdo;

use Phalcon\Db;
use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\RawValue;
use Phalcon\Db\Reference;
use Phalcon\Db\ReferenceInterface;
use Phalcon\Db\Index;
use Phalcon\Db\IndexInterface;
use Phalcon\Db\Adapter\Pdo as PdoAdapter;

class Sqlite extends PdoAdapter
{
	protected $_type = "sqlite";
	protected $_dialectType = "sqlite";

	public function connect($descriptor = null)
	{

		if (empty($descriptor))
		{
			$descriptor = (array) $this->_descriptor;

		}

		if (!(function() { if(isset($descriptor["dbname"])) {$dbname = $descriptor["dbname"]; return $dbname; } else { return false; } }()))
		{
			throw new Exception("dbname must be specified");
		}

		$descriptor["dsn"] = $dbname;

		return parent::connect($descriptor);
	}

	public function describeColumns($table, $schema = null)
	{

		$oldColumn = null;
		$sizePattern = "#\\(([0-9]+)(?:,\\s*([0-9]+))*\\)#";

		$columns = [];

		foreach ($this->fetchAll($this->_dialect->describeColumns($table, $schema), Db::FETCH_NUM) as $field) {
			$definition = ["bindType" => Column::BIND_PARAM_STR];
			$columnType = $field[2];
			if (memstr($columnType, "tinyint(1)"))
			{
				$definition["type"] = Column::TYPE_BOOLEAN;
				$definition["bindType"] = Column::BIND_PARAM_BOOL;
				$columnType = "boolean";

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
			if ($field[5])
			{
				$definition["primary"] = true;

			}
			if ($field[3])
			{
				$definition["notNull"] = true;

			}
			if (strcasecmp($field[4], "null") <> 0 && $field[4] <> "")
			{
				$definition["default"] = preg_replace("/^'|'$/", "", $field[4]);

			}
			$columnName = $field[1];
			$columns = new Column($columnName, $definition);
			$oldColumn = $columnName;
		}

		return $columns;
	}

	public function describeIndexes($table, $schema = null)
	{

		$indexes = [];

		foreach ($this->fetchAll($this->_dialect->describeIndexes($table, $schema), Db::FETCH_ASSOC) as $index) {
			$keyName = $index["name"];
			if (!(isset($indexes[$keyName])))
			{
				$indexes[$keyName] = [];

			}
			if (!(isset($indexes[$keyName]["columns"])))
			{
				$columns = [];

			}
			foreach ($this->fetchAll($this->_dialect->describeIndex($keyName), Db::FETCH_ASSOC) as $describeIndex) {
				$columns = $describeIndex["name"];
			}
			$indexes[$keyName] = $columns;
			$indexSql = $this->fetchColumn($this->_dialect->listIndexesSql($table, $schema, $keyName));
			if ($index["unique"])
			{
				if (preg_match("# UNIQUE #i", $indexSql))
				{
					$indexes[$keyName] = "UNIQUE";

				}

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

		foreach ($this->fetchAll($this->_dialect->describeReferences($table, $schema), Db::FETCH_NUM) as $number => $reference) {
			$constraintName = "foreign_key_" . $number;
			if (!(isset($references[$constraintName])))
			{
				$referencedSchema = null;

				$referencedTable = $reference[2];

				$columns = [];

				$referencedColumns = [];

			}
			$columns = $reference[3];
			$referencedColumns = $reference[4];
			$references[$constraintName] = ["referencedSchema" => $referencedSchema, "referencedTable" => $referencedTable, "columns" => $columns, "referencedColumns" => $referencedColumns];
		}

		$referenceObjects = [];

		foreach ($references as $name => $arrayReference) {
			$referenceObjects[$name] = new Reference($name, ["referencedSchema" => $arrayReference["referencedSchema"], "referencedTable" => $arrayReference["referencedTable"], "columns" => $arrayReference["columns"], "referencedColumns" => $arrayReference["referencedColumns"]]);
		}

		return $referenceObjects;
	}

	public function useExplicitIdValue()
	{
		return true;
	}

	public function getDefaultValue()
	{
		return new RawValue("NULL");
	}


}