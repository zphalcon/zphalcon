<?php
namespace Phalcon\Assets\Resource;

use Phalcon\Assets\Resource as ResourceBase;

class Css extends ResourceBase
{
	public function __construct($path, $local = true, $filter = true, $attributes = null)
	{
		parent::__construct("css", $path, $local, $filter, $attributes);

	}


}