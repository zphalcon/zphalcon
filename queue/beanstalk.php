<?php
namespace Phalcon\Queue;

use Phalcon\Queue\Beanstalk\Job;
use Phalcon\Queue\Beanstalk\Exception;

class Beanstalk
{
	const DEFAULT_DELAY = 0;
	const DEFAULT_PRIORITY = 100;
	const DEFAULT_TTR = 86400;
	const DEFAULT_TUBE = "default";
	const DEFAULT_HOST = "127.0.0.1";
	const DEFAULT_PORT = 11300;

	protected $_connection;
	protected $_parameters;

	public function __construct($parameters = [])
	{
		if (!(isset($parameters["host"])))
		{
			$parameters["host"] = self::DEFAULT_HOST;

		}

		if (!(isset($parameters["port"])))
		{
			$parameters["port"] = self::DEFAULT_PORT;

		}

		if (!(isset($parameters["persistent"])))
		{
			$parameters["persistent"] = false;

		}

		$this->_parameters = $parameters;

	}

	public function connect()
	{

		$connection = $this->_connection;

		if (typeof($connection) == "resource")
		{
			$this->disconnect();

		}

		$parameters = $this->_parameters;

		if ($parameters["persistent"])
		{
			$connection = pfsockopen($parameters["host"], $parameters["port"], null, null);

		}

		if (typeof($connection) <> "resource")
		{
			throw new Exception("Can't connect to Beanstalk server");
		}

		stream_set_timeout($connection, -1, null);

		$this->_connection = $connection;

		return $connection;
	}

	public function put($data, $options = null)
	{

		if (!(function() { if(isset($options["priority"])) {$priority = $options["priority"]; return $priority; } else { return false; } }()))
		{
			$priority = self::DEFAULT_PRIORITY;

		}

		if (!(function() { if(isset($options["delay"])) {$delay = $options["delay"]; return $delay; } else { return false; } }()))
		{
			$delay = self::DEFAULT_DELAY;

		}

		if (!(function() { if(isset($options["ttr"])) {$ttr = $options["ttr"]; return $ttr; } else { return false; } }()))
		{
			$ttr = self::DEFAULT_TTR;

		}

		$serialized = serialize($data);

		$length = strlen($serialized);

		$this->write("put " . $priority . " " . $delay . " " . $ttr . " " . $length . "\r\n" . $serialized);

		$response = $this->readStatus();

		$status = $response[0];

		if ($status <> "INSERTED" && $status <> "BURIED")
		{
			return false;
		}

		return (int) $response[1];
	}

	public function reserve($timeout = null)
	{

		if (typeof($timeout) <> "null")
		{
			$command = "reserve-with-timeout " . $timeout;

		}

		$this->write($command);

		$response = $this->readStatus();

		if ($response[0] <> "RESERVED")
		{
			return false;
		}

		return new Job($this, $response[1], unserialize($this->read($response[2])));
	}

	public function choose($tube)
	{

		$this->write("use " . $tube);

		$response = $this->readStatus();

		if ($response[0] <> "USING")
		{
			return false;
		}

		return $response[1];
	}

	public function watch($tube)
	{

		$this->write("watch " . $tube);

		$response = $this->readStatus();

		if ($response[0] <> "WATCHING")
		{
			return false;
		}

		return (int) $response[1];
	}

	public function ignore($tube)
	{

		$this->write("ignore " . $tube);

		$response = $this->readStatus();

		if ($response[0] <> "WATCHING")
		{
			return false;
		}

		return (int) $response[1];
	}

	public function pauseTube($tube, $delay)
	{

		$this->write("pause-tube " . $tube . " " . $delay);

		$response = $this->readStatus();

		if ($response[0] <> "PAUSED")
		{
			return false;
		}

		return true;
	}

	public function kick($bound)
	{

		$this->write("kick " . $bound);

		$response = $this->readStatus();

		if ($response[0] <> "KICKED")
		{
			return false;
		}

		return (int) $response[1];
	}

