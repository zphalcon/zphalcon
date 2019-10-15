<?php
namespace Phalcon\Db\Profiler;


class Item
{
	protected $_sqlStatement;
	protected $_sqlVariables;
	protected $_sqlBindTypes;
	protected $_initialTime;
	protected $_finalTime;

	public function getTotalElapsedSeconds()
	{
		return $this->_finalTime - $this->_initialTime;
	}


}