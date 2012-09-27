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
 * @package     iHMS_Kernel
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\Kernel\Service;

use iHMS\ServiceLocator\IServiceLocatorConfigurator;
use iHMS\ServiceLocator\IServiceLocator;
use iHMS\EventDispatcher\IEventDispatcherAware;
use iHMS\ServiceLocator\IServiceLocatorAware;

/**
 * ServiceLocatorConfigurator class
 *
 * @package     iHMS_Kernel
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class ServiceLocatorConfigurator implements IServiceLocatorConfigurator
{
    /**
     * @var array Services created via constructors
     */
    protected $constructors = array(
        'SharedEventDispatcher' => 'iHMS\EventDispatcher\SharedEventDispatcher'
    );

    /**
     * @var array Services created via factories
     */
    protected $factories = array(
        'EventDispatcher' => 'iHMS\Kernel\Service\EventDispatcherFactory',
        'Kernel' => 'iHMS\Kernel\Service\KernelFactory',
        'ModuleHandler' => 'iHMS\Kernel\Service\ModuleHandlerFactory',
        'Request' => 'iHMS\Kernel\Service\RequestFactory',
        'Response' => 'iHMS\Kernel\Service\ResponseFactory',
        'Router' => 'iHMS\Kernel\Service\RouterFactory',
    );

    /**
     * @var array Service aliases
     */
    protected $aliases = array(
        'iHMS\EventDispatcher\IEventDispatcher' => 'EventDispatcher',
        'iHMS\Request\IRequest' => 'Request',
        'iHMS\Request\IResponse' => 'Response',
    );

    /**
     * @var array Pairs of serviceName/Flags indicating whether service is shared; Services are shared by default
     */
    protected $shared = array(
        'EventDispatcher' => false // The EventDispatcher service is not shared
    );

    /**
     * Constructor
     *
     * @throws \InvalidArgumentException in case $config is not a Config object nor an array
     * @param \iHMS\Config\Config|array $config Configuration
     */
    public function __construct($config)
    {
        if ($config instanceof \iHMS\Config\Config) {
            $config->toArray();
        } elseif (!is_array($config)) {
            throw new \InvalidArgumentException(
                sprintf('%s(): Expects a config object or an array; received %s', __METHOD__, gettype($config))
            );
        }

        if (isset($config['constructors'])) {
            $this->invokables = array_merge($this->invokables, $config['invokables']);
        }

        if (isset($config['factories'])) {
            $this->factories = array_merge($this->factories, $config['factories']);
        }

        if (isset($config['aliases'])) {
            $this->aliases = array_merge($this->aliases, $config['aliases']);
        }

        if (isset($config['shared'])) {
            $this->shared = array_merge($this->shared, $config['shared']);
        }
    }

    /**
     * Configure the given service locator
     *
     * @param IServiceLocator $serviceLocator
     * @return void
     */
    public function configure(IServiceLocator $serviceLocator)
    {
        /** @var $serviceLocator \iHMS\ServiceLocator\ServiceLocator */

        // Set services that are created via constructors
        foreach ($this->constructors as $serviceName => $className) {
            $serviceLocator->setConstructor($serviceName, $className);
        }

        // Set services that are created via factories
        foreach ($this->factories as $serviceName => $className) {
            $serviceLocator->setFactory($serviceName, $className);
        }

        // Set service aliases
        foreach ($this->aliases as $aliasName => $serviceName) {
            $serviceLocator->setAlias($aliasName, $serviceName);
        }

        foreach ($this->shared as $serviceName => $flag) {
            $serviceLocator->setShared($serviceName, $flag);
        }

        // Add initializer for event dispatcher aware objects
        $serviceLocator->addInitializer(
            function($service) use($serviceLocator)
            {
                /** @var $service \iHMS\EventDispatcher\IEventDispatcherAware */
                if ($service instanceof IEventDispatcherAware) {

                    /** $@var  $serviceLocator IServiceLocator */
                    $service->setEventDispatcher($serviceLocator->get('EventDispatcher'));
                }
            }
        );

        // Add initializer for service locator aware objects
        $serviceLocator->addInitializer(
            function($service) use($serviceLocator)
            {
                if ($service instanceof IServiceLocatorAware) {
                    /** @var $service \iHMS\ServiceLocator\IServiceLocatorAware */
                    $service->setServiceLocator($serviceLocator);
                }
            }
        );

        $serviceLocator->setService('ServiceLocator', $serviceLocator);
        $serviceLocator->setAlias('iHMS\ServiceLocator\IServiceLocator', 'ServiceLocator');
        $serviceLocator->setAlias('iHMS\ServiceLocator\ServiceLocator', 'ServiceLocator');
    }
}
