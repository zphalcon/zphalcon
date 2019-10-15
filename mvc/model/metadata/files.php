<?php
namespace Phalcon\Mvc\Model\MetaData;

use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\Exception;

class Files extends MetaData
{
	protected $_metaDataDir = "./";
	protected $_metaData = [];

	public function __construct($options = null)
	{

		if (typeof($options) == "array")
		{
			if (function() { if(isset($options["metaDataDir"])) {$metaDataDir = $options["metaDataDir"]; return $metaDataDir; } else { return false; } }())
			{
				$this->_metaDataDir = $metaDataDir;

			}

		}

	}

	public function read($key)
	{

		$path = $this->_metaDataDir . prepare_virtual_path($key, "_") . ".php";

		if (file_exists($path))
		{
			return require $path;
		}

		return null;
	}

	public function write($key, $data)
	{

		$path = $this->_metaDataDir . prepare_virtual_path($key, "_") . ".php";

		if (file_put_contents($path, "<?php return " . var_export($data, true) . "; ") === false)
		{
			throw new Exception("Meta-Data directory cannot be written");
		}

	}


}