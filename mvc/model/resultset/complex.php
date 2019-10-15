<?php
namespace Phalcon\Mvc\Model\Resultset;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Row;
use Phalcon\Db\ResultInterface;
use Phalcon\Mvc\Model\Resultset;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Cache\BackendInterface;
use Phalcon\Mvc\Model\ResultsetInterface;

class Complex extends Resultset implements ResultsetInterface
{
	protected $_columnTypes;
	protected $_disableHydration = false;

	public function __construct($columnTypes, $result = null, $cache = null)
	{
		$this->_columnTypes = $columnTypes;

		parent::__construct($result, $cache);

	}

	public final function current()
	{

		$activeRow = $this->_activeRow;

		if ($activeRow !== null)
		{
			return $activeRow;
		}

		$row = $this->_row;

		if ($this->_disableHydration)
		{
			$this->_activeRow = $row;

			return $row;
		}

		if (typeof($row) <> "array")
		{
			$this->_activeRow = false;

			return false;
		}

		$hydrateMode = $this->_hydrateMode;

		switch ($hydrateMode) {
			case Resultset::HYDRATE_RECORDS:
				$activeRow = new Row();
				break;
			case Resultset::HYDRATE_ARRAYS:
				$activeRow = [];
				break;
			case Resultset::HYDRATE_OBJECTS:
			default:
				$activeRow = new \stdClass();
				break;

		}

		$dirtyState = 0;

		foreach ($this->_columnTypes as $alias => $column) {
			if (typeof($column) <> "array")
			{
				throw new Exception("Column type is corrupt");
			}
			$type = $column["type"];
			if ($type == "object")
			{
				$source = $column["column"];
				$attributes = $column["attributes"];
				$columnMap = $column["columnMap"];

				$rowModel = [];

				foreach ($attributes as $attribute) {
					$columnValue = $row["_" . $source . "_" . $attribute];
					$rowModel[$attribute] = $columnValue;
				}

				switch ($hydrateMode) {
					case Resultset::HYDRATE_RECORDS:
						if (!(function() { if(isset($column["keepSnapshots"])) {$keepSnapshots = $column["keepSnapshots"]; return $keepSnapshots; } else { return false; } }()))
						{
							$keepSnapshots = false;

						}
						if (globals_get("orm.late_state_binding"))
						{
							if ($column["instance"] instanceof $Model)
							{
								$modelName = get_class($column["instance"]);

							}

							$value = $modelName::cloneResultMap($column["instance"], $rowModel, $columnMap, $dirtyState, $keepSnapshots);

						}
						break;
					default:
						$value = Model::cloneResultMapHydrate($rowModel, $columnMap, $hydrateMode);
						break;

				}

				$attribute = $column["balias"];

			}
			if (!(function() { if(isset($column["eager"])) {$eager = $column["eager"]; return $eager; } else { return false; } }()))
			{
				switch ($hydrateMode) {
					case Resultset::HYDRATE_ARRAYS:
						$activeRow[$attribute] = $value;
						break;
					default:
						$activeRow->{$attribute} = $value;
						break;

				}

			}
		}

		$this->_activeRow = $activeRow;

		return $activeRow;
	}

	public function toArray()
	{

		$records = [];

		$this->rewind();

		while ($this->valid()) {
			$current = $this->current();
			$records = $current;
			$this->next();
		}

		return $records;
	}

	public function serialize()
	{

		$records = $this->toArray();

		$cache = $this->_cache;
		$columnTypes = $this->_columnTypes;
		$hydrateMode = $this->_hydrateMode;

		$serialized = serialize(["cache" => $cache, "rows" => $records, "columnTypes" => $columnTypes, "hydrateMode" => $hydrateMode]);

		return $serialized;
	}

	public function unserialize($data)
	{

		$this->_disableHydration = true;

		$resultset = unserialize($data);

		if (typeof($resultset) <> "array")
		{
			throw new Exception("Invalid serialization data");
		}

		$this->_rows = $resultset["rows"];
		$this->_count = count($resultset["rows"]);
		$this->_cache = $resultset["cache"];
		$this->_columnTypes = $resultset["columnTypes"];
		$this->_hydrateMode = $resultset["hydrateMode"];

	}


}