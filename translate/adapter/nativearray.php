<?php
namespace Phalcon\Translate\Adapter;

use Phalcon\Translate\Exception;
use Phalcon\Translate\Adapter;

class NativeArray extends Adapter implements \ArrayAccess
{
	protected $_translate;

	public function __construct($options)
	{

		parent::__construct($options);

		if (!(function() { if(isset($options["content"])) {$data = $options["content"]; return $data; } else { return false; } }()))
		{
			throw new Exception("Translation content was not provided");
		}

		if (typeof($data) !== "array")
		{
			throw new Exception("Translation data must be an array");
		}

		$this->_translate = $data;

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