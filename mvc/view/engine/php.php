<?php
namespace Phalcon\Mvc\View\Engine;

use Phalcon\Mvc\View\Engine;

class Php extends Engine
{
	public function render($path, $params, $mustClean = false)
	{

		if ($mustClean === true)
		{
			ob_clean();

		}

		if (typeof($params) == "array")
		{
			foreach ($params as $key => $value) {
				$$key = $value;
			}

		}

		require($path);

		if ($mustClean === true)
		{
			$this->_view->setContent(ob_get_contents());

		}

	}


}