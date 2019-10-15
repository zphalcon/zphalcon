<?php
namespace Phalcon\Paginator\Adapter;

use Phalcon\Paginator\Exception;
use Phalcon\Paginator\Adapter;

class Model extends Adapter
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


		$show = (int) $this->_limitRows;
		$config = $this->_config;
		$items = $config["data"];
		$pageNumber = (int) $this->_page;

		if (typeof($items) <> "object")
		{
			throw new Exception("Invalid data for paginator");
		}

		if ($pageNumber <= 0)
		{
			$pageNumber = 1;

		}

		if ($show <= 0)
		{
			throw new Exception("The start page number is zero or less");
		}

		$n = count($items);
		$lastShowPage = $pageNumber - 1;
		$start = $show * $lastShowPage;
		$pageItems = [];

		if ($n % $show <> 0)
		{
			$totalPages = (int) $n * $show + 1;

		}

		if ($n > 0)
		{
			if ($start <= $n)
			{
				$items->seek($start);

			}

			$i = 1;

			while ($items->valid()) {
				$pageItems = $items->current();
				if ($i >= $show)
				{
					break;

				}
				$i++;
				$items->next();
			}

		}

		$next = $pageNumber + 1;

		if ($next > $totalPages)
		{
			$next = $totalPages;

		}

		if ($pageNumber > 1)
		{
			$previous = $pageNumber - 1;

		}

		$page = new \stdClass();
		$page->items = $pageItems;
		$page->first = 1;
		$page->before = $previous;
		$page->previous = $previous;
		$page->current = $pageNumber;
		$page->last = $totalPages;
		$page->next = $next;
		$page->total_pages = $totalPages;
		$page->total_items = $n;
		$page->limit = $this->_limitRows;

		return $page;
	}


}