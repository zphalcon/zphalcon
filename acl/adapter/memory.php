<?php
namespace Phalcon\Acl\Adapter;

use Phalcon\Acl;
use Phalcon\Acl\Adapter;
use Phalcon\Acl\Role;
use Phalcon\Acl\RoleInterface;
use Phalcon\Acl\Resource;
use Phalcon\Acl\Exception;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Acl\RoleAware;
use Phalcon\Acl\ResourceAware;
use Phalcon\Acl\RoleInterface;
use Phalcon\Acl\ResourceInterface;

class Memory extends Adapter
{
	protected $_rolesNames;
	protected $_roles;
	protected $_resourcesNames;
	protected $_resources;
	protected $_access;
	protected $_roleInherits;
	protected $_accessList;
	protected $_func;
	protected $_noArgumentsDefaultAction = Acl::ALLOW;

	public function __construct()
	{
		$this->_resourcesNames = ["*" => true];

		$this->_accessList = ["*!*" => true];

	}

	public function addRole($role, $accessInherits = null)
	{

		if (typeof($role) == "object" && $role instanceof $RoleInterface)
		{
			$roleName = $role->getName();

			$roleObject = $role;

		}

		if (isset($this->_rolesNames[$roleName]))
		{
			return false;
		}

		$this->_roles[] = $roleObject;

		$this[$roleName] = true;

		if ($accessInherits <> null)
		{
			return $this->addInherit($roleName, $accessInherits);
		}

		return true;
	}

	public function addInherit($roleName, $roleToInherit)
	{

		$rolesNames = $this->_rolesNames;

		if (!(isset($rolesNames[$roleName])))
		{
			throw new Exception("Role '" . $roleName . "' does not exist in the role list");
		}

		if (typeof($roleToInherit) == "object" && $roleToInherit instanceof $RoleInterface)
		{
			$roleInheritName = $roleToInherit->getName();

		}

		if (isset($this->_roleInherits[$roleInheritName]))
		{
			foreach ($this->_roleInherits[$roleInheritName] as $deepInheritName) {
				$this->addInherit($roleName, $deepInheritName);
			}

		}

		if (!(isset($rolesNames[$roleInheritName])))
		{
			throw new Exception("Role '" . $roleInheritName . "' (to inherit) does not exist in the role list");
		}

		if ($roleName == $roleInheritName)
		{
			return false;
		}

		if (!(isset($this->_roleInherits[$roleName])))
		{
			$this[$roleName] = true;

		}

		$this->_roleInherits[$roleName] = $roleInheritName;

		return true;
	}

	public function isRole($roleName)
	{
		return isset($this->_rolesNames[$roleName]);
	}

	public function isResource($resourceName)
	{
		return isset($this->_resourcesNames[$resourceName]);
	}

	public function addResource($resourceValue, $accessList)
	{

		if (typeof($resourceValue) == "object" && $resourceValue instanceof $ResourceInterface)
		{
			$resourceName = $resourceValue->getName();

			$resourceObject = $resourceValue;

		}

		if (!(isset($this->_resourcesNames[$resourceName])))
		{
			$this->_resources[] = $resourceObject;

			$this[$resourceName] = true;

		}

		return $this->addResourceAccess($resourceName, $accessList);
	}

	public function addResourceAccess($resourceName, $accessList)
	{

		if (!(isset($this->_resourcesNames[$resourceName])))
		{
			throw new Exception("Resource '" . $resourceName . "' does not exist in ACL");
		}

		if (typeof($accessList) <> "array" && typeof($accessList) <> "string")
		{
			throw new Exception("Invalid value for accessList");
		}

		$exists = true;

		if (typeof($accessList) == "array")
		{
			foreach ($accessList as $accessName) {
				$accessKey = $resourceName . "!" . $accessName;
				if (!(isset($this->_accessList[$accessKey])))
				{
					$this[$accessKey] = $exists;

				}
			}

		}

		return true;
	}

