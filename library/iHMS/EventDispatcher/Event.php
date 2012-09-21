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

/**
 * Event class
 *
 * class providing default IEventDescription interface implementation.
 *
 * @package     iHMS_EventDispatcher
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class Event implements IEventDescription
{
    /**
     * @var string Event name
     */
    protected $name;

    /**
     * @var string|object Context from which that event was triggered
     */
    protected $context;

    /**
     * @var array Event parameters
     */
    protected $params = array();

    /**
     * @var bool Flag indicating whether event propagation is stopped
     */
    protected $isPropagationStopped = false;

    /**
     * Constructor
     *
     * @param string $name OPTIONAL Event name
     * @param object|string $context OPTIONAL Context from which that event was triggered
     */
    public function __construct($name = null, $context = null)
    {
        if (null !== $name) {
            $this->setName($name);
        }

        if (null !== $context) {
            $this->setContext($context);
        }
    }

    /**
     * Set event name
     *
     * @throws \InvalidArgumentException in case $name is not a string
     * @param string $name Event name
     * @return Event
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException(
                sprintf('%s(): expects a string, received %s', __METHOD__, gettype($name))
            );
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Returns event name
     *
     * @return string Event name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set context from which that event was triggered
     *
     * @throws \InvalidArgumentException in case $context is not a string nor an object
     * @param string|object $context Context from which that event was triggered
     * @return Event
     */
    public function setContext($context)
    {
        if (!is_string($context) && !is_object($context)) {
            throw new \InvalidArgumentException(
                sprintf('%s(): expects string or object, received %s', __METHOD__, gettype($context))
            );
        }

        $this->context = $context;

        return $this;
    }

    /**
     * Returns context from which that event was triggered
     *
     * @return string|object
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set event parameters
     *
     * @throws \InvalidArgumentException in case $params is not an array, nor an object nor an ArrayAccess
     * @param array|object|\ArrayAccess $params Parameters
     * @return IEventDescription
     */
    public function setParams($params)
    {
        if (!is_array($params) && !is_object($params)) {
            throw new \InvalidArgumentException(
                sprintf('%s(): Event parameters must be an array or object; received %s', __METHOD__, gettype($params))
            );
        }

        $this->params = $params;

        return $this;
    }

    /**
     * returns all parameters
     *
     * @return array|object|\ArrayAccess
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set value of the given event parameter
     *
     * @param string $pname Parameter name
     * @param mixed $pvalue Parameter value
     * @return Event
     */
    public function setParam($pname, $pvalue)
    {
        $this->params[$pname] = $pvalue;

        return $this;
    }

    /**
     * Returns value of the given event parameter or default value provided in case parameter is not found
     *
     * @param string $pname Parameter name
     * @param mixed $defaultValue Default value returned in case $pname is not set
     * @return mixed
     */
    public function getParam($pname, $defaultValue = null)
    {
        // Check in params that are arrays or implement array access
        if (is_array($this->params) || $this->params instanceof \ArrayAccess) {
            if (!isset($this->params[$pname])) {
                return $defaultValue;
            }

            return $this->params[$pname];
        }

        // Check in normal objects
        if (!isset($this->params->{$pname})) {
            return $defaultValue;
        }

        $x = new \ArrayObject();

        return $this->params->{$pname};
    }

    /**
     * Set value of flag indicating whether event propagation is stopped
     *
     * @param bool $flag Flag indicating whether event propagation is stopped
     * @return Event
     */
    public function stopPropagation($flag = true)
    {
        $this->isPropagationStopped = (bool)$flag;

        return $this;
    }

    /**
     * Is event propagation stopped?
     *
     * @return bool TRUE if event propagation is stopped, FALSE otherwise
     */
    public function isPropagationStopped()
    {
        return $this->isPropagationStopped;
    }
}
