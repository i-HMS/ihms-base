<?php
/**
 * iHMS - internet Hosting Management system
 * Copyright (C) 2012 by iHMS Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iHMS
 * @package     iHMS_ServiceLocator
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\ServiceLocator;

/**
 * ServiceLocator class
 *
 * @package     iHMS_ServiceLocator
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class ServiceLocator implements IServiceLocator, IServiceInitializer
{
    /**
     * @var array Services
     */
    protected $services = array();

    /**
     * Holds metadata for service that are created via constructors
     *
     * @var array Holds pairs of serviceName/className
     */
    protected $constructors = array();

    /**
     * Holds data about service that are created via factories
     *
     * @var array Holds pairs of serviceName/factoryClass
     */
    protected $factories = array();

    /**
     * @var array Service aliases
     */
    protected $aliases = array();

    /**
     * @var bool Flag indicating whether services can be overriden
     */
    protected $allowOverride = false;

    /**
     * @var array Holds pairs of service/flags indicating whether service is shared
     */
    protected $shared = array();

    /**
     * @var bool Flag indicating whether services are shared by default
     */
    protected $sharedByDefault = true;

    /**
     * @var callable[] Stack of service initializers
     */
    protected $initializers = array();

    /**
     * Constructor
     *
     * Configure the locator with the given configurator
     *
     * @param IServiceLocatorConfigurator $configurator
     */
    public function __construct(IServiceLocatorConfigurator $configurator = null)
    {
        if ($configurator) {
            $configurator->configure($this);
        }
    }

    /**
     * Set flag indicating whether services can be overriden
     *
     * @param bool $flag Flag indicating whether services can be overriden
     * @return ServiceLocator
     */
    public function setAllowOverride($flag)
    {
        $this->allowOverride = (bool)$flag;

        return $this;
    }

    /**
     * Returns flag indicating whether services can be overiden
     *
     * @return bool
     */
    public function getAllowOverride()
    {
        return $this->allowOverride;
    }

    /**
     * Set value of flag indicating whether services are shared by default
     *
     * @param bool $flag Flag indicating whether services are shared by default
     * @return ServiceLocator
     */
    public function setSharedByDefault($flag)
    {
        $this->sharedByDefault = (bool)$flag;

        return $this;
    }

    /**
     * Set value of flag indicating whether services are shared by default
     *
     * @return bool
     */
    public function getSharedByDefault()
    {
        return $this->sharedByDefault;
    }

    /**
     * Set a service to create via constructor
     *
     * @throws \InvalidArgumentException in case $serviceName is already taken
     * @param string $name Service name
     * @param string $class Service class name
     * @return ServiceLocator
     */
    public function setConstructor($name, $class)
    {
        if ($this->has($name)) { // Ensure we have not already a service/alias by this name
            throw new \InvalidArgumentException(
                sprintf("%s(): A service by the name '%s' or alias already exists. Please, choose another name", __METHOD__)
            );
        }

        $this->constructors[$name] = $class;

        return $this;
    }

    /**
     * Set a service to create via factory
     *
     * @throws \InvalidArgumentException in case $serviceName is already taken
     * @param string $name Service name
     * @param string $class Factory class name
     * @return ServiceLocator
     */
    public function setFactory($name, $class)
    {
        if ($this->has($name)) { // Ensure we have not already a service/alias by this name
            throw new \InvalidArgumentException(
                sprintf("%s(): A service by the name '%s' or alias already exists. Please, choose another name", __METHOD__, $name)
            );
        }

        $this->factories[$name] = $class;

        return $this;
    }

    /**
     * Set flag indicating whether the given service is shared
     *
     * @throws \RuntimeException in case $name service doesn't exists
     * @param string $name Service name
     * @param bool $flag Flag indicating whether the given service is shared
     * @return ServiceLocator
     */
    public function setShared($name, $flag)
    {
        if (!isset($this->constructors[$name]) && !isset($this->factories[$name])) {
            throw new \RuntimeException(sprintf("%s(): A service by the name '%s' doesn't exists", __METHOD__, $name));
        }

        $this->shared[$name] = (bool)$flag;

        return $this;
    }

    /**
     * Set service
     *
     * @throws \RuntimeException in case services cannot be overriden and $serviceName already exists
     * @param string $name Service name
     * @param mixed $service service
     * @param bool $shared Flag indicating whether service is shared
     * @return ServiceLocator
     */
    public function setService($name, $service, $shared = true)
    {
        if (!$this->allowOverride && $this->has($name)) {
            throw new \RuntimeException(
                sprintf(
                    "%s(): A service by the name '%s' or alias already exists and cannot be overriden. Please, choose another name",
                    __METHOD__, $name
                )
            );
        }

        // If the service is being overwritten, destroy all previous aliases
        if (isset($this->services[$name])) {
            $this->removeAlias($name);
        }

        $this->services[$name] = $service;
        $this->shared[$name] = (bool)$shared;

        return $this;
    }

    /**
     * Returns an instance of the given service
     *
     * @throws \RuntimeException in case $name refers to an orphaned alias or $name service cannot be created
     * @param string $name Service name
     * @return mixed
     */
    public function get($name)
    {
        if ($this->hasAlias($name)) {
            do {
                $name = $this->aliases[$name];
            } while ($this->hasAlias($name));

            if (!$this->has($name)) {
                throw new \RuntimeException(
                    sprintf("%s(): The alias '%s' is orphaned; no service could be found", __METHOD__, $name)
                );
            }
        }

        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        $service = $this->_create($name);

        if (!$service && !is_array($service)) {
            throw new \RuntimeException(
                sprintf("%s(): Unable to create the service '%s': No such service is registered", __METHOD__, $name)
            );
        }

        if ($this->getSharedByDefault() && (!isset($this->shared[$name]) || $this->shared[$name])) {
            $this->services[$name] = $service;
        }

        return $service;
    }

    /**
     * Has the given service?
     *
     * @param string $nameOrAlias Service or alias name
     * @return bool TRUE if the given service is found, FALSE otherwise
     */
    public function has($nameOrAlias)
    {
        if (
            isset($this->constructors[$nameOrAlias]) || isset($this->factories[$nameOrAlias]) ||
            isset($this->aliases[$nameOrAlias]) || isset($this->services[$nameOrAlias])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Set service alias
     *
     * @throws \RuntimeException when $serviceAlias already exists and cannot be overriden
     * @param string $alias Service alias
     * @param string $nameOrAlias Service or alias name
     * @return ServiceLocator
     */
    public function setAlias($alias, $nameOrAlias)
    {
        if ($this->allowOverride == false && $this->has($alias)) {
            throw new \RuntimeException(
                sprintf("%s(): An alias by name '%s' already exists and cannot be overriden.", __METHOD__, $alias)
            );
        }

        $this->aliases[$alias] = $nameOrAlias;

        return $this;
    }

    /**
     * Has the given service alias?
     *
     * @param string $alias Service alias
     * @return bool TRUE if the given service alias exists, FALSE otherwise
     */
    public function hasAlias($alias)
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * Remove the given service alias
     *
     * @param string $nameOrAlias Service or alias name
     * @return ServiceLocator
     */
    public function removeAlias($nameOrAlias)
    {
        $aliasesToRemove = array();

        foreach ($this->aliases as $k => $v) {
            if ($k == $nameOrAlias || $v == $nameOrAlias || isset($aliasesToRemove[$v])) {
                $aliasesToRemove[$k] = 1;
            }
        }

        $this->aliases = array_diff_key($this->aliases, $aliasesToRemove);

        return $this;
    }

    /**
     * Add service initializer
     *
     * @throws \InvalidArgumentException in case $initializer is not callable
     * @param callable $initializer Initializer
     * @return ServiceLocator
     */
    public function addInitializer($initializer)
    {
        if (!is_callable($initializer)) {
            throw new \InvalidArgumentException(
                sprintf("%s(): expects callable, received %s", __METHOD__, gettype($initializer))
            );
        }

        $this->initializers[] = $initializer;

        return $this;
    }

    /**
     * Create the given service
     *
     * @throws \RuntimeException in case service/factory class is not found
     * @param string $name Service name
     * @return mixed
     */
    protected function _create($name)
    {
        if (isset($this->constructors[$name])) {
            $className = $this->constructors[$name];
            $type = 'constructor';
        } elseif (isset($this->factories[$name])) {
            $className = $this->factories[$name];
            $type = 'factory';
        } else {
            return false;
        }

        $service = false;

        if ($type == 'constructor') {
            if (class_exists($className)) {
                $service = new $className();
            }
        } elseif (($interfaces = @class_implements($className)) !== false) {
            if (in_array('iHMS\ServiceLocator\IServiceFactory', $interfaces)) {
                $service = $className::$type($this); // Factory class implementing the IFactory interface
            } else {
                $service = $className::$type(); // A service class providing its own factory
            }
        }

        if ($service === false) {
            throw new \RuntimeException(
                sprintf(
                    "%s(): Unable to create the service '%s'; class '%s' not found or doesn't return expected data",
                    __METHOD__, $name, $className
                )
            );
        }

        // Initialize the service
        foreach ($this->initializers as $initializer) {
            $initializer($service);
        }

        return $service;
    }
}
