<?php
namespace Phalcon\Assets\Filters;

use Phalcon\Assets\FilterInterface;

class Jsmin implements FilterInterface
{
	public function filter($content)
	{
		return phalcon_jsmin($content);
	}


}