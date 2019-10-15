<?php
namespace Phalcon\Paginator\Adapter;

use Phalcon\Mvc\Model\Query\Builder;
use Phalcon\Paginator\Adapter;
use Phalcon\Paginator\Exception;
use Phalcon\Db;

class QueryBuilder extends Adapter
{
	protected $_config;
	protected $_builder;
	protected $_columns;

	public function __construct($config)
	{

		$this->_config = $config;

		if (!(function() { if(isset($config["builder"])) {$builder = $config["builder"]; return $builder; } else { return false; } }()))
		{
			throw new Exception("Parameter 'builder' is required");
		}

		if (!(function() { if(isset($config["limit"])) {$limit = $config["limit"]; return $limit; } else { return false; } }()))
		{
			throw new Exception("Parameter 'limit' is required");
		}

		if (function() { if(isset($config["columns"])) {$columns = $config["columns"]; return $columns; } else { return false; } }())
		{
			$this->_columns = $columns;

		}

		$this->setQueryBuilder($builder);

		$this->setLimit($limit);

		if (function() { if(isset($config["page"])) {$page = $config["page"]; return $page; } else { return false; } }())
		{
			$this->setCurrentPage($page);

		}

	}

	public function getCurrentPage()
	{
		return $this->_page;
	}

	public function setQueryBuilder($builder)
	{
		$this->_builder = $builder;

		return $this;
	}

	public function getQueryBuilder()
	{
		return $this->_builder;
	}

	public function getPaginate()
	{
		return $this->paginate();
	}

	public function paginate()
	{

		$originalBuilder = $this->_builder;

		$columns = $this->_columns;

		$builder = clone $originalBuilder;

		$totalBuilder = clone $builder;

		$limit = $this->_limitRows;

		$numberPage = (int) $this->_page;

		if (!($numberPage))
		{
			$numberPage = 1;

		}

		$number = $limit * $numberPage - 1;

		if ($number < $limit)
		{
			$builder->limit($limit);

		}

		$query = $builder->getQuery();

		if ($numberPage == 1)
		{
			$previous = 1;

		}

		$items = $query->execute();

		$hasHaving = !(empty($totalBuilder->getHaving()));


		$hasGroup = !(empty($groups));

		if ($hasHaving && !($hasGroup))
		{
			if (empty($columns))
			{
				throw new Exception("When having is set there should be columns option provided for which calculate row count");
			}

			$totalBuilder->columns($columns);

		}

		if ($hasGroup)
		{

			if (typeof($groups) == "array")
			{
				$groupColumn = implode(", ", $groups);

			}

			if (!($hasHaving))
			{
				$totalBuilder->groupBy(null)->columns(["COUNT(DISTINCT " . $groupColumn . ") AS [rowcount]"]);

			}

		}

		$totalBuilder->orderBy(null);

		$totalQuery = $totalBuilder->getQuery();

		if ($hasHaving)
		{
			$sql = $totalQuery->getSql();
			$modelClass = $builder->_models;

			if (typeof($modelClass) == "array")
			{
				$modelClass = array_values($modelClass)[0];

			}

			$model = new $modelClass();

			$dbService = $model->getReadConnectionService();

			$db = $totalBuilder->getDI()->get($dbService);

			$row = $db->fetchOne("SELECT COUNT(*) as \"rowcount\" FROM (" . $sql["sql"] . ") as T1", Db::FETCH_ASSOC, $sql["bind"]);
			$rowcount = $row ? intval($row["rowcount"]) : 0;
			$totalPages = intval(ceil($rowcount * $limit));

		}

		if ($numberPage < $totalPages)
		{
			$next = $numberPage + 1;

		}

		$page = new \stdClass();
		$page->items = $items;
		$page->first = 1;
		$page->before = $previous;
		$page->previous = $previous;
		$page->current = $numberPage;
		$page->last = $totalPages;
		$page->next = $next;
		$page->total_pages = $totalPages;
		$page->total_items = $rowcount;
		$page->limit = $this->_limitRows;

		return $page;
	}


}