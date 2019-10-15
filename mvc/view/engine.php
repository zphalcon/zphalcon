<?php
namespace Phalcon\Mvc\View;

use Phalcon\DiInterface;
use Phalcon\Di\Injectable;
use Phalcon\Mvc\ViewBaseInterface;
abstract 
class Engine extends Injectable implements EngineInterface
{
	protected $_view;

	public function __construct($view, $dependencyInjector = null)
	{
		$this->_view = $view;

		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getContent()
	{
		return $this->_view->getContent();
	}

	public function partial($partialPath, $params = null)
	{
		return $this->_view->partial($partialPath, $params);
	}

	public function getView()
	{
		return $this->_view;
	}


}