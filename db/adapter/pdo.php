<?php
namespace Phalcon\Db\Adapter;

use Phalcon\Db\Adapter;
use Phalcon\Db\Exception;
use Phalcon\Db\Column;
use Phalcon\Db\ResultInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Db\Result\Pdo as ResultPdo;
abstract 
class Pdo extends Adapter
{
	protected $_pdo;
	protected $_affectedRows;

	public function __construct($descriptor)
	{
		$this->connect($descriptor);

		parent::__construct($descriptor);

	}

	public function connect($descriptor = null)
	{

		if (empty($descriptor))
		{
			$descriptor = (array) $this->_descriptor;

		}

		if (function() { if(isset($descriptor["username"])) {$username = $descriptor["username"]; return $username; } else { return false; } }())
		{
			unset($descriptor["username"]);

		}

		if (function() { if(isset($descriptor["password"])) {$password = $descriptor["password"]; return $password; } else { return false; } }())
		{
			unset($descriptor["password"]);

		}

		if (function() { if(isset($descriptor["options"])) {$options = $descriptor["options"]; return $options; } else { return false; } }())
		{
			unset($descriptor["options"]);

		}

		foreach ($options as $key => $value) {
			if (typeof($key) == "string" && defined("\PDO::" . $key->upper()))
			{
				$options[constant("\PDO::" . $key->upper())] = $value;

				unset($options[$key]);

			}
		}

		if (function() { if(isset($descriptor["persistent"])) {$persistent = $descriptor["persistent"]; return $persistent; } else { return false; } }())
		{
			if ($persistent)
			{
				$options[\Pdo::ATTR_PERSISTENT] = true;

			}

			unset($descriptor["persistent"]);

		}

		if (isset($descriptor["dialectClass"]))
		{
			unset($descriptor["dialectClass"]);

		}

		if (!(function() { if(isset($descriptor["dsn"])) {$dsnAttributes = $descriptor["dsn"]; return $dsnAttributes; } else { return false; } }()))
		{
			$dsnParts = [];

			foreach ($descriptor as $key => $value) {
				$dsnParts = $key . "=" . $value;
			}

			$dsnAttributes = join(";", $dsnParts);

		}

		$options[\Pdo::ATTR_ERRMODE] = \Pdo::ERRMODE_EXCEPTION;

		$this->_pdo = new \Pdo($this->_type . ":" . $dsnAttributes, $username, $password, $options);

		return true;
	}

	public function prepare($sqlStatement)
	{
		return $this->_pdo->prepare($sqlStatement);
	}

	public function executePrepared($statement, $placeholders, $dataTypes)
	{

		foreach ($placeholders as $wildcard => $value) {
			if (typeof($wildcard) == "integer")
			{
				$parameter = $wildcard + 1;

			}
			if (typeof($dataTypes) == "array" && function() { if(isset($dataTypes[$wildcard])) {$type = $dataTypes[$wildcard]; return $type; } else { return false; } }())
			{
				if ($type == Column::BIND_PARAM_DECIMAL)
				{
					$castValue = doubleval($value);
					$type = Column::BIND_SKIP;

				}

				if (typeof($castValue) <> "array")
				{
					if ($type == Column::BIND_SKIP)
					{
						$statement->bindValue($parameter, $castValue);

					}

				}

			}
		}

		$statement->execute();

		return $statement;
	}

	public function query($sqlStatement, $bindParams = null, $bindTypes = null)
	{

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			$this->_sqlStatement = $sqlStatement;
			$this->_sqlVariables = $bindParams;
			$this->_sqlBindTypes = $bindTypes;

			if ($eventsManager->fire("db:beforeQuery", $this) === false)
			{
				return false;
			}

		}

		$pdo = $this->_pdo;

		if (typeof($bindParams) == "array")
		{
			$params = $bindParams;

			$types = $bindTypes;

		}

		$statement = $pdo->prepare($sqlStatement);

		if (typeof($statement) == "object")
		{
			$statement = $this->executePrepared($statement, $params, $types);

		}

		if (typeof($statement) == "object")
		{
			if (typeof($eventsManager) == "object")
			{
				$eventsManager->fire("db:afterQuery", $this);

			}

			return new ResultPdo($this, $statement, $sqlStatement, $bindParams, $bindTypes);
		}

