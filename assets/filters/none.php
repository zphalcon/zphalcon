<?php
namespace Phalcon\Assets\Filters;

use Phalcon\Assets\FilterInterface;

class None implements FilterInterface
{
	public function filter($content)
	{
		return $content;
	}


}