<?php
namespace Phalcon\Db;

use Phalcon\Db\ColumnInterface;
use Phalcon\Db\ReferenceInterface;
use Phalcon\Db\IndexInterface;

interface DialectInterface
{
	public function limit($sqlQuery, $number)
	{
	}

	public function forUpdate($sqlQuery)
	{
	}

	public function sharedLock($sqlQuery)
	{
	}

	public function select($definition)
	{
	}

	public function getColumnList($columnList)
	{
	}

	public function getColumnDefinition($column)
	{
	}

	public function addColumn($tableName, $schemaName, $column)
	{
	}

	public function modifyColumn($tableName, $schemaName, $column, $currentColumn = null)
	{
	}

	public function dropColumn($tableName, $schemaName, $columnName)
	{
	}

	public function addIndex($tableName, $schemaName, $index)
	{
	}

	public function dropIndex($tableName, $schemaName, $indexName)
	{
	}

	public function addPrimaryKey($tableName, $schemaName, $index)
	{
	}

	public function dropPrimaryKey($tableName, $schemaName)
	{
	}

	public function addForeignKey($tableName, $schemaName, $reference)
	{
	}

	public function dropForeignKey($tableName, $schemaName, $referenceName)
	{
	}

	public function createTable($tableName, $schemaName, $definition)
	{
	}

	public function createView($viewName, $definition, $schemaName = null)
	{
	}

	public function dropTable($tableName, $schemaName)
	{
	}

	public function dropView($viewName, $schemaName = null, $ifExists = true)
	{
	}

	public function tableExists($tableName, $schemaName = null)
	{
	}

	public function viewExists($viewName, $schemaName = null)
	{
	}

	public function describeColumns($table, $schema = null)
	{
	}

	public function listTables($schemaName = null)
	{
	}

	public function describeIndexes($table, $schema = null)
	{
	}

	public function describeReferences($table, $schema = null)
	{
	}

	public function tableOptions($table, $schema = null)
	{
	}

	public function supportsSavepoints()
	{
	}

	public function supportsReleaseSavepoints()
	{
	}

	public function createSavepoint($name)
	{
	}

	public function releaseSavepoint($name)
	{
	}

	public function rollbackSavepoint($name)
	{
	}


}