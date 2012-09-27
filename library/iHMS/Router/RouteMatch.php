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
 * @package     iHMS_Router
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\Router;

/**
 * RouteMatch class
 *
 * Class representing a mached route.
 *
 * @package     iHMS_Router
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class RouteMatch
{
    /**
     * @var array Matched route parameters
     */
    protected $parameters;

    /**
     * @var string Matched route name
     */
    protected $matchedRouteName = null;

    /**
     * Constructor
     *
     * @param array $parameters Matched route parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Set matched route name
     *
     * @param string $name Matched route name
     * @return RouteMatch
     */
    public function setMatchedRouteName($name)
    {
        $this->matchedRouteName = (string)$name;

        return $this;
    }

    /**
     * Returns matched route name
     *
     * @return RouteMatch
     */
    public function getMatchedRouteName()
    {
        return $this->matchedRouteName;
    }

    /**
     * Return route parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set a parameter
     *
     * @param string $name Route parameter name
     * @param mixed $value Route parameter value
     * @return RouteMatch
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    /**
     * Returns value of the given parameter or the default value if not defined
     *
     * @param string $name Route parameter name
     * @param mixed $defaultValue Default value
     * @return mixed
     */
    public function getParameter($name, $defaultValue = null)
    {
        if (array_key_exists($name, $this->parameters)) {
            $defaultValue = $this->parameters[$name];
        }

        return $defaultValue;
    }
}
