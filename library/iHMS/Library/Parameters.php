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
 * @package     iHMS_Library
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\Library;

/**
 * Parameters class
 *
 * Class representing parameter container.
 *
 * @package     iHMS_Library
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @todo return null if parameter is not found
 */
class Parameters extends \ArrayObject
{
    /**
     * Constructor
     *
     * @throws \InvalidArgumentException in case parameter is not an array
     * @param array $parameters
     */
    public function __construct($parameters = array())
    {
        if (!is_array($parameters)) {
            throw new \InvalidArgumentException(
                sprintf('%s() expect an array of named parameters; received: %s', __METHOD__, gettype($parameters))
            );
        }

        parent::__construct($parameters, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Set parameters from the given array
     *
     * @param array $parameters
     * @return Parameters
     */
    public function setFromArray($parameters)
    {
        $this->exchangeArray($parameters);

        return $this;
    }

    /**
     * Set parameters from the given string
     *
     * @param string $string
     * @return Parameters
     */
    public function fromString($string)
    {
        parse_str($string, $array);
        $this->setFromArray($array);

        return $this;
    }

    /**
     * Set value of the given parameter
     *
     * @param string $name Parameter name
     * @param mixed $value Parameter value
     * @return Parameters
     */
    public function set($name, $value)
    {
        $this[$name] = $value;

        return $this;
    }

    /**
     * Returns value of the given parameter or default value in case parameter is not found
     *
     * @param string $name Parameter name
     * @param mixed|null $defaultValue Default value returned in case parameter is not found
     * @return mixed
     */
    public function get($name, $defaultValue = null)
    {
        return parent::offsetExists($name) ? parent::offsetGet($name) : $defaultValue;
    }

    /**
     * Returns array representation of parameters
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getArrayCopy();
    }

    /**
     * Allow PHP casting - Returns string representation of parameters
     *
     * @return string
     */
    public function __toString()
    {
        return http_build_query($this);
    }
}
