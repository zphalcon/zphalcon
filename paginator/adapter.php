<?php
namespace Phalcon\Paginator;

abstract 
class Adapter implements AdapterInterface
{
	protected $_limitRows = null;
	protected $_page = null;

	public function getLimit()
	{
		return $this->_limitRows;
	}

	public function setCurrentPage($page)
	{
		$this->_page = $page;

		return $this;
	}

	public function setLimit($limitRows)
	{
		$this->_limitRows = $limitRows;

		return $this;
	}


}