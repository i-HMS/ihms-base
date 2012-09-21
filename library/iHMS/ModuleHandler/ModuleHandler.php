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

namespace iHMS\ModuleHandler;

use iHMS\EventDispatcher\IEventDescription;
use iHMS\EventDispatcher\IEventDispatcher;
use iHMS\EventDispatcher\IEventSubscriber;
use iHMS\EventDispatcher\Listener\EventListener;
use iHMS\EventDispatcher\EventDispatcher;
use iHMS\Config\Config;

/**
 * ModuleHandler class
 *
 * @package     iHMS_ModuleHandler
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class ModuleHandler
{
    /**
     * @var array List of modules to load
     */
    protected $moduleNames = array();

    /**
     * @var IEventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var ModuleEvent
     */
    protected $event;

    /**
     * @var array Array that olds instances of loaded modules
     */
    protected $loadedModules = array();

    /**
     * @var bool Flag indicating whether $_moduleNames are loaded
     */
    protected $areModulesLoaded = false;

    /**
     * Constructor
     *
     * @param array|Config $moduleNames Module list
     */
    public function __construct($moduleNames)
    {
        $this->setModules($moduleNames);
    }

    /**
     * __invoke proxy to loadModules for easier attaching
     *
     * @param  IEventDescription $event
     * @return ModuleHandler
     */
    public function __invoke(IEventDescription $event)
    {
        return $this->loadModules($event);
    }

    /**
     * onLoadModules listener
     *
     * @return ModuleHandler
     */
    public function onLoadModules()
    {
        if (!$this->areModulesLoaded) {
            foreach ($this->getModules() as $moduleName) {
                $this->loadModule($moduleName);
            }

            $this->areModulesLoaded = true;
        }

        return $this;
    }

    /**
     * Set list of module names to load
     *
     * @throws \InvalidArgumentException in case $moduleName is not an array
     * @param Array|Config $moduleNames Module names
     * @return ModuleHandler
     */
    public function setModules($moduleNames = null)
    {
        if (null !== $moduleNames) {
            if (is_array($moduleNames)) {
                $this->moduleNames = $moduleNames;
            } elseif ($moduleNames instanceof Config) {
                $this->moduleNames = $moduleNames->toArray();
            } else {
                throw new \InvalidArgumentException(
                    sprintf('%s(): expects an array or Config object, received %s', __METHOD__, gettype($moduleNames))
                );
            }
        }

        return $this;
    }

    /**
     * Returns list of module names to load
     *
     * @return array
     */
    public function getModules()
    {
        return $this->moduleNames;
    }

    /**
     * Load modules
     *
     * @trigger beforeLoadModules(ModuleEvent)
     * @trigger loadModules(ModuleEvent)
     * @trigger afterLoadModules(ModuleEvent)
     * @return ModuleHandler
     */
    public function loadModules()
    {
        if (!$this->areModulesLoaded) {
            $eventDispatcher = $this->getEventDispatcher();
            $eventDispatcher->dispatchEvent(ModuleEvent::BEFORE_LOAD_MODULES_EVENT, $this, $this->getEvent());
            $eventDispatcher->dispatchEvent(ModuleEvent::LOAD_MODULES_EVENT, $this, $this->getEvent());
            $eventDispatcher->dispatchEvent(ModuleEvent::AFTER_LOAD_MODULES_EVENT, $this, $this->getEvent());
        }

        return $this;
    }

    /**
     * Load the given module
     *
     * @trigger resolveModule(ModuleEvent)
     * @trigger beforeLoadModule(ModuleEvent)
     * @trigger loadModuleEvent(ModuleEvent)
     * @trigger afterLoadModule(ModuleEvent)
     * @throws \RuntimeException in case module bootstrapper is not found or doesn't extends the AModuleBootstrapper class
     * @param string $moduleName Module name
     * @return ABootstrapper
     */
    public function loadModule($moduleName)
    {
        if (!isset($this->loadedModules[$moduleName])) {
            $event = clone $this->getEvent();
            $event->setModuleName($moduleName);
            $result = $this->getEventDispatcher()->dispatchEvent(
                ModuleEvent::RESOLVE_MODULE_EVENT, $this, $event, function ($r) {
                    return ($r instanceof ABootstrapper);
                }
            );

            if (!($moduleBootstrapper = $result->getLastResult())) {
                throw new \RuntimeException(
                    sprintf(
                        "%s(): The Bootstrapper class for the %s module was not found or doesn't extends the iHMS\ModuleHandler\ABootstrapper class",
                        __METHOD__, $moduleName
                    )
                );
            }

            $event->setModuleBootstrapper($moduleBootstrapper);

            // Trigger beforeLoadModule event
            $this->getEventDispatcher()->dispatchEvent(ModuleEvent::BEFORE_LOAD_MODULE_EVENT, $this, $event);

            // Trigger loadModule event
            $this->getEventDispatcher()->dispatchEvent(ModuleEvent::LOAD_MODULE_EVENT, $this, $event);

            // Trigger AfterLoadModule event
            $this->getEventDispatcher()->dispatchEvent(ModuleEvent::AFTER_LOAD_MODULE_EVENT, $this, $event);

            $this->loadedModules[$moduleName] = $moduleBootstrapper;
        }

        return $this->loadedModules[$moduleName];
    }

    /**
     * Set module event
     *
     * @param ModuleEvent $event
     */
    public function setEvent(ModuleEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Returns module event
     *
     * @return ModuleEvent
     */
    public function getEvent()
    {
        if (!$this->event instanceof ModuleEvent) {
            $this->setEvent(new ModuleEvent);
        }

        return $this->event;
    }

    /**
     * Set Event dispatcher instance
     *
     * @param IEventDispatcher $eventDispatcher
     * @return ModuleHandler
     */
    public function setEventDispatcher(IEventDispatcher $eventDispatcher)
    {
        $eventDispatcher->setIdentifiers(__CLASS__);
        $eventDispatcher->addEventListener(ModuleEvent::LOAD_MODULES_EVENT, array($this, 'onLoadModules'));
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * Returns event dispatcher instance
     *
     * @return IEventDispatcher
     */
    public function getEventDispatcher()
    {
        if (null === $this->eventDispatcher) {
            $this->setEventDispatcher(new EventDispatcher());
        }

        return $this->eventDispatcher;
    }
}
