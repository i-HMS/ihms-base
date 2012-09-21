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
 * @package     iHMS_
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\EventDispatcher;

/**
 * ISharedEventDispatcherAware interface
 *
 * Interface allowing any object to be aware of the shared event dispatcher.
 *
 * @package     iHMS_
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
interface ISharedEventDispatcherAware
{
    /**
     * Set shared event dispatcher instance
     *
     * @abstract
     * @param ISharedEventDispatcher $sharedEventDispatcher
     * @return ISharedEventDispatcherAware
     */
    public function setSharedEventDispatcher(ISharedEventDispatcher $sharedEventDispatcher);

    /**
     * Returns shared event dispatcher instance
     *
     * @abstract
     * @return ISharedEventDispatcher
     */
    public function getSharedEventDispatcher();

    /**
     * Remove shared event dispatcher
     *
     * @abstract
     * @return ISharedEventDispatcher
     */
    public function removeSharedEventDispatcher();
}
