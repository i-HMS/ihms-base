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
 * SharedEventDispatcher class
 *
 * @package     iHMS_EventDispatcher
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class SharedEventDispatcher implements ISharedEventDispatcher
{
    /**
     * @var EventDispatcher[] Pairs of identifier/EventDispatcher
     */
    protected $identifiers = array();

    /**
     * Add the given event listener to the given event
     *
     * @throws \InvalidArgumentException in case $identifier is not a string nor an array of strings
     * @param string|array $identifier Identifier(s) for component(s) emitting the event
     * @param string $event Event name
     * @param callable $callback Listener callback
     * @param int $priority Priority
     * @return EventListener|EventListener[]
     */
    public function addEventListener($identifier, $event, $callback, $priority = 1)
    {
        if (!is_string($identifier) && !is_array($identifier)) {
            throw new  \InvalidArgumentException(
                sprintf('%s(): An identifier must be either a string or an array, received %s', __METHOD__, gettype($identifier))
            );
        }

        $identifiers = (array)$identifier;
        $eventListeners = array();

        foreach ($identifiers as $identifier) {
            if (!is_string($identifier)) {
                throw new  \InvalidArgumentException(
                    sprintf('%s(): Identifier must be a string, %s given', __METHOD__, gettype($identifier))
                );
            }

            if (!isset($this->identifiers[$identifier])) {
                $this->identifiers[$identifier] = new EventDispatcher();
            }

            $eventListeners[] = $this->identifiers[$identifier]->addEventListener($event, $callback, $priority);
        }

        return (count($eventListeners) > 1) ? $eventListeners : $eventListeners[0];
    }

    /**
     * Remove the given event listener from an event offered by the given resource
     *
     * @param string $identifier Identifier
     * @param EventListener $listener
     * @return bool TRUE if the given listener is found and successfuly removed, FALSE otherwise
     */
    public function removeEventListener($identifier, EventListener $listener)
    {
        if (isset($this->identifiers[$identifier])) {
            return $this->identifiers[$identifier]->removeEventListener($listener);
        }

        return false;
    }

    /**
     * Returns all event listeners for a given identifier and event
     *
     * @param string $identifier Identifier
     * @param string $event Event name
     * @return bool|EventListenerPriorityQueue FALSE if not listeners were found, ListenerPriorityQueue otherwise
     */
    public function getEventListeners($identifier, $event)
    {
        return (isset($this->identifiers[$identifier]))
            ? $this->identifiers[$identifier]->getListeners($event) : false;
    }

    /**
     * Returns all events for a given resource
     *
     * @param string $identifier Identifier
     * @return array
     */
    public function getEvents($identifier)
    {
        return isset($this->identifiers[$identifier]) ? $this->identifiers[$identifier]->getEvents() : array();
    }

    /**
     * Whether a listener is registered for the given identifier and event
     *
     * @param string $identifier Identifier
     * @param string|null $event Event name
     * @return bool
     */
    public function hasEventListener($identifier, $event)
    {
        return isset($this->identifiers[$identifier])
            ? $this->identifiers[$identifier]->hasEventListener($event) : false;
    }

    /**
     * Remove all event listeners for the given identifier, optionally for a specific event
     *
     * @param string $identifier Identifier
     * @param string|null $event Event name
     * @return bool
     */
    public function removeEventListeners($identifier, $event = null)
    {
        if (isset($this->identifiers[$identifier])) {
            if (!$event) {
                unset($this->identifiers[$identifier]);
                return true;
            }

            return $this->identifiers[$identifier]->removeEventListeners($event);
        }

        return false;
    }
}
