<?php
namespace Phalcon\Db;


class Reference implements ReferenceInterface
{
	protected $_name;
	protected $_schemaName;
	protected $_referencedSchema;
	protected $_referencedTable;
	protected $_columns;
	protected $_referencedColumns;
	protected $_onDelete;
	protected $_onUpdate;

	public function __construct($name, $definition)
	{

		$this->_name = $name;

		if (function() { if(isset($definition["referencedTable"])) {$referencedTable = $definition["referencedTable"]; return $referencedTable; } else { return false; } }())
		{
			$this->_referencedTable = $referencedTable;

		}

		if (function() { if(isset($definition["columns"])) {$columns = $definition["columns"]; return $columns; } else { return false; } }())
		{
			$this->_columns = $columns;

		}

		if (function() { if(isset($definition["referencedColumns"])) {$referencedColumns = $definition["referencedColumns"]; return $referencedColumns; } else { return false; } }())
		{
			$this->_referencedColumns = $referencedColumns;

		}

		if (function() { if(isset($definition["schema"])) {$schema = $definition["schema"]; return $schema; } else { return false; } }())
		{
			$this->_schemaName = $schema;

		}

		if (function() { if(isset($definition["referencedSchema"])) {$referencedSchema = $definition["referencedSchema"]; return $referencedSchema; } else { return false; } }())
		{
			$this->_referencedSchema = $referencedSchema;

		}

		if (function() { if(isset($definition["onDelete"])) {$onDelete = $definition["onDelete"]; return $onDelete; } else { return false; } }())
		{
			$this->_onDelete = $onDelete;

		}

		if (function() { if(isset($definition["onUpdate"])) {$onUpdate = $definition["onUpdate"]; return $onUpdate; } else { return false; } }())
		{
			$this->_onUpdate = $onUpdate;

		}

		if (count($columns) <> count($referencedColumns))
		{
			throw new Exception("Number of columns is not equals than the number of columns referenced");
		}

	}

	public static function __set_state($data)
	{

		if (!(function() { if(isset($data["_referenceName"])) {$constraintName = $data["_referenceName"]; return $constraintName; } else { return false; } }()))
		{
			if (!(function() { if(isset($data["_name"])) {$constraintName = $data["_name"]; return $constraintName; } else { return false; } }()))
			{
				throw new Exception("_name parameter is required");
			}

		}

		$referencedSchema = $data["_referencedSchema"]
		$referencedTable = $data["_referencedTable"]
		$columns = $data["_columns"]
		$referencedColumns = $data["_referencedColumns"]
		$onDelete = $data["_onDelete"]
		$onUpdate = $data["_onUpdate"]
		return new Reference($constraintName, ["referencedSchema" => $referencedSchema, "referencedTable" => $referencedTable, "columns" => $columns, "referencedColumns" => $referencedColumns, "onDelete" => $onDelete, "onUpdate" => $onUpdate]);
	}


}