	public function dropResourceAccess($resourceName, $accessList)
	{

		if (typeof($accessList) == "array")
		{
			foreach ($accessList as $accessName) {
				$accessKey = $resourceName . "!" . $accessName;
				if (isset($this->_accessList[$accessKey]))
				{
					unset($this->_accessList[$accessKey]);

				}
			}

		}

	}

	protected function _allowOrDeny($roleName, $resourceName, $access, $action, $func = null)
	{

		if (!(isset($this->_rolesNames[$roleName])))
		{
			throw new Exception("Role '" . $roleName . "' does not exist in ACL");
		}

		if (!(isset($this->_resourcesNames[$resourceName])))
		{
			throw new Exception("Resource '" . $resourceName . "' does not exist in ACL");
		}

		$accessList = $this->_accessList;

		if (typeof($access) == "array")
		{
			foreach ($access as $accessName) {
				$accessKey = $resourceName . "!" . $accessName;
				if (!(isset($accessList[$accessKey])))
				{
					throw new Exception("Access '" . $accessName . "' does not exist in resource '" . $resourceName . "'");
				}
			}

			foreach ($access as $accessName) {
				$accessKey = $roleName . "!" . $resourceName . "!" . $accessName;
				$this[$accessKey] = $action;
				if ($func <> null)
				{
					$this[$accessKey] = $func;

				}
			}

		}

	}

	public function allow($roleName, $resourceName, $access, $func = null)
	{

		if ($roleName <> "*")
		{
			return $this->_allowOrDeny($roleName, $resourceName, $access, Acl::ALLOW, $func);
		}

	}

	public function deny($roleName, $resourceName, $access, $func = null)
	{

		if ($roleName <> "*")
		{
			return $this->_allowordeny($roleName, $resourceName, $access, Acl::DENY, $func);
		}

	}

