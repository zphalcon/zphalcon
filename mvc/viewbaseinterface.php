<?php
namespace Phalcon\Mvc;


interface ViewBaseInterface
{
	public function setViewsDir($viewsDir)
	{
	}

	public function getViewsDir()
	{
	}

	public function setParamToView($key, $value)
	{
	}

	public function setVar($key, $value)
	{
	}

	public function getParamsToView()
	{
	}

	public function getCache()
	{
	}

	public function cache($options = true)
	{
	}

	public function setContent($content)
	{
	}

	public function getContent()
	{
	}

	public function partial($partialPath, $params = null)
	{
	}


}