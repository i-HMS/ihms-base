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
use iHMS\EventDispatcher\Listener\EventListenerResults;

/**
 * EventDispatcher interface
 *
 * The IEventDispatcher interface defines methods for adding or removing event listeners, checks whether specific types
 * of event listeners are registered, and dispatches events.
 *
 * @package     iHMS_EventDispatcher
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
interface IEventDispatcher extends ISharedEventDispatcherAware
{
    /**
     * Register the given event listener on the EventDispatcher
     *
     * @abstract
     * @param string|array $event An event name or an array of event names
     * @param callable $eventListenerCallback Listener callback
     * @param int $priority Priority at which the event should be executed
     * @return EventListener|EventListener[]
     */
    public function addEventListener($event, $eventListenerCallback, $priority = 1);

    /**
     * Remove an event listener from the EventDispatcher
     *
     * @abstract
     * @param EventListener $eventListener
     * @return bool TRUE if the given listener is found and successfuly removed, FALSE otherwise
     */
    public function removeEventListener(EventListener $eventListener);

    /**
     * Register the given event subscriber on the EventDispatcher
     *
     * @abstract
     * @param IEventSubscriber $eventSubscriber
     * @return mixed
     */
    public function addEventSubscriber(IEventSubscriber $eventSubscriber);

    /**
     * Unregister the given event subscriber from the EventDispatcher
     *
     * @param IEventSubscriber $eventSubscriber
     * @return mixed
     */
    public function removeEventSubscriber(IEventSubscriber $eventSubscriber);

    /**
     * Dispatch the given event in the events flow
     *
     * The following scenarios are handled
     *
     * - Passing Event object only
     * - Passing event name and event object
     * - Passing event name, context and event object
     * - Passing event name, context and parameters
     *
     * Optionnally, you can pass in a condition callback that will stop the event propagation when returning TRUE.
     *
     * @throws \InvalidArgumentException when $conditionCallback is invalid
     * @param string|IEventDescription $event Event name or event object
     * @param string|object|IEventDescription|callable $context Context or event object or condition callback
     * @param array|\ArrayAccess|IEventDescription|callable $eventParameters Event parameters or event object or condition callback
     * @param callable $conditionCallback Condition callback
     * @return EventListenerResults
     */
    public function dispatchEvent($event, $context = null, $eventParameters = array(), $conditionCallback = null);

    /**
     * Check if listeners are registered for the given event
     *
     * @abstract
     * @param string $event Event name
     * @return bool
     */
    public function hasEventListener($event);

    /**
     * Set the identifiers
     *
     * @abstract
     * @param string|array $identifiers Identifiers
     * @return IEventDispatcher
     */
    public function setIdentifiers($identifiers);

    /**
     * Returns list of identifiers
     *
     * @abstract
     * @return array List of identifiers
     */
    public function getIdentifiers();

    /**
     * Add given identifier(s)
     *
     * @abstract
     * @param string|array $identifiers Identifier(s)
     * @return IEventDispatcher
     */
    public function addIdentifiers($identifiers);
}