	public function isAllowed($roleName, $resourceName, $access, $parameters = null)
	{

		if (typeof($roleName) == "object")
		{
			if ($roleName instanceof $RoleAware)
			{
				$roleObject = $roleName;

				$roleName = $roleObject->getRoleName();

			}

		}

		if (typeof($resourceName) == "object")
		{
			if ($resourceName instanceof $ResourceAware)
			{
				$resourceObject = $resourceName;

				$resourceName = $resourceObject->getResourceName();

			}

		}

		$this->_activeRole = $roleName;

		$this->_activeResource = $resourceName;

		$this->_activeAccess = $access;

		$accessList = $this->_access;

		$eventsManager = $this->_eventsManager;

		$funcList = $this->_func;

		if (typeof($eventsManager) == "object")
		{
			if ($eventsManager->fire("acl:beforeCheckAccess", $this) === false)
			{
				return false;
			}

		}

		$rolesNames = $this->_rolesNames;

		if (!(isset($rolesNames[$roleName])))
		{
			return $this->_defaultAccess == Acl::ALLOW;
		}

		$accessKey = $roleName . "!" . $resourceName . "!" . $access;

		if (isset($accessList[$accessKey]))
		{
			$haveAccess = $accessList[$accessKey];

		}

		$funcAccess = $funcList[$accessKey]
		if ($haveAccess == null)
		{
			$roleInherits = $this->_roleInherits;

			if (function() { if(isset($roleInherits[$roleName])) {$inheritedRoles = $roleInherits[$roleName]; return $inheritedRoles; } else { return false; } }())
			{
				if (typeof($inheritedRoles) == "array")
				{
					foreach ($inheritedRoles as $inheritedRole) {
						$accessKey = $inheritedRole . "!" . $resourceName . "!" . $access;
						if (isset($accessList[$accessKey]))
						{
							$haveAccess = $accessList[$accessKey];

						}
						$funcAccess = $funcList[$accessKey]					}

				}

			}

		}

		if ($haveAccess == null)
		{
			$accessKey = $roleName . "!" . $resourceName . "!*";

			if (isset($accessList[$accessKey]))
			{
				$haveAccess = $accessList[$accessKey];

				$funcAccess = $funcList[$accessKey]
			}

		}

		if ($haveAccess == null)
		{
			$accessKey = $roleName . "!*!*";

			if (isset($accessList[$accessKey]))
			{
				$haveAccess = $accessList[$accessKey];

				$funcAccess = $funcList[$accessKey]
			}

		}

		$this->_accessGranted = $haveAccess;

		if (typeof($eventsManager) == "object")
		{
			$eventsManager->fire("acl:afterCheckAccess", $this);

		}

		if ($haveAccess == null)
		{
			return $this->_defaultAccess == Acl::ALLOW;
		}

		if ($funcAccess !== null)
		{
			$reflectionFunction = new \ReflectionFunction($funcAccess);

			$reflectionParameters = $reflectionFunction->getParameters();

			$parameterNumber = count($reflectionParameters);

			if ($parameterNumber === 0)
			{
				return $haveAccess == Acl::ALLOW && call_user_func($funcAccess);
			}

			$parametersForFunction = [];

			$numberOfRequiredParameters = $reflectionFunction->getNumberOfRequiredParameters();

			$userParametersSizeShouldBe = $parameterNumber;

			foreach ($reflectionParameters as $reflectionParameter) {
				$reflectionClass = $reflectionParameter->getClass();
				$parameterToCheck = $reflectionParameter->getName();
				if ($reflectionClass !== null)
				{
					if ($roleObject !== null && $reflectionClass->isInstance($roleObject) && !($hasRole))
					{
						$hasRole = true;

						$parametersForFunction = $roleObject;

						$userParametersSizeShouldBe--;

						continue;

					}

					if ($resourceObject !== null && $reflectionClass->isInstance($resourceObject) && !($hasResource))
					{
						$hasResource = true;

						$parametersForFunction = $resourceObject;

						$userParametersSizeShouldBe--;

						continue;

					}

					if (isset($parameters[$parameterToCheck]) && typeof($parameters[$parameterToCheck]) == "object" && !($reflectionClass->isInstance($parameters[$parameterToCheck])))
					{
						throw new Exception("Your passed parameter doesn't have the same class as the parameter in defined function when check " . $roleName . " can " . $access . " " . $resourceName . ". Class passed: " . get_class($parameters[$parameterToCheck]) . " , Class in defined function: " . $reflectionClass->getName() . ".");
					}

				}
				if (isset($parameters[$parameterToCheck]))
				{
					$parametersForFunction = $parameters[$parameterToCheck];

				}
			}

			if (count($parameters) > $userParametersSizeShouldBe)
			{
				trigger_error("Number of parameters in array is higher than the number of parameters in defined function when check " . $roleName . " can " . $access . " " . $resourceName . ". Remember that more parameters than defined in function will be ignored.", E_USER_WARNING);

			}

			if (count($parametersForFunction) == 0)
			{
				if ($numberOfRequiredParameters > 0)
				{
					trigger_error("You didn't provide any parameters when check " . $roleName . " can " . $access . " " . $resourceName . ". We will use default action when no arguments.");

					return $haveAccess == Acl::ALLOW && $this->_noArgumentsDefaultAction == Acl::ALLOW;
				}

				return $haveAccess == Acl::ALLOW && call_user_func($funcAccess);
			}

			if (count($parametersForFunction) >= $numberOfRequiredParameters)
			{
				return $haveAccess == Acl::ALLOW && call_user_func_array($funcAccess, $parametersForFunction);
			}

			throw new Exception("You didn't provide all necessary parameters for defined function when check " . $roleName . " can " . $access . " " . $resourceName);
		}

		return $haveAccess == Acl::ALLOW;
	}

	public function setNoArgumentsDefaultAction($defaultAccess)
	{
		$this->_noArgumentsDefaultAction = $defaultAccess;

	}

	public function getNoArgumentsDefaultAction()
	{
		return $this->_noArgumentsDefaultAction;
	}

	public function getRoles()
	{
		return $this->_roles;
	}

	public function getResources()
	{
		return $this->_resources;
	}


}