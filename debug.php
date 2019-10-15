<?php
namespace Phalcon;


class Debug
{
	public $_uri = "//static.phalconphp.com/www/debug/3.0.x/";
	public $_theme = "default";
	protected $_hideDocumentRoot = false;
	protected $_showBackTrace = true;
	protected $_showFiles = true;
	protected $_showFileFragment = false;
	protected $_data;
	protected static $_isActive;

	public function setUri($uri)
	{
		$this->_uri = $uri;

		return $this;
	}

	public function setShowBackTrace($showBackTrace)
	{
		$this->_showBackTrace = $showBackTrace;

		return $this;
	}

	public function setShowFiles($showFiles)
	{
		$this->_showFiles = $showFiles;

		return $this;
	}

	public function setShowFileFragment($showFileFragment)
	{
		$this->_showFileFragment = $showFileFragment;

		return $this;
	}

	public function listen($exceptions = true, $lowSeverity = false)
	{
		if ($exceptions)
		{
			$this->listenExceptions();

		}

		if ($lowSeverity)
		{
			$this->listenLowSeverity();

		}

		return $this;
	}

	public function listenExceptions()
	{
		set_exception_handler([$this, "onUncaughtException"]);

		return $this;
	}

	public function listenLowSeverity()
	{
		set_error_handler([$this, "onUncaughtLowSeverity"]);

		set_exception_handler([$this, "onUncaughtException"]);

		return $this;
	}

	public function halt()
	{
		throw new Exception("Halted request");
	}

	public function debugVar($varz, $key = null)
	{
		$this->_data[] = [$varz, debug_backtrace(), time()];

		return $this;
	}

	public function clearVars()
	{
		$this->_data = null;

		return $this;
	}

	protected function _escapeString($value)
	{
		if (typeof($value) == "string")
		{
			return htmlentities(str_replace("\n", "\\n", $value), ENT_COMPAT, "utf-8");
		}

		return $value;
	}

	protected function _getArrayDump($argument, $n = 0)
	{

		$numberArguments = count($argument);

		if ($n >= 3 || $numberArguments == 0)
		{
			return null;
		}

		if ($numberArguments >= 10)
		{
			return $numberArguments;
		}

		$dump = [];

		foreach ($argument as $k => $v) {
			if ($v == "")
			{
				$varDump = "(empty string)";

			}
			$dump = "[" . $k . "] =&gt; " . $varDump;
		}

		return join(", ", $dump);
	}

	protected function _getVarDump($variable)
	{

		if (is_scalar($variable))
		{
			if (typeof($variable) == "boolean")
			{
				if ($variable)
				{
					return "true";
				}

			}

			if (typeof($variable) == "string")
			{
				return $this->_escapeString($variable);
			}

			return $variable;
		}

		if (typeof($variable) == "object")
		{
			$className = get_class($variable);

			if (method_exists($variable, "dump"))
			{
				$dumpedObject = $variable->dump();

				return "Object(" . $className . ": " . $this->_getArrayDump($dumpedObject) . ")";
			}

		}

		if (typeof($variable) == "array")
		{
			return "Array(" . $this->_getArrayDump($variable) . ")";
		}

		if (typeof($variable) == "null")
		{
			return "null";
		}

		return gettype($variable);
	}

	deprecated public function getMajorVersion()
	{

		$parts = explode(" ", \Phalcon\Version::get());

		return $parts[0];
	}

	public function getVersion()
	{

		$link = ["action" => "https://docs.phalconphp.com/en/" . Version::getPart(Version::VERSION_MAJOR) . ".0.0/", "text" => Version::get(), "local" => false, "target" => "_new"];

		return "<div class='version'>Phalcon Framework " . Tag::linkTo($link) . "</div>";
	}

	public function getCssSources()
	{

		$uri = $this->_uri;

		$sources = "<link href=\"" . $uri . "bower_components/jquery-ui/themes/ui-lightness/jquery-ui.min.css\" type=\"text/css\" rel=\"stylesheet\" />";

		$sources .= "<link href=\"" . $uri . "bower_components/jquery-ui/themes/ui-lightness/theme.css\" type=\"text/css\" rel=\"stylesheet\" />";

		$sources .= "<link href=\"" . $uri . "themes/default/style.css\" type=\"text/css\" rel=\"stylesheet\" />";

		return $sources;
	}

