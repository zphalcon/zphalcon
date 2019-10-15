<?php
namespace Phalcon\Mvc\Model;

use Phalcon\Db;
use Phalcon\Mvc\Model;
use Phalcon\Cache\BackendInterface;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Mvc\Model\MessageInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
abstract 
class Resultset implements ResultsetInterface, \Iterator, \SeekableIterator, \Countable, \ArrayAccess, \Serializable, \JsonSerializable
{
	const TYPE_RESULT_FULL = 0;
	const TYPE_RESULT_PARTIAL = 1;
	const HYDRATE_RECORDS = 0;
	const HYDRATE_OBJECTS = 2;
	const HYDRATE_ARRAYS = 1;

	protected $_result = false;
	protected $_cache;
	protected $_isFresh = true;
	protected $_pointer = 0;
	protected $_count;
	protected $_activeRow = null;
	protected $_rows = null;
	protected $_row = null;
	protected $_errorMessages;
	protected $_hydrateMode = 0;

	public function __construct($result, $cache = null)
	{

		if (typeof($result) <> "object")
		{
			$this->_count = 0;

			$this->_rows = [];

			return ;
		}

		$this->_result = $result;

		if ($cache !== null)
		{
			$this->_cache = $cache;

		}

		$result->setFetchMode(Db::FETCH_ASSOC);

		$rowCount = $result->numRows();

		$this->_count = $rowCount;

		if ($rowCount == 0)
		{
			$this->_rows = [];

			return ;
		}

		if ($rowCount <= 32)
		{
			$rows = $result->fetchAll();

			if (typeof($rows) == "array")
			{
				$this->_rows = $rows;

			}

		}

	}

	public function next()
	{
		$this->seek($this->_pointer + 1);

	}

	public function valid()
	{
		return $this->_pointer < $this->_count;
	}

	public function key()
	{
		if ($this->_pointer >= $this->_count)
		{
			return null;
		}

		return $this->_pointer;
	}

	public final function rewind()
	{
		$this->seek(0);

	}

	public final function seek($position)
	{

		if ($this->_pointer <> $position || $this->_row === null)
		{
			if (typeof($this->_rows) == "array")
			{
				if (function() { if(isset($this->_rows[$position])) {$row = $this->_rows[$position]; return $row; } else { return false; } }())
				{
					$this->_row = $row;

				}

				$this->_pointer = $position;

				$this->_activeRow = null;

				return ;
			}

			$result = $this->_result;

			if ($this->_row === null && $this->_pointer === 0)
			{
				$this->_row = $result->fetch();

			}

			if ($this->_pointer > $position)
			{
				$result->dataSeek($position);

				$this->_row = $result->fetch();

				$this->_pointer = $position;

			}

			while ($this->_pointer < $position) {
				$this->_row = $result->fetch();
				$this->_pointer++;			}

			$this->_pointer = $position;

			$this->_activeRow = null;

		}

	}

	public final function count()
	{
		return $this->_count;
	}

	public function offsetExists($index)
	{
		return $index < $this->_count;
	}

	public function offsetGet($index)
	{
		if ($index < $this->_count)
		{
			$this->seek($index);

			return $this->current();
		}

		throw new Exception("The index does not exist in the cursor");
	}

	public function offsetSet($index, $value)
	{
		throw new Exception("Cursor is an immutable ArrayAccess object");
	}

	public function offsetUnset($offset)
	{
		throw new Exception("Cursor is an immutable ArrayAccess object");
	}

	public function getType()
	{
		return typeof($this->_rows) == "array" ? self::TYPE_RESULT_FULL : self::TYPE_RESULT_PARTIAL;
	}

	public function getFirst()
	{
		if ($this->_count == 0)
		{
			return false;
		}

		$this->seek(0);

		return $this->current();
	}

	public function getLast()
	{

		$count = $this->_count;

		if ($count == 0)
		{
			return false;
		}

		$this->seek($count - 1);

		return $this->current();
	}

	public function setIsFresh($isFresh)
	{
		$this->_isFresh = $isFresh;

		return $this;
	}

	public function isFresh()
	{
		return $this->_isFresh;
	}

	public function setHydrateMode($hydrateMode)
	{
		$this->_hydrateMode = $hydrateMode;

		return $this;
	}

	public function getHydrateMode()
	{
		return $this->_hydrateMode;
	}

	public function getCache()
	{
		return $this->_cache;
	}

	public function getMessages()
	{
		return $this->_errorMessages;
	}

	public function update($data, $conditionCallback = null)
	{


		$transaction = false;

		$this->rewind();

		while ($this->valid()) {
			$record = $this->current();
			if ($transaction === false)
			{
				if (!(method_exists($record, "getWriteConnection")))
				{
					throw new Exception("The returned record is not valid");
				}

				$connection = $record->getWriteConnection();
				$transaction = true;

				$connection->begin();

			}
			if (typeof($conditionCallback) == "object")
			{
				if (call_user_func_array($conditionCallback, [$record]) === false)
				{
					$this->next();

					continue;

				}

			}
			if (!($record->save($data)))
			{
				$this->_errorMessages = $record->getMessages();

				$connection->rollback();

				$transaction = false;

				break;

			}
			$this->next();
		}

		if ($transaction === true)
		{
			$connection->commit();

		}

		return true;
	}

	public function delete($conditionCallback = null)
	{


		$result = true;

		$transaction = false;

		$this->rewind();

		while ($this->valid()) {
			$record = $this->current();
			if ($transaction === false)
			{
				if (!(method_exists($record, "getWriteConnection")))
				{
					throw new Exception("The returned record is not valid");
				}

				$connection = $record->getWriteConnection();
				$transaction = true;

				$connection->begin();

			}
			if (typeof($conditionCallback) == "object")
			{
				if (call_user_func_array($conditionCallback, [$record]) === false)
				{
					$this->next();

					continue;

				}

			}
			if (!($record->delete()))
			{
				$this->_errorMessages = $record->getMessages();

				$connection->rollback();

				$result = false;

				$transaction = false;

				break;

			}
			$this->next();
		}

		if ($transaction === true)
		{
			$connection->commit();

		}

		return $result;
	}

	public function filter($filter)
	{

		$records = [];
		$parameters = [];

		$this->rewind();

		while ($this->valid()) {
			$record = $this->current();
			$parameters[0] = $record;
			$processedRecord = call_user_func_array($filter, $parameters);
			if (typeof($processedRecord) <> "object" && typeof($processedRecord) <> "array")
			{
				$this->next();

				continue;

			}
			$records = $processedRecord;
			$this->next();
		}

		return $records;
	}

	public function jsonSerialize()
	{

		$records = [];

		$this->rewind();

		while ($this->valid()) {
			$current = $this->current();
			if (typeof($current) == "object" && method_exists($current, "jsonSerialize"))
			{
				$records = $current->jsonSerialize();

			}
			$this->next();
		}

		return $records;
	}


}