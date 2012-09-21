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
 * @package     iHMS_EventDispatcher
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\EventDispatcher;

use iHMS\EventDispatcher\Listener\EventListener;
use iHMS\EventDispatcher\Listener\EventListenerPriorityQueue;

/**
 * ISharedEventDispatcher interface
 *
 * @package     iHMS_EventDispatcher
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
interface ISharedEventDispatcher
{
    /**
     * Add the given event listener to the given event
     *
     * @abstract
     * @param string|array $identifier Identifier(s) for component(s) emitting the event
     * @param string $event Event name
     * @param callable $callback Listener callback
     * @param int $priority Priority
     * @return EventListener|EventListener[]
     */
    public function addEventListener($identifier, $event, $callback, $priority = 1);

    /**
     * Remove the given event listener from an event offered by the given resource
     *
     * @abstract
     * @param string $identifier Identifier
     * @param EventListener $listener
     * @return bool TRUE if the given listener is found and successfuly removed, FALSE otherwise
     */
    public function removeEventListener($identifier, EventListener $listener);

    /**
     * Returns all event listeners for a given identifier and event
     *
     * @abstract
     * @param string $identifier Identifier
     * @param string $event Event name
     * @return bool|EventListenerPriorityQueue FALSE if not listeners were found, EventListenerPriorityQueue otherwise
     */
    public function getEventListeners($identifier, $event);

    /**
     * Returns all events for a given resource
     *
     * @abstract
     * @param string $identifier Identifier
     * @return array
     */
    public function getEvents($identifier);

    /**
     * Whether a listener is registered for the given identifier and event
     *
     * @abstract
     * @param string $identifier Identifier
     * @param string|null $event Event name
     * @return bool
     */
    public function hasEventListener($identifier, $event);

    /**
     * Remove all event listeners for the given identifier, optionally for a specific event
     *
     * @abstract
     * @param string $identifier Identifier
     * @param string|null $event Event name
     * @return bool
     */
    public function removeEventListeners($identifier, $event = null);
}
