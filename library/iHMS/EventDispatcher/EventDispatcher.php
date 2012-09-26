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
use iHMS\EventDispatcher\Listener\EventListenerPriorityQueue;
use iHMS\EventDispatcher\IEventSubscriber;

/**
 * EventDispatcher class
 *
 * @package     iHMS_EventDispatcher
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class EventDispatcher implements IEventDispatcher
{
    /**
     * @var EventListenerPriorityQueue[] Pairs of event/ListenerPriorityQueue
     */
    protected $events = array();

    /**
     * @var array Identifiers
     */
    protected $identifiers = array();

    /**
     * @var ISharedEventDispatcher
     */
    protected $sharedEventDispatcher = null;

    /**
     * Constructor
     *
     * @param null $identtifiers
     */
    public function __construct($identtifiers = null)
    {
        $this->setIdentifiers($identtifiers);
    }

    /**
     * Register the given event listener on the EventDispatcher
     *
     * @param string|array $event An event name or an array of event names
     * @param callable $eventListenerCallback Listener callback
     * @param int $priority Priority at which the event should be executed
     * @return EventListener|EventListener[]
     */
    public function addEventListener($event, $eventListenerCallback, $priority = 1)
    {
        if (is_array($event)) {
            $eventListeners = array();

            foreach ($event as $e) {
                $eventListeners[] = $this->addEventListener($e, $eventListenerCallback, $priority);
            }

            return $eventListeners;
        }

        if (!isset($this->events[$event])) {
            $this->events[$event] = new EventListenerPriorityQueue();
        }

        $eventListener = new EventListener($eventListenerCallback, $event, $priority);

        $this->events[$event]->addEventListener($eventListener, $priority);

        return $eventListener;
    }

    /**
     * Remove an event listener from the EventDispatcher
     *
     * @param EventListener $eventListener
     * @return bool TRUE if the given listener is found and successfuly removed, FALSE otherwise
     */
    public function removeEventListener(EventListener $eventListener)
    {
        $event = $eventListener->getEventName();

        if (!isset($this->events[$event])) {
            return false;
        }

        $ret = $this->events[$event]->removeEventListener($eventListener);

        if (!$ret) {
            return false;
        }

        if ($this->events[$event]->isEmpty()) {
            unset($this->events[$event]);
        }

        return true;
    }

    /**
     * Register the given event subscriber on the EventDispatcher
     *
     * An event listener is an object that
     *
     * @param IEventSubscriber $eventSubscriber
     * @return mixed
     */
    public function addEventSubscriber(IEventSubscriber $eventSubscriber)
    {
        return $eventSubscriber->subscribe($this);
    }

    /**
     * Unregister the given event subscriber from the EventDispatcher
     *
     * @param IEventSubscriber $eventSubscriber
     * @return mixed
     */
    public function removeEventSubscriber(IEventSubscriber $eventSubscriber)
    {
        return $eventSubscriber->unsubscribe($this);
    }

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
    public function dispatchEvent($event, $context = null, $eventParameters = array(), $conditionCallback = null)
    {
        if ($event instanceof IEventDescription) { // Passing event object only
            $eventObject = $event;
            $event = $eventObject->getName();
            $conditionCallback = $context;
        } elseif ($context instanceof IEventDescription) { // Passing event name and event object
            $eventObject = $context;
            $eventObject->setName($event);
            $conditionCallback = $eventParameters;
        } elseif ($eventParameters instanceof IEventDescription) { // passing event name, context and event object
            $eventObject = $eventParameters;
            $eventObject->setName($event);
            $eventObject->setContext($context);
        } else { // Passing event name, context and event parameters
            $eventObject = new \iHMS\EventDispatcher\Event($event, $context);

            foreach ($eventParameters as $pname => $pvalue) {
                $eventObject->setParam($pname, $pvalue);
            }
        }

        // Enforce type hinting for condition callback
        if ($conditionCallback && !is_callable($conditionCallback)) {
            throw new \InvalidArgumentException(
                sprintf("%s(): Invalid condition callback provided; expects callable", __METHOD__)
            );
        }

        $eventListenerResults = new EventListenerResults();
        $eventListenerQueue = $this->getListeners($event);
        $sharedEventListenerQueue = $this->getSharedListeners($event);

        if ($sharedEventListenerQueue) {
            $eventListenerQueue = clone $eventListenerQueue;

            /** @var $eventListener EventListener  */
            foreach ($sharedEventListenerQueue as $eventListener) {
                $eventListenerQueue->addEventListener($eventListener, $eventListener->getPriority());
            }
        }

        if (!$eventListenerQueue->isEmpty()) {
            /** @var $eventListener EventListener */
            foreach ($eventListenerQueue as $eventListener) {
                // Execute event listener callback and push its result into the ListenerResult object
                $eventListenerResults->addResult($eventListener->execute($eventObject));

                // Event asked top stop propagation, do so
                if ($eventObject->isPropagationStopped()) {
                    $eventListenerResults->setStopped(true);
                    break;
                }

                // If condition callback is provided and execution of it return TRUE, stop event propagation
                if ($conditionCallback && call_user_func($conditionCallback, $eventListenerResults->getLastResult())) {
                    $eventListenerResults->setStopped(true);
                    break;
                }
            }
        }

        return $eventListenerResults;
    }

    /**
     * Check if listeners are registered for the given event
     *
     * @param string $event Event name
     * @return bool
     */
    public function hasEventListener($event)
    {
        return (!empty($this->events[$event]));
    }

    /**
     * Returns list of registered events
     *
     * @return array
     */
    public function getEvents()
    {
        return array_keys($this->events);
    }

    /**
     * Returns Listener queue for the given event
     *
     * @param string $event Event name
     * @return EventListenerPriorityQueue
     */
    public function getListeners($event)
    {
        if (isset($this->events[$event])) {
            return $this->events[$event];
        }

        return new EventListenerPriorityQueue;
    }

    /**
     * Remove all listeners for the given event
     *
     * @param string $event Event name
     * @return bool
     */
    public function removeEventListeners($event)
    {
        if (isset($this->events)) {
            unset($this->events[$event]);
            return true;
        }

        return false;
    }

    /**
     * Prepare parameters
     *
     * Use this method if you want to be able to modify parameters from within an event listener. It returns an
     * ArrayObject of the parameters, which may then be passed to dispatchEvent().
     *
     * @param  array $parameters Parameters
     * @return \ArrayObject
     */
    public function prepareParameters(array $parameters)
    {
        return new \ArrayObject($parameters);
    }

    /**
     * Set the identifiers
     *
     * @throws \InvalidArgumentException in case $identifier is not a string nor an array
     * @param string|array $identifiers Identifiers
     * @return IEventDispatcher
     */
    public function setIdentifiers($identifiers)
    {
        $identifiers = (array)$identifiers;

        foreach ($identifiers as $identifier) {
            if (!is_string($identifier)) {
                throw new \InvalidArgumentException(
                    sprintf('%s:() Identifier must be a string, %s given', __METHOD__, gettype($identifier))
                );
            }
        }

        $this->identifiers = array_unique($identifiers);

        return $this;
    }

    /**
     * Add given identifier(s)
     *
     * @throws \InvalidArgumentException in case $identifier is not a string nor an array
     * @param string|array $identifiers Identifier(s)
     * @return EventDispatcher
     */
    public function addIdentifiers($identifiers)
    {
        $identifiers = (array)$identifiers;

        foreach ($identifiers as $identifier) {
            if (!is_string($identifier)) {
                throw new \InvalidArgumentException(
                    sprintf('%s:() Identifier must be a string, %s given', __METHOD__, gettype($identifier))
                );
            }
        }

        $this->identifiers = array_unique(array_merge($this->identifiers, $identifiers));

        return $this;
    }

    /**
     * Returns list of identifiers
     *
     * @return array List of identifiers
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    /**
     * Set shared event dispatcher instance
     *
     * @param ISharedEventDispatcher $sharedEventDispatcher
     * @return EventDispatcher
     */
    public function setSharedEventDispatcher(ISharedEventDispatcher $sharedEventDispatcher)
    {
        $this->sharedEventDispatcher = $sharedEventDispatcher;

        return $this;
    }

    /**
     * Returns shared event dispatcher instance
     *
     * @return bool|ISharedEventDispatcher FALSE in case no sharedEventDispatcher is set, SharedEventDispatcher otherwise
     */
    public function getSharedEventDispatcher()
    {
        if ($this->sharedEventDispatcher) {
            return $this->sharedEventDispatcher;
        }

        return false;
    }

    /**
     * Remove shared event dispatcher
     *
     * @return EventDispatcher
     */
    public function removeSharedEventDispatcher()
    {
        $this->sharedEventDispatcher = null;
    }

    /**
     * Returns list of all listeneds registered onto the shared event dispatcher for identifiers registered
     * by this instance
     *
     * @param string $event Event name
     * @return array
     */
    protected function getSharedListeners($event)
    {
        $sharedListeners = array();

        if (($sharedEventDispatcher = $this->sharedEventDispatcher)) {
            foreach ($this->getIdentifiers() as $identifier) {
                if ($listeners = $sharedEventDispatcher->getEventListeners($identifier, $event)) {
                    foreach ($listeners as $listener) {
                        $sharedListeners[] = $listener;
                    }
                }
            }
        }

        return $sharedListeners;
    }
}