	public function stats()
	{

		$this->write("stats");

		$response = $this->readYaml();

		if ($response[0] <> "OK")
		{
			return false;
		}

		return $response[2];
	}

	public function statsTube($tube)
	{

		$this->write("stats-tube " . $tube);

		$response = $this->readYaml();

		if ($response[0] <> "OK")
		{
			return false;
		}

		return $response[2];
	}

	public function listTubes()
	{

		$this->write("list-tubes");

		$response = $this->readYaml();

		if ($response[0] <> "OK")
		{
			return false;
		}

		return $response[2];
	}

	public function listTubeUsed()
	{

		$this->write("list-tube-used");

		$response = $this->readStatus();

		if ($response[0] <> "USING")
		{
			return false;
		}

		return $response[1];
	}

	public function listTubesWatched()
	{

		$this->write("list-tubes-watched");

		$response = $this->readYaml();

		if ($response[0] <> "OK")
		{
			return false;
		}

		return $response[2];
	}

	public function peekReady()
	{

		$this->write("peek-ready");

		$response = $this->readStatus();

		if ($response[0] <> "FOUND")
		{
			return false;
		}

		return new Job($this, $response[1], unserialize($this->read($response[2])));
	}

	public function peekBuried()
	{

		$this->write("peek-buried");

		$response = $this->readStatus();

		if ($response[0] <> "FOUND")
		{
			return false;
		}

		return new Job($this, $response[1], unserialize($this->read($response[2])));
	}

	public function peekDelayed()
	{

		if (!($this->write("peek-delayed")))
		{
			return false;
		}

		$response = $this->readStatus();

		if ($response[0] <> "FOUND")
		{
			return false;
		}

		return new Job($this, $response[1], unserialize($this->read($response[2])));
	}

	public function jobPeek($id)
	{

		$this->write("peek " . $id);

		$response = $this->readStatus();

		if ($response[0] <> "FOUND")
		{
			return false;
		}

		return new Job($this, $response[1], unserialize($this->read($response[2])));
	}

	final public function readStatus()
	{

		$status = $this->read();

		if ($status === false)
		{
			return [];
		}

		return explode(" ", $status);
	}

	final public function readYaml()
	{

		$response = $this->readStatus();

		$status = $response[0];

		if (count($response) > 1)
		{
			$numberOfBytes = $response[1];

			$response = $this->read();

			$data = yaml_parse($response);

		}

		return [$status, $numberOfBytes, $data];
	}

	public function read($length = 0)
	{

		$connection = $this->_connection;

		if (typeof($connection) <> "resource")
		{
			$connection = $this->connect();

			if (typeof($connection) <> "resource")
			{
				return false;
			}

		}

		if ($length)
		{
			if (feof($connection))
			{
				return false;
			}

			$data = rtrim(stream_get_line($connection, $length + 2), "\r\n");

			if (stream_get_meta_data($connection)["timed_out"])
			{
				throw new Exception("Connection timed out");
			}

		}

		if ($data === "UNKNOWN_COMMAND")
		{
			throw new Exception("UNKNOWN_COMMAND");
		}

		if ($data === "JOB_TOO_BIG")
		{
			throw new Exception("JOB_TOO_BIG");
		}

		if ($data === "BAD_FORMAT")
		{
			throw new Exception("BAD_FORMAT");
		}

		if ($data === "OUT_OF_MEMORY")
		{
			throw new Exception("OUT_OF_MEMORY");
		}

		return $data;
	}

	public function write($data)
	{

		$connection = $this->_connection;

		if (typeof($connection) <> "resource")
		{
			$connection = $this->connect();

			if (typeof($connection) <> "resource")
			{
				return false;
			}

		}

		$packet = $data . "\r\n";

		return fwrite($connection, $packet, strlen($packet));
	}

	public function disconnect()
	{

		$connection = $this->_connection;

		if (typeof($connection) <> "resource")
		{
			return false;
		}

		fclose($connection);

		$this->_connection = null;

		return true;
	}

	public function quit()
	{
		$this->write("quit");

		$this->disconnect();

		return typeof($this->_connection) <> "resource";
	}


}