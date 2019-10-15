<?php
namespace Phalcon\Translate\Adapter;

use Phalcon\Translate\Exception;
use Phalcon\Translate\Adapter;

class Gettext extends Adapter implements \ArrayAccess
{
	protected $_directory;
	protected $_defaultDomain;
	protected $_locale;
	protected $_category;

	public function __construct($options)
	{
		if (!(function_exists("gettext")))
		{
			throw new Exception("This class requires the gettext extension for PHP");
		}

		parent::__construct($options);

		$this->prepareOptions($options);

	}

	public function query($index, $placeholders = null)
	{

		$translation = gettext($index);

		return $this->replacePlaceholders($translation, $placeholders);
	}

	public function exists($index)
	{

		$result = $this->query($index);

		return $result !== $index;
	}

	public function nquery($msgid1, $msgid2, $count, $placeholders = null, $domain = null)
	{

		if (!($domain))
		{
			$translation = ngettext($msgid1, $msgid2, $count);

		}

		return $this->replacePlaceholders($translation, $placeholders);
	}

	public function setDomain($domain)
	{
		return textdomain($domain);
	}

	public function resetDomain()
	{
		return textdomain($this->getDefaultDomain());
	}

	public function setDefaultDomain($domain)
	{
		$this->_defaultDomain = $domain;

	}

	public function setDirectory($directory)
	{

		if (empty($directory))
		{
			return ;
		}

		$this->_directory = $directory;

		if (typeof($directory) === "array")
		{
			foreach ($directory as $key => $value) {
				bindtextdomain($key, $value);
			}

		}

	}

	public function setLocale($category, $locale)
	{
		$this->_locale = call_user_func_array("setlocale", func_get_args());

		$this->_category = $category;

		putenv("LC_ALL=" . $this->_locale);

		putenv("LANG=" . $this->_locale);

		putenv("LANGUAGE=" . $this->_locale);

		setlocale(LC_ALL, $this->_locale);

		return $this->_locale;
	}

	protected function prepareOptions($options)
	{
		if (!(isset($options["locale"])))
		{
			throw new Exception("Parameter 'locale' is required");
		}

		if (!(isset($options["directory"])))
		{
			throw new Exception("Parameter 'directory' is required");
		}

		$options = array_merge($this->getOptionsDefault(), $options);

		$this->setLocale($options["category"], $options["locale"]);

		$this->setDefaultDomain($options["defaultDomain"]);

		$this->setDirectory($options["directory"]);

		$this->setDomain($options["defaultDomain"]);

	}

	protected function getOptionsDefault()
	{
		return ["category" => LC_ALL, "defaultDomain" => "messages"];
	}


}