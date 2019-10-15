<?php
namespace Phalcon\Translate\Adapter;

use Phalcon\Translate\Exception;
use Phalcon\Translate\Adapter;

class Csv extends Adapter implements \ArrayAccess
{
	protected $_translate = [];

	public function __construct($options)
	{
		parent::__construct($options);

		if (!(isset($options["content"])))
		{
			throw new Exception("Parameter 'content' is required");
		}

		$this->_load($options["content"], 0, ";", "\"");

	}

	private function _load($file, $length, $delimiter, $enclosure)
	{

		$fileHandler = fopen($file, "rb");

		if (typeof($fileHandler) !== "resource")
		{
			throw new Exception("Error opening translation file '" . $file . "'");
		}

		while (true) {
			$data = fgetcsv($fileHandler, $length, $delimiter, $enclosure);
			if ($data === false)
			{
				break;

			}
			if (substr($data[0], 0, 1) === "#" || !(isset($data[1])))
			{
				continue;

			}
			$this[$data[0]] = $data[1];
		}

		fclose($fileHandler);

	}

	public function query($index, $placeholders = null)
	{

		if (!(function() { if(isset($this->_translate[$index])) {$translation = $this->_translate[$index]; return $translation; } else { return false; } }()))
		{
			$translation = $index;

		}

		return $this->replacePlaceholders($translation, $placeholders);
	}

	public function exists($index)
	{
		return isset($this->_translate[$index]);
	}


}