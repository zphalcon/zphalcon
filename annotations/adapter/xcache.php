<?php
namespace Phalcon\Annotations\Adapter;

use Phalcon\Annotations\Adapter;
use Phalcon\Annotations\Reflection;

class Xcache extends Adapter
{
	public function read($key)
	{

		$serialized = xcache_get(strtolower("_PHAN" . $key));

		if (typeof($serialized) == "string")
		{
			$data = unserialize($serialized);

			if (typeof($data) == "object")
			{
				return $data;
			}

		}

		return false;
	}

	public function write($key, $data)
	{
		xcache_set(strtolower("_PHAN" . $key), serialize($data));

	}


}