<?php
namespace Phalcon\Config\Adapter;

use Phalcon\Config;

class Json extends Config
{
	public function __construct($filePath)
	{
		parent::__construct(json_decode(file_get_contents($filePath), true));

	}


}