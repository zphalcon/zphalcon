<?php
namespace Phalcon\Mvc\Model\Query;

abstract 
class Lang
{
	public static function parsePHQL($phql)
	{
		return phql_parse_phql($phql);
	}


}