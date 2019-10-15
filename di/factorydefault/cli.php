<?php
namespace Phalcon\Di\FactoryDefault;

use Phalcon\Di\Service;
use Phalcon\Di\FactoryDefault;

class Cli extends FactoryDefault
{
	public function __construct()
	{
		parent::__construct();

		$this->_services = ["router" => new Service("router", "Phalcon\\Cli\\Router", true), "dispatcher" => new Service("dispatcher", "Phalcon\\Cli\\Dispatcher", true), "modelsManager" => new Service("modelsManager", "Phalcon\\Mvc\\Model\\Manager", true), "modelsMetadata" => new Service("modelsMetadata", "Phalcon\\Mvc\\Model\\MetaData\\Memory", true), "filter" => new Service("filter", "Phalcon\\Filter", true), "escaper" => new Service("escaper", "Phalcon\\Escaper", true), "annotations" => new Service("annotations", "Phalcon\\Annotations\\Adapter\\Memory", true), "security" => new Service("security", "Phalcon\\Security", true), "eventsManager" => new Service("eventsManager", "Phalcon\\Events\\Manager", true), "transactionManager" => new Service("transactionManager", "Phalcon\\Mvc\\Model\\Transaction\\Manager", true)];

	}


}