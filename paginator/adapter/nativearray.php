<?php
namespace Phalcon\Paginator\Adapter;

use Phalcon\Paginator\Exception;
use Phalcon\Paginator\Adapter;

class NativeArray extends Adapter
{
	protected $_config = null;

	public function __construct($config)
	{

		$this->_config = $config;

		if (function() { if(isset($config["limit"])) {$limit = $config["limit"]; return $limit; } else { return false; } }())
		{
			$this->_limitRows = $limit;

		}

		if (function() { if(isset($config["page"])) {$page = $config["page"]; return $page; } else { return false; } }())
		{
			$this->_page = $page;

		}

	}

	public function getPaginate()
	{
		return $this->paginate();
	}

	public function paginate()
	{



		$config = $this->_config;
		$items = $config["data"];

		if (typeof($items) <> "array")
		{
			throw new Exception("Invalid data for paginator");
		}

		$show = (int) $this->_limitRows;
		$pageNumber = (int) $this->_page;

		if ($pageNumber <= 0)
		{
			$pageNumber = 1;

		}

		$number = count($items);
		$roundedTotal = $number * floatval($show);
		$totalPages = (int) $roundedTotal;

		if ($totalPages <> $roundedTotal)
		{
			$totalPages++;

		}

		$items = array_slice($items, $show * $pageNumber - 1, $show);

		if ($pageNumber < $totalPages)
		{
			$next = $pageNumber + 1;

		}

		if ($pageNumber > 1)
		{
			$previous = $pageNumber - 1;

		}

		$page = new \stdClass();
		$page->items = $items;
		$page->first = 1;
		$page->before = $previous;
		$page->previous = $previous;
		$page->current = $pageNumber;
		$page->last = $totalPages;
		$page->next = $next;
		$page->total_pages = $totalPages;
		$page->total_items = $number;
		$page->limit = $this->_limitRows;

		return $page;
	}


}