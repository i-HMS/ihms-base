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
 * @package     iHMS_ModuleHandler
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\ModuleHandler\Listener;

use iHMS\Config\Config;
use iHMS\EventDispatcher\IEventSubscriber;
use iHMS\EventDispatcher\IEventDispatcher;
use iHMS\Kernel\KernelEvent;
use iHMS\ModuleHandler\ABootstrapper as Bootstrapper;
use iHMS\ModuleHandler\Listener\ConfigListener;
use iHMS\ModuleHandler\Listener\AListener;
use iHMS\ModuleHandler\ModuleEvent;
use iHMS\Loader\AutoloaderFactory as Loader;

/**
 * ModuleListener class
 *
 * @package     iHMS_ModuleHandler
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class ModuleListener extends AListener implements IEventSubscriber
{
    /**
     * @var \iHMS\EventDispatcher\Listener\EventListener[]|IEventSubscriber[]
     */
    protected $listeners = array();

    /**
     * Constructor
     *
     * @param Config $options Module listener options
     */
    public function __construct($options)
    {
        $this->setOptions($options);
    }

    /**
     * Register listeners of this subscriber on the event dispatcher
     *
     * @param IEventDispatcher $eventDispatcher
     * @return mixed
     */
    public function subscribe(IEventDispatcher $eventDispatcher)
    {
        // High priority since module autoloading must be available before anything else
        // Will register all module namespaces onto the loader
        $this->listeners[] = $eventDispatcher->addEventListener(ModuleEvent::LOAD_MODULES_EVENT, array($this, 'autoloaderListener'), 10000);

        // Will try to resolve all modules by searching for a module bootstrapper
        $this->listeners[] = $eventDispatcher->addEventListener(ModuleEvent::RESOLVE_MODULE_EVENT, array($this, 'resolverListener'));

        // High priority since other loadModule listeners will assume the module's classes are available via autoloading
        $this->listeners[] = $eventDispatcher->addEventListener(ModuleEvent::LOAD_MODULE_EVENT, array($this, 'autoloaderConfigListener'), 10000);

        // Initialize module
        $this->listeners[] = $eventDispatcher->addEventListener(ModuleEvent::LOAD_MODULE_EVENT, array($this, 'initListener'));

        // Will  register the modules onBootstrap listener on the event dispatcher if found
        $this->listeners[] = $eventDispatcher->addEventListener(ModuleEvent::LOAD_MODULE_EVENT, array($this, 'onBootstrapListener'));

        // Register the config listener responsible for modules configuration
        $this->listeners = $eventDispatcher->addEventSubscriber(new ConfigListener($this->getOptions()));
    }

    /**
     * Unregister listeners of this subscriber from the event dispatcher
     *
     * @param IEventDispatcher $eventDispatcher
     * @return mixed
     */
    public function unsubscribe(IEventDispatcher $eventDispatcher)
    {
        foreach ($this->listeners as $listener) {
            if ($listener instanceof IEventSubscriber) {
                $listener->unsubscribe($eventDispatcher);
            } else {
                $eventDispatcher->removeEventListener($listener);
            }
        }

        $this->listeners = array();
    }

    /**
     * autoloaderListener - Make module namespaces availables
     *
     * @param ModuleEvent $event
     * @return void
     * @TODO add module specific autoloader
     */
    public function autoloaderListener(ModuleEvent $event)
    {
        /** @var $moduleHandler \iHMS\ModuleHandler\ModuleHandler */
        $moduleHandler = $event->getContext();
        $moduleList = $moduleHandler->getModules();
        $moduleDirectories = $this->getOption('module_directories', './module');

        Loader::factory();

        /** @var $loader \iHMS\Loader\UniversalLoader */
        $loader = Loader::getAutoloader(Loader::DEFAULT_LOADER);

        foreach ($moduleList as $moduleName) {
            $loader->add($moduleName, $moduleDirectories);
        }
    }

    /**
     * resolverListener - Resolve module bootstrapper and returns an instance of it
     *
     * @param ModuleEvent $event
     * @return Bootstrapper|bool Module bootstrapper instance or FALSE if not found
     */
    public function resolverListener(ModuleEvent $event)
    {
        $moduleName = $event->getModuleName();
        $bootstrapperClass = $moduleName . '\Bootstrapper';

        if (!class_exists($bootstrapperClass)) {
            return false;
        }

        return new $bootstrapperClass();
    }

    /**
     * autoloaderConfigListener - Setup module autoloader
     *
     * @param ModuleEvent $event Module event associated to the current module
     */
    public function autoloaderConfigListener(ModuleEvent $event)
    {
        $moduleBootstrapper = $event->getModuleBootstrapper();

        if (method_exists($moduleBootstrapper, 'getAutoloaderConfig')) {
            Loader::factory($moduleBootstrapper->getAutoloaderConfig());
        }
    }

    /**
     * initListener - Initialize module
     *
     * @param ModuleEvent $event
     * @return void
     */
    public function initListener(ModuleEvent $event)
    {
        $moduleBootstrapper = $event->getModuleBootstrapper();

        if (method_exists($moduleBootstrapper, 'init')) {
            /** @var $moduleHandler \iHMS\ModuleHandler\ModuleHandler */
            $moduleHandler = $event->getContext();
            $moduleBootstrapper->init($moduleHandler);
        }
    }

    /**
     * onBootstrapListener - Register module onBootstrap listener method if found
     *
     * @param ModuleEvent $event Module event associated to the current module
     * @return void
     */
    public function onBootstrapListener(ModuleEvent $event)
    {
        $moduleBootstrapper = $event->getModuleBootstrapper();

        if (method_exists($moduleBootstrapper, 'onBootstrap')) {
            /** @var $moduleHandler \iHMS\ModuleHandler\ModuleHandler */
            $moduleHandler = $event->getContext();
            $eventDispatcher = $moduleHandler->getEventDispatcher();
            $eventDispatcher->addEventListener(KernelEvent::BOOTSTRAP_EVENT, array($moduleBootstrapper, 'onBootstrap'));
        }
    }
}
