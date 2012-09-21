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
 * IEventDescription interface
 *
 * Interface describing an event. Any event must implement this interface.
 *
 * @package     iHMS_EventDispatcher
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
interface IEventDescription
{
    /**
     * Set event name
     *
     * @abstract
     * @param string $name Event name
     * @return IEventDescription
     */
    public function setName($name);

    /**
     * Returns event name
     *
     * @abstract
     * @return string Event name
     */
    public function getName();

    /**
     * Set context from which that event was triggered
     *
     * @abstract
     * @param string|object $context Context from which that event was triggered
     * @return IEventDescription
     */
    public function setContext($context);

    /**
     * Returns context from which that event was triggered
     *
     * @abstract
     * @return string|object
     */
    public function getContext();

    /**
     * Set event parameters
     *
     * @abstract
     * @param array|object|\ArrayAccess $params Parameters
     * @return IEventDescription
     */
    public function setParams($params);

    /**
     * returns all event parameters
     *
     * @abstract
     * @return array|object|\ArrayAccess
     */
    public function getParams();

    /**
     * Set value of the given event parameter
     *
     * @abstract
     * @param string $pname Parameter name
     * @param mixed $pvalue Parameter value
     * @return IEventDescription
     */
    public function setParam($pname, $pvalue);

    /**
     * Returns value of the given event parameter
     *
     * @abstract
     * @param string $pname Parameter name
     * @param mixed $defaultValue Default value returned in case $pname doesn't exists
     * @return mixed
     */
    public function getParam($pname, $defaultValue = null);

    /**
     * Set flag indicating whether event propagation is stopped
     *
     * @abstract
     * @param bool $flag Flag indicating whether event propagation is stopped
     * @return IEventDescription
     */
    public function stopPropagation($flag = true);

    /**
     * Is event propagation stopped?
     *
     * @abstract
     * @return bool TRUE if event propagation is stopped, FALSE otherwise
     */
    public function isPropagationStopped();
}