	public function getJsSources()
	{

		$uri = $this->_uri;

		$sources = "<script type=\"text/javascript\" src=\"" . $uri . "bower_components/jquery/dist/jquery.min.js\"></script>";

		$sources .= "<script type=\"text/javascript\" src=\"" . $uri . "bower_components/jquery-ui/jquery-ui.min.js\"></script>";

		$sources .= "<script type=\"text/javascript\" src=\"" . $uri . "bower_components/jquery.scrollTo/jquery.scrollTo.min.js\"></script>";

		$sources .= "<script type=\"text/javascript\" src=\"" . $uri . "prettify/prettify.js\"></script>";

		$sources .= "<script type=\"text/javascript\" src=\"" . $uri . "pretty.js\"></script>";

		return $sources;
	}

	protected final function showTraceItem($n, $trace)
	{

		$html = "<tr><td align=\"right\" valign=\"top\" class=\"error-number\">#" . $n . "</td><td>";

		if (function() { if(isset($trace["class"])) {$className = $trace["class"]; return $className; } else { return false; } }())
		{
			if (preg_match("/^Phalcon/", $className))
			{
				$prepareUriClass = str_replace("\\", "/", $className);

				$classNameWithLink = "<a target=\"_new\" href=\"//api.phalconphp.com/class/" . $prepareUriClass . ".html\">" . $className . "</a>";

			}

			$html .= "<span class=\"error-class\">" . $classNameWithLink . "</span>";

			$html .= $trace["type"];

		}

		$functionName = $trace["function"];

		if (isset($trace["class"]))
		{
			$functionNameWithLink = $functionName;

		}

		$html .= "<span class=\"error-function\">" . $functionNameWithLink . "</span>";

		if (function() { if(isset($trace["args"])) {$traceArgs = $trace["args"]; return $traceArgs; } else { return false; } }())
		{
			$arguments = [];

			foreach ($traceArgs as $argument) {
				$arguments = "<span class=\"error-parameter\">" . $this->_getVarDump($argument) . "</span>";
			}

			$html .= "(" . join(", ", $arguments) . ")";

		}

		if (function() { if(isset($trace["file"])) {$filez = $trace["file"]; return $filez; } else { return false; } }())
		{
			$line = (string) $trace["line"];

			$html .= "<br/><div class=\"error-file\">" . $filez . " (" . $line . ")</div>";

			$showFiles = $this->_showFiles;

			if ($showFiles)
			{
				$lines = file($filez);

				$numberLines = count($lines);

				$showFileFragment = $this->_showFileFragment;

				if ($showFileFragment)
				{

					if ($beforeLine < 1)
					{
						$firstLine = 1;

					}


					if ($afterLine > $numberLines)
					{
						$lastLine = $numberLines;

					}

					$html .= "<pre class=\"prettyprint highlight:" . $firstLine . ":" . $line . " linenums:" . $firstLine . "\">";

				}

				$i = $firstLine;

				while ($i <= $lastLine) {
					$linePosition = $i - 1;
					$currentLine = $lines[$linePosition];
					if ($showFileFragment)
					{
						if ($i == $firstLine)
						{
							if (preg_match("#\\*\\/#", rtrim($currentLine)))
							{
								$currentLine = str_replace("* /", " ", $currentLine);

							}

						}

					}
					if ($currentLine == "\n" || $currentLine == "\r\n")
					{
						$html .= "&nbsp;\n";

					}
					$i++;
				}

				$html .= "</pre>";

			}

		}

		$html .= "</td></tr>";

		return $html;
	}

	public function onUncaughtLowSeverity($severity, $message, $file, $line, $context)
	{
		if (error_reporting() & $severity)
		{
			throw new \ErrorException($message, 0, $severity, $file, $line);
		}

	}

