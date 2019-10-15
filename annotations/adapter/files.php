<?php
namespace Phalcon\Annotations\Adapter;

use Phalcon\Annotations\Adapter;
use Phalcon\Annotations\Reflection;
use Phalcon\Annotations\Exception;

class Files extends Adapter
{
	protected $_annotationsDir = "./";

	public function __construct($options = null)
	{

		if (typeof($options) == "array")
		{
			if (function() { if(isset($options["annotationsDir"])) {$annotationsDir = $options["annotationsDir"]; return $annotationsDir; } else { return false; } }())
			{
				$this->_annotationsDir = $annotationsDir;

			}

		}

	}

	public function read($key)
	{

		$path = $this->_annotationsDir . prepare_virtual_path($key, "_") . ".php";

		if (file_exists($path))
		{
			return require $path;
		}

		return false;
	}

	public function write($key, $data)
	{

		$path = $this->_annotationsDir . prepare_virtual_path($key, "_") . ".php";

		if (file_put_contents($path, "<?php return " . var_export($data, true) . "; ") === false)
		{
			throw new Exception("Annotations directory cannot be written");
		}

	}


}