		return $statement;
	}

	public function execute($sqlStatement, $bindParams = null, $bindTypes = null)
	{

		$eventsManager = $this->_eventsManager;

		if (typeof($eventsManager) == "object")
		{
			$this->_sqlStatement = $sqlStatement;
			$this->_sqlVariables = $bindParams;
			$this->_sqlBindTypes = $bindTypes;

			if ($eventsManager->fire("db:beforeQuery", $this) === false)
			{
				return false;
			}

		}

		$affectedRows = 0;

		$pdo = $this->_pdo;

		if (typeof($bindParams) == "array")
		{
			$statement = $pdo->prepare($sqlStatement);

			if (typeof($statement) == "object")
			{
				$newStatement = $this->executePrepared($statement, $bindParams, $bindTypes);
				$affectedRows = $newStatement->rowCount();

			}

		}

		if (typeof($affectedRows) == "integer")
		{
			$this->_affectedRows = $affectedRows;

			if (typeof($eventsManager) == "object")
			{
				$eventsManager->fire("db:afterQuery", $this);

			}

		}

		return true;
	}

	public function affectedRows()
	{
		return $this->_affectedRows;
	}

	public function close()
	{

		$pdo = $this->_pdo;

		if (typeof($pdo) == "object")
		{
			$this->_pdo = null;

		}

		return true;
	}

	public function escapeString($str)
	{
		return $this->_pdo->quote($str);
	}

	public function convertBoundParams($sql, $params = [])
	{

		$placeHolders = [];
		$bindPattern = "/\\?([0-9]+)|:([a-zA-Z0-9_]+):/";
		$matches = null;
		$setOrder = 2;

		if (preg_match_all($bindPattern, $sql, $matches, $setOrder))
		{
			foreach ($matches as $placeMatch) {
				if (!(function() { if(isset($params[$placeMatch[1]])) {$value = $params[$placeMatch[1]]; return $value; } else { return false; } }()))
				{
					if (isset($placeMatch[2]))
					{
						if (!(function() { if(isset($params[$placeMatch[2]])) {$value = $params[$placeMatch[2]]; return $value; } else { return false; } }()))
						{
							throw new Exception("Matched parameter wasn't found in parameters list");
						}

					}

				}
				$placeHolders = $value;
			}

			$boundSql = preg_replace($bindPattern, "?", $sql);

		}

		return ["sql" => $boundSql, "params" => $placeHolders];
	}

	public function lastInsertId($sequenceName = null)
	{

		$pdo = $this->_pdo;

		if (typeof($pdo) <> "object")
		{
			return false;
		}

		return $pdo->lastInsertId($sequenceName);
	}

	public function begin($nesting = true)
	{

		$pdo = $this->_pdo;

		if (typeof($pdo) <> "object")
		{
			return false;
		}

		$this->_transactionLevel++;
		$transactionLevel = (int) $this->_transactionLevel;

		if ($transactionLevel == 1)
		{
			$eventsManager = $this->_eventsManager;

			if (typeof($eventsManager) == "object")
			{
				$eventsManager->fire("db:beginTransaction", $this);

			}

			return $pdo->beginTransaction();
		}

		return false;
	}

	public function rollback($nesting = true)
	{

		$pdo = $this->_pdo;

		if (typeof($pdo) <> "object")
		{
			return false;
		}

		$transactionLevel = (int) $this->_transactionLevel;

		if (!($transactionLevel))
		{
			throw new Exception("There is no active transaction");
		}

		if ($transactionLevel == 1)
		{
			$eventsManager = $this->_eventsManager;

			if (typeof($eventsManager) == "object")
			{
				$eventsManager->fire("db:rollbackTransaction", $this);

			}

			$this->_transactionLevel--;
			return $pdo->rollback();
		}

		if ($transactionLevel > 0)
		{
			$this->_transactionLevel--;
		}

		return false;
	}

	public function commit($nesting = true)
	{

		$pdo = $this->_pdo;

		if (typeof($pdo) <> "object")
		{
			return false;
		}

		$transactionLevel = (int) $this->_transactionLevel;

		if (!($transactionLevel))
		{
			throw new Exception("There is no active transaction");
		}

		if ($transactionLevel == 1)
		{
			$eventsManager = $this->_eventsManager;

			if (typeof($eventsManager) == "object")
			{
				$eventsManager->fire("db:commitTransaction", $this);

			}

			$this->_transactionLevel--;
			return $pdo->commit();
		}

		if ($transactionLevel > 0)
		{
			$this->_transactionLevel--;
		}

		return false;
	}

	public function getTransactionLevel()
	{
		return $this->_transactionLevel;
	}

	public function isUnderTransaction()
	{

		$pdo = $this->_pdo;

		if (typeof($pdo) == "object")
		{
			return $pdo->inTransaction();
		}

		return false;
	}

	public function getInternalHandler()
	{
		return $this->_pdo;
	}

	public function getErrorInfo()
	{
		return $this->_pdo->errorInfo();
	}


}