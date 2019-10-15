<?php
namespace Phalcon\Db\Adapter\Pdo;

use Phalcon\Db\Column;
use Phalcon\Db\RawValue;
use Phalcon\Db\Adapter\Pdo as PdoAdapter;
use Phalcon\Db\Exception;

class Postgresql extends PdoAdapter
{
	protected $_type = "pgsql";
	protected $_dialectType = "postgresql";

	public function connect($descriptor = null)
	{

		if (empty($descriptor))
		{
			$descriptor = (array) $this->_descriptor;

		}

		if (function() { if(isset($descriptor["schema"])) {$schema = $descriptor["schema"]; return $schema; } else { return false; } }())
		{
			unset($descriptor["schema"]);

		}

		if (isset($descriptor["password"]))
		{
			if (typeof($descriptor["password"]) == "string" && strlen($descriptor["password"]) == 0)
			{
				$descriptor["password"] = null;

			}

		}

		$status = parent::connect($descriptor);

		if (!(empty($schema)))
		{
			$sql = "SET search_path TO '" . $schema . "'";

			$this->execute($sql);

		}

		return $status;
	}

	public function describeColumns($table, $schema = null)
	{

		$oldColumn = null;
		$columns = [];

		foreach ($this->fetchAll($this->_dialect->describeColumns($table, $schema), \Phalcon\Db::FETCH_NUM) as $field) {
			$definition = ["bindType" => Column::BIND_PARAM_STR];
			$columnType = $field[1];
			$charSize = $field[2];
			$numericSize = $field[3];
			$numericScale = $field[4];
			if (memstr($columnType, "smallint(1)"))
			{
				$definition["type"] = Column::TYPE_BOOLEAN;
				$definition["bindType"] = Column::BIND_PARAM_BOOL;

			}
			if ($oldColumn == null)
			{
				$definition["first"] = true;

			}
			if ($field[6] == "PRI")
			{
				$definition["primary"] = true;

			}
			if ($field[5] == "NO")
			{
				$definition["notNull"] = true;

			}
			if ($field[7] == "auto_increment")
			{
				$definition["autoIncrement"] = true;

			}
			if (typeof($field[9]) <> "null")
			{
				$definition["default"] = preg_replace("/^'|'?::[[:alnum:][:space:]]+$/", "", $field[9]);

				if (strcasecmp($definition["default"], "null") == 0)
				{
					$definition["default"] = null;

				}

			}
			$columnName = $field[0];
			$columns = new Column($columnName, $definition);
			$oldColumn = $columnName;
		}

		return $columns;
	}

	public function createTable($tableName, $schemaName, $definition)
	{

		if (!(function() { if(isset($definition["columns"])) {$columns = $definition["columns"]; return $columns; } else { return false; } }()))
		{
			throw new Exception("The table must contain at least one column");
		}

		if (!(count($columns)))
		{
			throw new Exception("The table must contain at least one column");
		}

		$sql = $this->_dialect->createTable($tableName, $schemaName, $definition);

		$queries = explode(";", $sql);

		if (count($queries) > 1)
		{
			try {
				$this->begin();
				foreach ($queries as $query) {
					if (empty($query))
					{
						continue;

					}
					$this->query($query . ";");
				}
				return $this->commit();			} catch (\Exception $exception) {
				$this->rollback();
				throw $exception;
			}
		}

		return true;
	}

	public function modifyColumn($tableName, $schemaName, $column, $currentColumn = null)
	{

		$sql = $this->_dialect->modifyColumn($tableName, $schemaName, $column, $currentColumn);

		$queries = explode(";", $sql);

		if (count($queries) > 1)
		{
			try {
				$this->begin();
				foreach ($queries as $query) {
					if (empty($query))
					{
						continue;

					}
					$this->query($query . ";");
				}
				return $this->commit();			} catch (\Exception $exception) {
				$this->rollback();
				throw $exception;
			}
		}

		return true;
	}

	public function useExplicitIdValue()
	{
		return true;
	}

	public function getDefaultIdValue()
	{
		return new RawValue("DEFAULT");
	}

	public function supportSequences()
	{
		return true;
	}


}