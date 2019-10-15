<?php
namespace Phalcon\Db;

use Phalcon\Db;
use Phalcon\Db\ColumnInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface;
abstract 
class Adapter implements AdapterInterface, EventsAwareInterface
{
	protected $_eventsManager;
	protected $_descriptor = [];
	protected $_dialectType;
	protected $_type;
	protected $_dialect;
	protected $_connectionId;
	protected $_sqlStatement;
	protected $_sqlVariables;
	protected $_sqlBindTypes;
	protected $_transactionLevel = 0;
	protected $_transactionsWithSavepoints = false;
	protected static $_connectionConsecutive = 0;

	public function __construct($descriptor)
	{

		$connectionId = self::_connectionConsecutive;
		$this->_connectionId = $connectionId;
		self::_connectionConsecutive = $connectionId + 1;

		if (!(function() { if(isset($descriptor["dialectClass"])) {$dialectClass = $descriptor["dialectClass"]; return $dialectClass; } else { return false; } }()))
		{
			$dialectClass = "Phalcon\\Db\\Dialect\\" . ucfirst($this->_dialectType);

		}

		if (typeof($dialectClass) == "string")
		{
			$this->_dialect = new $dialectClass();

		}

		$this->_descriptor = $descriptor;

	}

	public function setEventsManager($eventsManager)
	{
		$this->_eventsManager = $eventsManager;

	}

	public function getEventsManager()
	{
		return $this->_eventsManager;
	}

	public function setDialect($dialect)
	{
		$this->_dialect = $dialect;

	}

	public function getDialect()
	{
		return $this->_dialect;
	}

	public function fetchOne($sqlQuery, $fetchMode = Db::FETCH_ASSOC, $bindParams = null, $bindTypes = null)
	{

		$result = $this->query($sqlQuery, $bindParams, $bindTypes);

		if (typeof($result) == "object")
		{
			if (typeof($fetchMode) !== "null")
			{
				$result->setFetchMode($fetchMode);

			}

			return $result->fetch();
		}

		return [];
	}

	public function fetchAll($sqlQuery, $fetchMode = Db::FETCH_ASSOC, $bindParams = null, $bindTypes = null)
	{

		$results = [];
		$result = $this->query($sqlQuery, $bindParams, $bindTypes);

		if (typeof($result) == "object")
		{
			if ($fetchMode !== null)
			{
				$result->setFetchMode($fetchMode);

			}

			$results = $result->fetchAll();

		}

		return $results;
	}

	public function fetchColumn($sqlQuery, $placeholders = null, $column = 0)
	{

		$row = $this->fetchOne($sqlQuery, Db::FETCH_BOTH, $placeholders);

		if (!(empty($row)) && function() { if(isset($row[$column])) {$columnValue = $row[$column]; return $columnValue; } else { return false; } }())
		{
			return $columnValue;
		}

		return false;
	}

	public function insert($table, $values, $fields = null, $dataTypes = null)
	{

		if (!(count($values)))
		{
			throw new Exception("Unable to insert into " . $table . " without data");
		}

		$placeholders = [];
		$insertValues = [];

		$bindDataTypes = [];

		foreach ($values as $position => $value) {
			if (typeof($value) == "object" && $value instanceof $RawValue)
			{
				$placeholders = (string) $value;

			}
		}

		$escapedTable = $this->escapeIdentifier($table);

		$joinedValues = join(", ", $placeholders);

		if (typeof($fields) == "array")
		{
			$escapedFields = [];

			foreach ($fields as $field) {
				$escapedFields = $this->escapeIdentifier($field);
			}

			$insertSql = "INSERT INTO " . $escapedTable . " (" . join(", ", $escapedFields) . ") VALUES (" . $joinedValues . ")";

		}

		if (!(count($bindDataTypes)))
		{
			return $this->execute($insertSql, $insertValues);
		}

		return $this->execute($insertSql, $insertValues, $bindDataTypes);
	}

	public function insertAsDict($table, $data, $dataTypes = null)
	{


		if (typeof($data) <> "array" || empty($data))
		{
			return false;
		}

		foreach ($data as $field => $value) {
			$fields = $field;
			$values = $value;
		}

		return $this->insert($table, $values, $fields, $dataTypes);
	}

