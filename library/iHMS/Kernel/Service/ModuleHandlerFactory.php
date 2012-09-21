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

namespace iHMS\Kernel\Service;

use iHMS\ServiceLocator\IServiceFactory;
use iHMS\ServiceLocator\IServiceLocator;
use iHMS\ModuleHandler\ModuleEvent;
use iHMS\ModuleHandler\ModuleHandler;
use iHMS\ModuleHandler\Listener\ModuleListener;
use iHMS\Config\Config;

/**
 * ModuleHandlerFactory class
 *
 * @package     iHMS_ModuleHandler
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class ModuleHandlerFactory implements IServiceFactory
{
    /**
     * Create and returns module handler service instance
     *
     * Instanntiate the module listeners, providing the configuration from the "module_listener_options" key of
     * the Config service.
     *
     * Module handler is instantiaded and provided with it own EventDispatcher service instance, to which the
     * ModuleListener subscriber is added. The ModuleEvent is also created and added to the module handler.
     *
     * @static
     * @param IServiceLocator $serviceLocator
     * @return mixed
     */
    public static function factory(IServiceLocator $serviceLocator)
    {
        /** @var $config \iHMS\Config\Config */
        $config = $serviceLocator->get('Config');
        $moduleListener = new ModuleListener($config->get('module_listener_options') ? : new Config);

        /** @var $eventDispatcher \iHMS\EventDispatcher\IEventDispatcher */
        $eventDispatcher = $serviceLocator->get('EventDispatcher');
        $eventDispatcher->addEventSubscriber($moduleListener);

        $moduleEvent = new ModuleEvent();
        $moduleEvent->setParam('ServiceLocator', $serviceLocator);

        $moduleHandler = new ModuleHandler($config->get('modules'));
        // TODO pass this to the ModudleHandler constructor?
        $moduleHandler->setEventDispatcher($eventDispatcher);
        $moduleHandler->setEvent($moduleEvent);

        return $moduleHandler;
    }
}
