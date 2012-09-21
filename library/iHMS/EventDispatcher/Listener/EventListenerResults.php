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

/**
 * EventListenerResults
 *
 * class representing collection of EventListener execution results
 *
 * @package     iHMS_EventDispatcher
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class EventListenerResults implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * @var array Stack of result
     */
    protected $results = array();

    /**
     * @var bool Flag indicating whether event propagation is stopped
     */
    protected $isStopped = false;

    /**
     * Set event propagation flag
     *
     * @param bool $flag Flag indicating whether event propagtion is stopped
     * @return EventListenerResults
     */
    public function setStopped($flag = true)
    {
        $this->isStopped = (bool)$flag;

        return $this;
    }

    /**
     * Is event proparagtion stopped
     *
     * @return bool
     */
    public function isStopped()
    {
        return $this->isStopped;
    }

    /**
     * Add the given result to the end of the result stack
     *
     * @param mixed $result Result to add in the result stack
     */
    public function addResult($result)
    {
        $this->results[] = $result;
    }

    /**
     * Returns first result from the result stack
     *
     * @return mixed
     */
    public function getFirstResult()
    {
        reset($this->results);
        return current($this->results);
    }

    /**
     * Returns last result from the result stack
     *
     * @return mixed
     */
    public function getLastResult()
    {
        return end($this->results);
    }

    /**
     * Has the given result in result stack?
     *
     * @param mixed $result Result to match against
     * @return bool
     */
    public function hasResult($result)
    {
        return (in_array($result, $this->results, true));
    }

    /**
     * Implements ArrayAccess interface - Whether a offset exists
     *
     * @param mixed $offset An offset to check for
     * @return boolean TRUE on success or FALSE on failure
     */
    public function offsetExists($offset)
    {
        return (isset($this->results[$offset]));
    }

    /**
     * Implements ArrayAccess interface - Offset to retrieve
     *
     * @param mixed $offset The offset to retrieve
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->results[$offset];
    }

    /**
     * Implements ArrayAccess interface - Offset to set
     *
     * @param mixed $offset The offset to assign the value to
     * @param mixed $value The value to set
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->results[$offset] = $value;
    }

    /**
     * Implements ArrayAccess interface - Offset to unset
     *
     * @param mixed $offset The offset to unset.
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->results[$offset]);
    }

    /**
     * Implements IteratorAggregate interface
     * @return \ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->results);
    }

    /**
     * Implements Countable interface - Count elements of an object
     * @return int
     */
    public function count()
    {
        return count($this->results);
    }
}
