<?php
namespace Phalcon\Db\Result;

use Phalcon\Db;
use Phalcon\Db\ResultInterface;
TODO: cblock

class Pdo implements ResultInterface
{
	protected $_connection;
	protected $_result;
	protected $_fetchMode = Db::FETCH_OBJ;
	protected $_pdoStatement;
	protected $_sqlStatement;
	protected $_bindParams;
	protected $_bindTypes;
	protected $_rowCount = false;

	public function __construct($connection, $result, $sqlStatement = null, $bindParams = null, $bindTypes = null)
	{
		$this->_connection = $connection;
		$this->_pdoStatement = $result;

		if ($sqlStatement !== null)
		{
			$this->_sqlStatement = $sqlStatement;

		}

		if ($bindParams !== null)
		{
			$this->_bindParams = $bindParams;

		}

		if ($bindTypes !== null)
		{
			$this->_bindTypes = $bindTypes;

		}

	}

	public function execute()
	{
		return $this->_pdoStatement->execute();
	}

	public function fetch($fetchStyle = null, $cursorOrientation = null, $cursorOffset = null)
	{
		return $this->_pdoStatement->fetch($fetchStyle, $cursorOrientation, $cursorOffset);
	}

	public function fetchArray()
	{
		return $this->_pdoStatement->fetch();
	}

	public function fetchAll($fetchStyle = null, $fetchArgument = null, $ctorArgs = null)
	{

		$pdoStatement = $this->_pdoStatement;

		if (typeof($fetchStyle) == "integer")
		{
			if ($fetchStyle == Db::FETCH_CLASS)
			{
				return $pdoStatement->fetchAll($fetchStyle, $fetchArgument, $ctorArgs);
			}

			if ($fetchStyle == Db::FETCH_COLUMN)
			{
				return $pdoStatement->fetchAll($fetchStyle, $fetchArgument);
			}

			if ($fetchStyle == Db::FETCH_FUNC)
			{
				return $pdoStatement->fetchAll($fetchStyle, $fetchArgument);
			}

			return $pdoStatement->fetchAll($fetchStyle);
		}

		return $pdoStatement->fetchAll();
	}

	public function numRows()
	{

		$rowCount = $this->_rowCount;

		if ($rowCount === false)
		{
			$connection = $this->_connection;
			$type = $connection->getType();

			if ($type == "mysql" || $type == "pgsql")
			{
				$pdoStatement = $this->_pdoStatement;
				$rowCount = $pdoStatement->rowCount();

			}

			if ($rowCount === false)
			{
				$sqlStatement = $this->_sqlStatement;

				if (!(starts_with($sqlStatement, "SELECT COUNT(*) ")))
				{
					$matches = null;

					if (preg_match("/^SELECT\\s+(.*)/i", $sqlStatement, $matches))
					{
						$result = $connection->query("SELECT COUNT(*) \"numrows\" FROM (SELECT " . $matches[1] . ")", $this->_bindParams, $this->_bindTypes);

						$row = $result->fetch();
						$rowCount = $row["numrows"];

					}

				}

			}

			$this->_rowCount = $rowCount;

		}

		return $rowCount;
	}

	public function dataSeek($number)
	{


		$connection = $this->_connection;
		$pdo = $connection->getInternalHandler();
		$sqlStatement = $this->_sqlStatement;
		$bindParams = $this->_bindParams;

		if (typeof($bindParams) == "array")
		{
			$statement = $pdo->prepare($sqlStatement);

			if (typeof($statement) == "object")
			{
				$statement = $connection->executePrepared($statement, $bindParams, $this->_bindTypes);

			}

		}

		$this->_pdoStatement = $statement;

		$n = -1;
		$number--;

		while ($n <> $number) {
			$statement->fetch();
			$n++;
		}

	}

	public function setFetchMode($fetchMode, $colNoOrClassNameOrObject = null, $ctorargs = null)
	{

		$pdoStatement = $this->_pdoStatement;

		if ($fetchMode == Db::FETCH_CLASS)
		{
			if ($pdoStatement->setFetchMode($fetchMode, $colNoOrClassNameOrObject, $ctorargs))
			{
				$this->_fetchMode = $fetchMode;

				return true;
			}

			return false;
		}

		if ($fetchMode == Db::FETCH_INTO)
		{
			if ($pdoStatement->setFetchMode($fetchMode, $colNoOrClassNameOrObject))
			{
				$this->_fetchMode = $fetchMode;

				return true;
			}

			return false;
		}

		if ($fetchMode == Db::FETCH_COLUMN)
		{
			if ($pdoStatement->setFetchMode($fetchMode, $colNoOrClassNameOrObject))
			{
				$this->_fetchMode = $fetchMode;

				return true;
			}

			return false;
		}

		if ($pdoStatement->setFetchMode($fetchMode))
		{
			$this->_fetchMode = $fetchMode;

			return true;
		}

		return false;
	}

	public function getInternalResult()
	{
		return $this->_pdoStatement;
	}


}