	public function update($table, $fields, $values, $whereCondition = null, $dataTypes = null)
	{

		$placeholders = [];
		$updateValues = [];

		$bindDataTypes = [];

		foreach ($values as $position => $value) {
			if (!(function() { if(isset($fields[$position])) {$field = $fields[$position]; return $field; } else { return false; } }()))
			{
				throw new Exception("The number of values in the update is not the same as fields");
			}
			$escapedField = $this->escapeIdentifier($field);
			if (typeof($value) == "object" && $value instanceof $RawValue)
			{
				$placeholders = $escapedField . " = " . (string) $value;

			}
		}

		$escapedTable = $this->escapeIdentifier($table);

		$setClause = join(", ", $placeholders);

		if ($whereCondition !== null)
		{
			$updateSql = "UPDATE " . $escapedTable . " SET " . $setClause . " WHERE ";

			if (typeof($whereCondition) == "string")
			{
				$updateSql .= $whereCondition;

			}

		}

		if (!(count($bindDataTypes)))
		{
			return $this->execute($updateSql, $updateValues);
		}

		return $this->execute($updateSql, $updateValues, $bindDataTypes);
	}

	public function updateAsDict($table, $data, $whereCondition = null, $dataTypes = null)
	{


		if (typeof($data) <> "array" || empty($data))
		{
			return false;
		}

		foreach ($data as $field => $value) {
			$fields = $field;
			$values = $value;
		}

		return $this->update($table, $fields, $values, $whereCondition, $dataTypes);
	}

	public function delete($table, $whereCondition = null, $placeholders = null, $dataTypes = null)
	{

		$escapedTable = $this->escapeIdentifier($table);

		if (!(empty($whereCondition)))
		{
			$sql = "DELETE FROM " . $escapedTable . " WHERE " . $whereCondition;

		}

		return $this->execute($sql, $placeholders, $dataTypes);
	}

	public function escapeIdentifier($identifier)
	{
		if (typeof($identifier) == "array")
		{
			return $this->_dialect->escape($identifier[0]) . "." . $this->_dialect->escape($identifier[1]);
		}

		return $this->_dialect->escape($identifier);
	}

	public function getColumnList($columnList)
	{
		return $this->_dialect->getColumnList($columnList);
	}

	public function limit($sqlQuery, $number)
	{
		return $this->_dialect->limit($sqlQuery, $number);
	}

	public function tableExists($tableName, $schemaName = null)
	{
		return $this->fetchOne($this->_dialect->tableExists($tableName, $schemaName), Db::FETCH_NUM)[0] > 0;
	}

	public function viewExists($viewName, $schemaName = null)
	{
		return $this->fetchOne($this->_dialect->viewExists($viewName, $schemaName), Db::FETCH_NUM)[0] > 0;
	}

	public function forUpdate($sqlQuery)
	{
		return $this->_dialect->forUpdate($sqlQuery);
	}

	public function sharedLock($sqlQuery)
	{
		return $this->_dialect->sharedLock($sqlQuery);
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

		return $this->execute($this->_dialect->createTable($tableName, $schemaName, $definition));
	}

	public function dropTable($tableName, $schemaName = null, $ifExists = true)
	{
		return $this->execute($this->_dialect->dropTable($tableName, $schemaName, $ifExists));
	}

	public function createView($viewName, $definition, $schemaName = null)
	{
		if (!(isset($definition["sql"])))
		{
			throw new Exception("The table must contain at least one column");
		}

		return $this->execute($this->_dialect->createView($viewName, $definition, $schemaName));
	}

	public function dropView($viewName, $schemaName = null, $ifExists = true)
	{
		return $this->execute($this->_dialect->dropView($viewName, $schemaName, $ifExists));
	}

	public function addColumn($tableName, $schemaName, $column)
	{
		return $this->execute($this->_dialect->addColumn($tableName, $schemaName, $column));
	}

	public function modifyColumn($tableName, $schemaName, $column, $currentColumn = null)
	{
		return $this->execute($this->_dialect->modifyColumn($tableName, $schemaName, $column, $currentColumn));
	}

	public function dropColumn($tableName, $schemaName, $columnName)
	{
		return $this->execute($this->_dialect->dropColumn($tableName, $schemaName, $columnName));
	}

	public function addIndex($tableName, $schemaName, $index)
	{
		return $this->execute($this->_dialect->addIndex($tableName, $schemaName, $index));
	}

	public function dropIndex($tableName, $schemaName, $indexName)
	{
		return $this->execute($this->_dialect->dropIndex($tableName, $schemaName, $indexName));
	}

	public function addPrimaryKey($tableName, $schemaName, $index)
	{
		return $this->execute($this->_dialect->addPrimaryKey($tableName, $schemaName, $index));
	}

	public function dropPrimaryKey($tableName, $schemaName)
	{
		return $this->execute($this->_dialect->dropPrimaryKey($tableName, $schemaName));
	}

	public function addForeignKey($tableName, $schemaName, $reference)
	{
		return $this->execute($this->_dialect->addForeignKey($tableName, $schemaName, $reference));
	}

