<?php
namespace Phalcon\Mvc\Model\Resultset;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset;
use Phalcon\Mvc\Model\Exception;
use Phalcon\Cache\BackendInterface;

class Simple extends Resultset
{
	protected $_model;
	protected $_columnMap;
	protected $_keepSnapshots = false;

	public function __construct($columnMap, $model, $result, $cache = null, $keepSnapshots = null)
	{
		$this->_model = $model;
		$this->_columnMap = $columnMap;

		$this->_keepSnapshots = $keepSnapshots;

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

		if (typeof($row) <> "array")
		{
			$this->_activeRow = false;

			return false;
		}

		$hydrateMode = $this->_hydrateMode;

		$columnMap = $this->_columnMap;

		switch ($hydrateMode) {
			case Resultset::HYDRATE_RECORDS:
				if (globals_get("orm.late_state_binding"))
				{
					if ($this->_model instanceof $\Phalcon\Mvc\Model)
					{
						$modelName = get_class($this->_model);

					}

					$activeRow = $modelName::cloneResultMap($this->_model, $row, $columnMap, Model::DIRTY_STATE_PERSISTENT, $this->_keepSnapshots);

				}
				break;

			default:
				$activeRow = Model::cloneResultMapHydrate($row, $columnMap, $hydrateMode);
				break;


		}

		$this->_activeRow = $activeRow;

		return $activeRow;
	}

	public function toArray($renameColumns = true)
	{

		$records = $this->_rows;

		if (typeof($records) <> "array")
		{
			$result = $this->_result;

			if ($this->_row !== null)
			{
				$result->execute();

			}

			$records = $result->fetchAll();

			$this->_row = null;

			$this->_rows = $records;

		}

		if ($renameColumns)
		{
			$columnMap = $this->_columnMap;

			if (typeof($columnMap) <> "array")
			{
				return $records;
			}

			$renamedRecords = [];

			if (typeof($records) == "array")
			{
				foreach ($records as $record) {
					$renamed = [];
					foreach ($record as $key => $value) {
						if (!(function() { if(isset($columnMap[$key])) {$renamedKey = $columnMap[$key]; return $renamedKey; } else { return false; } }()))
						{
							throw new Exception("Column '" . $key . "' is not part of the column map");
						}
						if (typeof($renamedKey) == "array")
						{
							if (!(function() { if(isset($renamedKey[0])) {$renamedKey = $renamedKey[0]; return $renamedKey; } else { return false; } }()))
							{
								throw new Exception("Column '" . $key . "' is not part of the column map");
							}

						}
						$renamed[$renamedKey] = $value;
					}
					$renamedRecords = $renamed;
				}

			}

			return $renamedRecords;
		}

		return $records;
	}

	public function serialize()
	{
		return serialize(["model" => $this->_model, "cache" => $this->_cache, "rows" => $this->toArray(false), "columnMap" => $this->_columnMap, "hydrateMode" => $this->_hydrateMode, "keepSnapshots" => $this->_keepSnapshots]);
	}

	public function unserialize($data)
	{

		$resultset = unserialize($data);

		if (typeof($resultset) <> "array")
		{
			throw new Exception("Invalid serialization data");
		}

		$this->_model = $resultset["model"];
		$this->_rows = $resultset["rows"];
		$this->_count = count($resultset["rows"]);
		$this->_cache = $resultset["cache"];
		$this->_columnMap = $resultset["columnMap"];
		$this->_hydrateMode = $resultset["hydrateMode"];

		if (function() { if(isset($resultset["keepSnapshots"])) {$keepSnapshots = $resultset["keepSnapshots"]; return $keepSnapshots; } else { return false; } }())
		{
			$this->_keepSnapshots = $keepSnapshots;

		}

	}


}