	public function onUncaughtException($exception)
	{

		$obLevel = ob_get_level();

		if ($obLevel > 0)
		{
			ob_end_clean();

		}

		if (self::_isActive)
		{
			echo($exception->getMessage());

			return ;
		}

		self::_isActive = true;

		$className = get_class($exception);

		$escapedMessage = $this->_escapeString($exception->getMessage());

		$html = "<html><head><title>" . $className . ": " . $escapedMessage . "</title>";

		$html .= $this->getCssSources() . "</head><body>";

		$html .= $this->getVersion();

		$html .= "<div align=\"center\"><div class=\"error-main\">";

		$html .= "<h1>" . $className . ": " . $escapedMessage . "</h1>";

		$html .= "<span class=\"error-file\">" . $exception->getFile() . " (" . $exception->getLine() . ")</span>";

		$html .= "</div>";

		$showBackTrace = $this->_showBackTrace;

		if ($showBackTrace)
		{
			$dataVars = $this->_data;

			$html .= "<div class=\"error-info\"><div id=\"tabs\"><ul>";

			$html .= "<li><a href=\"#error-tabs-1\">Backtrace</a></li>";

			$html .= "<li><a href=\"#error-tabs-2\">Request</a></li>";

			$html .= "<li><a href=\"#error-tabs-3\">Server</a></li>";

			$html .= "<li><a href=\"#error-tabs-4\">Included Files</a></li>";

			$html .= "<li><a href=\"#error-tabs-5\">Memory</a></li>";

			if (typeof($dataVars) == "array")
			{
				$html .= "<li><a href=\"#error-tabs-6\">Variables</a></li>";

			}

			$html .= "</ul>";

			$html .= "<div id=\"error-tabs-1\"><table cellspacing=\"0\" align=\"center\" width=\"100%\">";

			foreach ($exception->getTrace() as $n => $traceItem) {
				$html .= $this->showTraceItem($n, $traceItem);
			}

			$html .= "</table></div>";

			$html .= "<div id=\"error-tabs-2\"><table cellspacing=\"0\" align=\"center\" class=\"superglobal-detail\">";

			$html .= "<tr><th>Key</th><th>Value</th></tr>";

			foreach ($_REQUEST as $keyRequest => $value) {
				if (typeof($value) <> "array")
				{
					$html .= "<tr><td class=\"key\">" . $keyRequest . "</td><td>" . $value . "</td></tr>";

				}
			}

			$html .= "</table></div>";

			$html .= "<div id=\"error-tabs-3\"><table cellspacing=\"0\" align=\"center\" class=\"superglobal-detail\">";

			$html .= "<tr><th>Key</th><th>Value</th></tr>";

			foreach ($_SERVER as $keyServer => $value) {
				$html .= "<tr><td class=\"key\">" . $keyServer . "</td><td>" . $this->_getVarDump($value) . "</td></tr>";
			}

			$html .= "</table></div>";

			$html .= "<div id=\"error-tabs-4\"><table cellspacing=\"0\" align=\"center\" class=\"superglobal-detail\">";

			$html .= "<tr><th>#</th><th>Path</th></tr>";

			foreach (get_included_files() as $keyFile => $value) {
				$html .= "<tr><td>" . $keyFile . "</th><td>" . $value . "</td></tr>";
			}

			$html .= "</table></div>";

			$html .= "<div id=\"error-tabs-5\"><table cellspacing=\"0\" align=\"center\" class=\"superglobal-detail\">";

			$html .= "<tr><th colspan=\"2\">Memory</th></tr><tr><td>Usage</td><td>" . memory_get_usage(true) . "</td></tr>";

			$html .= "</table></div>";

			if (typeof($dataVars) == "array")
			{
				$html .= "<div id=\"error-tabs-6\"><table cellspacing=\"0\" align=\"center\" class=\"superglobal-detail\">";

				$html .= "<tr><th>Key</th><th>Value</th></tr>";

				foreach ($dataVars as $keyVar => $dataVar) {
					$html .= "<tr><td class=\"key\">" . $keyVar . "</td><td>" . $this->_getVarDump($dataVar[0]) . "</td></tr>";
				}

				$html .= "</table></div>";

			}

			$html .= "</div>";

		}

		$html .= $this->getJsSources() . "</div></body></html>";

		echo($html);

		self::_isActive = false;

		return true;
	}


}