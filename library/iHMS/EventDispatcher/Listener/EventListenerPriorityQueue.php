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

use SplPriorityQueue;

/**
 * EventListenerPriorityQueue class
 *
 * Class implementing reusable priority queue of event listeners.
 *
 * @package     iHMS_EventDispatcher
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class EventListenerPriorityQueue implements \IteratorAggregate, \Countable
{
    /**
     * @var int Serial used for queue ordering
     */
    protected $serial = PHP_INT_MAX;

    /**
     * @var SplPriorityQueue
     */
    protected $innerPriorityQueue = null;

    /**
     * @var array Listeners and their associated priority for internal use
     */
    protected $items = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->innerPriorityQueue = new SplPriorityQueue();
    }

    /**
     * Add the given listener into the queue
     *
     * @param EventListener $listener
     * @param int $priority Priority
     * @return EventListenerPriorityQueue
     */
    public function addEventListener(EventListener $listener, $priority = 1)
    {
        $priority = (int)$priority;
        $this->items[] = array('listener' => $listener, 'priority' => $priority);
        $this->innerPriorityQueue->insert($listener, array($priority, $this->serial--));

        return $this;
    }

    /**
     * Dequeue the given listener
     *
     * @param EventListener $listener
     * @return bool TRUE if the listener was found, FALSE otherwise
     */
    public function removeEventListener(EventListener $listener)
    {
        foreach ($this->items as $k => $v) {
            if ($v['listener'] === $listener) {
                unset($this->items[$k]);
                $q = new SplPriorityQueue();

                foreach ($this->items as $i) {
                    $q->insert($i['listener'], $i['priority']);
                }

                // Replace previous inner priority queue with the new one
                $this->innerPriorityQueue = $q;

                return true;
            }
        }

        return false;
    }

    /**
     * Is the queue empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return (0 === $this->count());
    }

    /**
     * Does the queue have a listener with the given priority?
     *
     * @param int $priority Priority
     * @return bool
     */
    public function hasPriority($priority)
    {
        foreach ($this->items as $item) {
            if ($item['priority'] === $priority) {
                return true;
            }
        }

        return false;
    }

    /**
     * Implements IteratorAggregate interface
     *
     * @return SplPriorityQueue|\Traversable
     */
    public function getIterator()
    {
        return clone $this->innerPriorityQueue;
    }

    /**
     * Implements Countable interface
     *
     * @return int Number of listeners in the queue
     */
    public function count()
    {
        return count($this->items);
    }
}
