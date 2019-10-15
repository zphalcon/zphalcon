<?php
namespace Phalcon\Forms;


class Manager
{
	protected $_forms;

	public function create($name, $entity = null)
	{

		$form = new Form($entity);
		$this[$name] = $form;

		return $form;
	}

	public function get($name)
	{

		if (!(function() { if(isset($this->_forms[$name])) {$form = $this->_forms[$name]; return $form; } else { return false; } }()))
		{
			throw new Exception("There is no form with name='" . $name . "'");
		}

		return $form;
	}

	public function has($name)
	{
		return isset($this->_forms[$name]);
	}

	public function set($name, $form)
	{
		$this[$name] = $form;

		return $this;
	}


}