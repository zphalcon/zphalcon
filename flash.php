<?php
namespace Phalcon;

use Phalcon\Flash\Exception;
use Phalcon\Di\InjectionAwareInterface;
abstract 
class Flash implements FlashInterface, InjectionAwareInterface
{
	protected $_cssClasses;
	protected $_implicitFlush = true;
	protected $_automaticHtml = true;
	protected $_escaperService = null;
	protected $_autoescape = true;
	protected $_dependencyInjector = null;
	protected $_messages;

	public function __construct($cssClasses = null)
	{
		if (typeof($cssClasses) <> "array")
		{
			$cssClasses = ["error" => "errorMessage", "notice" => "noticeMessage", "success" => "successMessage", "warning" => "warningMessage"];

		}

		$this->_cssClasses = $cssClasses;

	}

	public function getAutoescape()
	{
		return $this->_autoescape;
	}

	public function setAutoescape($autoescape)
	{
		$this->_autoescape = $autoescape;

		return $this;
	}

	public function getEscaperService()
	{

		$escaper = $this->_escaperService;

		if (typeof($escaper) <> "object")
		{
			$dependencyInjector = $this->getDI();

			$escaper = $dependencyInjector->getShared("escaper");
			$this->_escaperService = $escaper;

		}

		return $escaper;
	}

	public function setEscaperService($escaperService)
	{
		$this->_escaperService = $escaperService;

		return $this;
	}

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

		return $this;
	}

	public function getDI()
	{

		$di = $this->_dependencyInjector;

		if (typeof($di) <> "object")
		{
			$di = Di::getDefault();

		}

		return $di;
	}

	public function setImplicitFlush($implicitFlush)
	{
		$this->_implicitFlush = $implicitFlush;

		return $this;
	}

	public function setAutomaticHtml($automaticHtml)
	{
		$this->_automaticHtml = $automaticHtml;

		return $this;
	}

	public function setCssClasses($cssClasses)
	{
		$this->_cssClasses = $cssClasses;

		return $this;
	}

	public function error($message)
	{
		return $this->message("error", $message);
	}

	public function notice($message)
	{
		return $this->message("notice", $message);
	}

	public function success($message)
	{
		return $this->message("success", $message);
	}

	public function warning($message)
	{
		return $this->message("warning", $message);
	}

	public function outputMessage($type, $message)
	{


		$automaticHtml = (bool) $this->_automaticHtml;
		$autoEscape = (bool) $this->_autoescape;

		if ($automaticHtml === true)
		{
			$classes = $this->_cssClasses;

			if (function() { if(isset($classes[$type])) {$typeClasses = $classes[$type]; return $typeClasses; } else { return false; } }())
			{
				if (typeof($typeClasses) == "array")
				{
					$cssClasses = " class=\"" . join(" ", $typeClasses) . "\"";

				}

			}

			$eol = PHP_EOL;

		}

		if ($autoEscape === true)
		{
			$escaper = $this->getEscaperService();

		}

		$implicitFlush = (bool) $this->_implicitFlush;

		if (typeof($message) == "array")
		{
			if ($implicitFlush === false)
			{
				$content = "";

			}

			foreach ($message as $msg) {
				if ($autoEscape === true)
				{
					$preparedMsg = $escaper->escapeHtml($msg);

				}
				if ($automaticHtml === true)
				{
					$htmlMessage = "<div" . $cssClasses . ">" . $preparedMsg . "</div>" . $eol;

				}
				if ($implicitFlush === true)
				{
					echo($htmlMessage);

				}
			}

			if ($implicitFlush === false)
			{
				return $content;
			}

		}

	}

	public function clear()
	{
		$this->_messages = [];

	}


}