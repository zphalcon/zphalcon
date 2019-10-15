<?php
namespace Phalcon\Assets\Filters;

use Phalcon\Assets\FilterInterface;

class Cssmin implements FilterInterface
{
	public function filter($content)
	{
		return phalcon_cssmin($content);
	}


}