	public function dropForeignKey($tableName, $schemaName, $referenceName)
	{
		return $this->execute($this->_dialect->dropForeignKey($tableName, $schemaName, $referenceName));
	}

	public function getColumnDefinition($column)
	{
		return $this->_dialect->getColumnDefinition($column);
	}

	public function listTables($schemaName = null)
	{

		$allTables = [];

		foreach ($this->fetchAll($this->_dialect->listTables($schemaName), Db::FETCH_NUM) as $table) {
			$allTables = $table[0];
		}

		return $allTables;
	}

	public function listViews($schemaName = null)
	{

		$allTables = [];

		foreach ($this->fetchAll($this->_dialect->listViews($schemaName), Db::FETCH_NUM) as $table) {
			$allTables = $table[0];
		}

		return $allTables;
	}

	public function describeIndexes($table, $schema = null)
	{

		$indexes = [];

		foreach ($this->fetchAll($this->_dialect->describeIndexes($table, $schema), Db::FETCH_NUM) as $index) {
			$keyName = $index[2];
			if (!(isset($indexes[$keyName])))
			{
				$columns = [];

			}
			$columns = $index[4];
			$indexes[$keyName] = $columns;
		}

		$indexObjects = [];

		foreach ($indexes as $name => $indexColumns) {
			$indexObjects[$name] = new Index($name, $indexColumns);
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

				$columns = [];

				$referencedColumns = [];

			}
			$columns = $reference[1];
			$referencedColumns = $reference[5];
			$references[$constraintName] = ["referencedSchema" => $referencedSchema, "referencedTable" => $referencedTable, "columns" => $columns, "referencedColumns" => $referencedColumns];
		}

		$referenceObjects = [];

		foreach ($references as $name => $arrayReference) {
			$referenceObjects[$name] = new Reference($name, ["referencedSchema" => $arrayReference["referencedSchema"], "referencedTable" => $arrayReference["referencedTable"], "columns" => $arrayReference["columns"], "referencedColumns" => $arrayReference["referencedColumns"]]);
		}

		return $referenceObjects;
	}

	public function tableOptions($tableName, $schemaName = null)
	{

		$sql = $this->_dialect->tableOptions($tableName, $schemaName);

		if ($sql)
		{
			return $this->fetchAll($sql, Db::FETCH_ASSOC)[0];
		}

		return [];
	}

	public function createSavepoint($name)
	{

		$dialect = $this->_dialect;

		if (!($dialect->supportsSavePoints()))
		{
			throw new Exception("Savepoints are not supported by this database adapter.");
		}

		return $this->execute($dialect->createSavepoint($name));
	}

	public function releaseSavepoint($name)
	{

		$dialect = $this->_dialect;

		if (!($dialect->supportsSavePoints()))
		{
			throw new Exception("Savepoints are not supported by this database adapter");
		}

		if (!($dialect->supportsReleaseSavePoints()))
		{
			return false;
		}

		return $this->execute($dialect->releaseSavepoint($name));
	}

	public function rollbackSavepoint($name)
	{

		$dialect = $this->_dialect;

		if (!($dialect->supportsSavePoints()))
		{
			throw new Exception("Savepoints are not supported by this database adapter");
		}

		return $this->execute($dialect->rollbackSavepoint($name));
	}

	public function setNestedTransactionsWithSavepoints($nestedTransactionsWithSavepoints)
	{
		if ($this->_transactionLevel > 0)
		{
			throw new Exception("Nested transaction with savepoints behavior cannot be changed while a transaction is open");
		}

		if (!($this->_dialect->supportsSavePoints()))
		{
			throw new Exception("Savepoints are not supported by this database adapter");
		}

		$this->_transactionsWithSavepoints = $nestedTransactionsWithSavepoints;

		return $this;
	}

	public function isNestedTransactionsWithSavepoints()
	{
		return $this->_transactionsWithSavepoints;
	}

	public function getNestedTransactionSavepointName()
	{
		return "PHALCON_SAVEPOINT_" . $this->_transactionLevel;
	}

	public function getDefaultIdValue()
	{
		return new RawValue("null");
	}

	public function getDefaultValue()
	{
		return new RawValue("DEFAULT");
	}

	public function supportSequences()
	{
		return false;
	}

	public function useExplicitIdValue()
	{
		return false;
	}

	public function getDescriptor()
	{
		return $this->_descriptor;
	}

	public function getConnectionId()
	{
		return $this->_connectionId;
	}

	public function getSQLStatement()
	{
		return $this->_sqlStatement;
	}

	public function getRealSQLStatement()
	{
		return $this->_sqlStatement;
	}

	public function getSQLBindTypes()
	{
		return $this->_sqlBindTypes;
	}


}