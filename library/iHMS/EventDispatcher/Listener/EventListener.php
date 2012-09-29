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

namespace iHMS\EventDispatcher\Listener;

use iHMS\EventDispatcher\IEventDescription;

/**
 * EventListener class
 *
 * Class describing an event listener.
 *
 * @package     iHMS_EventDispatcher
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class EventListener
{
    /**
     * @var string Event name on which this listener callback operate on
     */
    protected $eventName = null;

    /**
     * @var int Priority at which this listener callback should be executed
     */
    protected $priority = null;

    /**
     * @var callable Listener callback
     */
    protected $callback = null;

    /**
     * Constructor
     *
     * @param callable $callback Listener callback
     * @param string $eventName Event name
     * @param int $priority Priority
     */
    public function __construct($callback, $eventName, $priority)
    {
        $this->_setCallback($callback);
        $this->setEventName($eventName);
        $this->setPriority($priority);
    }

    /**
     * Set event name on which this listener callback operate
     *
     * @throws \InvalidArgumentException in case $name is not a string
     * @param string $eventName Event name
     * @return EventListener
     */
    public function setEventName($eventName)
    {
        if (!is_string($eventName)) {
            throw new \InvalidArgumentException(
                sprintf('%s(): expects a string, received %s'), __METHOD__, gettype($eventName)
            );
        }

        $this->eventName = $eventName;

        return $this;
    }

    /**
     * Returns event name on which this listener callback operate
     *
     * @return string Event name
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * Set priority
     *
     * @param int $priority Priority
     * @return EventListener
     */
    public function setPriority($priority)
    {
        $this->priority = (int)$priority;

        return $this;
    }

    /**
     * Returns priority
     *
     * @return int Priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Returns callback
     *
     * @return callable Listener callback
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Execute Listener callback
     *
     * @param IEventDescription $event Event
     * @return mixed
     */
    public function execute(IEventDescription $event)
    {
        return call_user_func($this->callback, $event);
    }

    /**
     * Set listener callback
     *
     * @throws \InvalidArgumentException
     * @param callable $callback Listener callback
     * @return void
     */
    protected function _setCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException(sprintf('%s(): Invalid callback provided; expects callable', __METHOD__));
        }

        $this->callback = $callback;
    }
}
