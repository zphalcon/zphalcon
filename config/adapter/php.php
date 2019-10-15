<?php
namespace Phalcon\Config\Adapter;

use Phalcon\Config;

class Php extends Config
{
	public function __construct($filePath)
	{
		parent::__construct(require $filePath);

	}


}