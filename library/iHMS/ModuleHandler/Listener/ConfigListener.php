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

use iHMS\EventDispatcher\IEventSubscriber;
use iHMS\EventDispatcher\IEventDispatcher;
use iHMS\Config\Config;

/**
 * ConfigListener class
 *
 * @package     iHMS_ModuleHandler
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class ConfigListener extends AListener implements IEventSubscriber
{
    /**
     * @var Config
     */
    public $config;

    /**
     * Implements IEventSubscriber interface - Register listeners of this subscriber on the event dispatcher
     *
     * @param IEventDispatcher $eventDispatcher
     * @return mixed
     */
    public function subscribe(IEventDispatcher $eventDispatcher)
    {
        // TODO: Implement subscribe() method.
    }

    /**
     * Implements IEventSubscriber interface - Unregister listeners of this subscriber from the event dispatcher
     *
     * @param IEventDispatcher $eventDispatcher
     * @return mixed
     */
    public function unsubscribe(IEventDispatcher $eventDispatcher)
    {
        // TODO: Implement unsubscribe() method.
    }

    public function getConfig()
    {
        return $this->config;
    }

}
