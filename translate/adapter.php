<?php
namespace Phalcon\Translate;

use Phalcon\Translate\Exception;
use Phalcon\Translate\InterpolatorInterface;
use Phalcon\Translate\Interpolator\AssociativeArray;
abstract 
class Adapter implements AdapterInterface
{
	protected $_interpolator;

	public function __construct($options)
	{

		if (!(function() { if(isset($options["interpolator"])) {$interpolator = $options["interpolator"]; return $interpolator; } else { return false; } }()))
		{
			$interpolator = new AssociativeArray();

		}

		$this->setInterpolator($interpolator);

	}

	public function setInterpolator($interpolator)
	{
		$this->_interpolator = $interpolator;

		return $this;
	}

	public function t($translateKey, $placeholders = null)
	{
		return $this->query($translateKey, $placeholders);
	}

	public function _($translateKey, $placeholders = null)
	{
		return $this->query($translateKey, $placeholders);
	}

	public function offsetSet($offset, $value)
	{
		throw new Exception("Translate is an immutable ArrayAccess object");
	}

	public function offsetExists($translateKey)
	{
		return $this->exists($translateKey);
	}

	public function offsetUnset($offset)
	{
		throw new Exception("Translate is an immutable ArrayAccess object");
	}

	public function offsetGet($translateKey)
	{
		return $this->query($translateKey, null);
	}

	protected function replacePlaceholders($translation, $placeholders = null)
	{
		return $this->_interpolator->replacePlaceholders($translation, $placeholders);
	}


}