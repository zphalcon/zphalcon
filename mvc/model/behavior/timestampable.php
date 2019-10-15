<?php
namespace Phalcon\Mvc\Model\Behavior;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\Model\Exception;

class Timestampable extends Behavior
{
	public function notify($type, $model)
	{

		if ($this->mustTakeAction($type) !== true)
		{
			return null;
		}

		$options = $this->getOptions($type);

		if (typeof($options) == "array")
		{
			if (!(function() { if(isset($options["field"])) {$field = $options["field"]; return $field; } else { return false; } }()))
			{
				throw new Exception("The option 'field' is required");
			}

			$timestamp = null;

			if (function() { if(isset($options["format"])) {$format = $options["format"]; return $format; } else { return false; } }())
			{
				$timestamp = date($format);

			}

			if ($timestamp === null)
			{
				$timestamp = time();

			}

			if (typeof($field) == "array")
			{
				foreach ($field as $singleField) {
					$model->writeAttribute($singleField, $timestamp);
				}

			}

		}

	}


}