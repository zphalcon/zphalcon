<?php
namespace Phalcon\Assets\Resource;

use Phalcon\Assets\Resource as ResourceBase;

class Js extends ResourceBase
{
	public function __construct($path, $local = true, $filter = true, $attributes = null)
	{
		parent::__construct("js", $path, $local, $filter, $attributes);

	}


}