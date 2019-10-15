<?php
namespace Phalcon\Db;

use Phalcon\Db\Profiler\Item;

class Profiler
{
	protected $_allProfiles;
	protected $_activeProfile;
	protected $_totalSeconds = 0;

	public function startProfile($sqlStatement, $sqlVariables = null, $sqlBindTypes = null)
	{

		$activeProfile = new Item();

		$activeProfile->setSqlStatement($sqlStatement);

		if (typeof($sqlVariables) == "array")
		{
			$activeProfile->setSqlVariables($sqlVariables);

		}

		if (typeof($sqlBindTypes) == "array")
		{
			$activeProfile->setSqlBindTypes($sqlBindTypes);

		}

		$activeProfile->setInitialTime(microtime(true));

		if (method_exists($this, "beforeStartProfile"))
		{
			$this->beforeStartProfile($activeProfile);

		}

		$this->_activeProfile = $activeProfile;

		return $this;
	}

	public function stopProfile()
	{

		$finalTime = microtime(true);
		$activeProfile = $this->_activeProfile;

		$activeProfile->setFinalTime($finalTime);

		$initialTime = $activeProfile->getInitialTime();
		$this->_totalSeconds = $this->_totalSeconds + $finalTime - $initialTime;
		$this->_allProfiles[] = $activeProfile;

		if (method_exists($this, "afterEndProfile"))
		{
			$this->afterEndProfile($activeProfile);

		}

		return $this;
	}

	public function getNumberTotalStatements()
	{
		return count($this->_allProfiles);
	}

	public function getTotalElapsedSeconds()
	{
		return $this->_totalSeconds;
	}

	public function getProfiles()
	{
		return $this->_allProfiles;
	}

	public function reset()
	{
		$this->_allProfiles = [];

		return $this;
	}

	public function getLastProfile()
	{
		return $this->_activeProfile;
	}


}