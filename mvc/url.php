<?php
namespace Phalcon\Mvc;

use Phalcon\DiInterface;
use Phalcon\Mvc\UrlInterface;
use Phalcon\Mvc\Url\Exception;
use Phalcon\Mvc\RouterInterface;
use Phalcon\Mvc\Router\RouteInterface;
use Phalcon\Di\InjectionAwareInterface;

class Url implements UrlInterface, InjectionAwareInterface
{
	protected $_dependencyInjector;
	protected $_baseUri = null;
	protected $_staticBaseUri = null;
	protected $_basePath = null;
	protected $_router;

	public function setDI($dependencyInjector)
	{
		$this->_dependencyInjector = $dependencyInjector;

	}

	public function getDI()
	{
		return $this->_dependencyInjector;
	}

	public function setBaseUri($baseUri)
	{
		$this->_baseUri = $baseUri;

		if ($this->_staticBaseUri === null)
		{
			$this->_staticBaseUri = $baseUri;

		}

		return $this;
	}

	public function setStaticBaseUri($staticBaseUri)
	{
		$this->_staticBaseUri = $staticBaseUri;

		return $this;
	}

	public function getBaseUri()
	{

		$baseUri = $this->_baseUri;

		if ($baseUri === null)
		{
			if (function() { if(isset($_SERVER["PHP_SELF"])) {$phpSelf = $_SERVER["PHP_SELF"]; return $phpSelf; } else { return false; } }())
			{
				$uri = phalcon_get_uri($phpSelf);

			}

			if (!($uri))
			{
				$baseUri = "/";

			}

			$this->_baseUri = $baseUri;

		}

		return $baseUri;
	}

	public function getStaticBaseUri()
	{

		$staticBaseUri = $this->_staticBaseUri;

		if ($staticBaseUri !== null)
		{
			return $staticBaseUri;
		}

		return $this->getBaseUri();
	}

	public function setBasePath($basePath)
	{
		$this->_basePath = $basePath;

		return $this;
	}

	public function getBasePath()
	{
		return $this->_basePath;
	}

	public function get($uri = null, $args = null, $local = null, $baseUri = null)
	{


		if ($local == null)
		{
			if (typeof($uri) == "string" && memstr($uri, "//") || memstr($uri, ":"))
			{
				if (preg_match("#^((//)|([a-z0-9]+://)|([a-z0-9]+:))#i", $uri))
				{
					$local = false;

				}

			}

		}

		if (typeof($baseUri) <> "string")
		{
			$baseUri = $this->getBaseUri();

		}

		if (typeof($uri) == "array")
		{
			if (!(function() { if(isset($uri["for"])) {$routeName = $uri["for"]; return $routeName; } else { return false; } }()))
			{
				throw new Exception("It's necessary to define the route name with the parameter 'for'");
			}

			$router = $this->_router;

			if (typeof($router) <> "object")
			{
				$dependencyInjector = $this->_dependencyInjector;

				if (typeof($dependencyInjector) <> "object")
				{
					throw new Exception("A dependency injector container is required to obtain the 'router' service");
				}

				$router = $dependencyInjector->getShared("router");
				$this->_router = $router;

			}

			$route = $router->getRouteByName($routeName);

			if (typeof($route) <> "object")
			{
				throw new Exception("Cannot obtain a route using the name '" . $routeName . "'");
			}

			$uri = phalcon_replace_paths($route->getPattern(), $route->getReversedPaths(), $uri);

		}

		if ($local)
		{
			$strUri = (string) $uri;

			if ($baseUri == "/" && strlen($strUri) > 2 && $strUri[0] == "/" && $strUri[1] <> "/")
			{
				$uri = $baseUri . substr($strUri, 1);

			}

		}

		if ($args)
		{
			$queryString = http_build_query($args);

			if (typeof($queryString) == "string" && strlen($queryString))
			{
				if (strpos($uri, "?") !== false)
				{
					$uri .= "&" . $queryString;

				}

			}

		}

		return $uri;
	}

	public function getStatic($uri = null)
	{
		return $this->get($uri, null, null, $this->getStaticBaseUri());
	}

	public function path($path = null)
	{
		return $this->_basePath . $path;
	}


}