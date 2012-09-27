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

use iHMS\ServiceLocator\IServiceLocator;
use iHMS\ServiceLocator\ServiceLocator;
use iHMS\ServiceLocator\IServiceLocatorConfigurator;
use iHMS\Router\IRoute;

/**
 * RouteLocator class
 *
 * @package     iHMS_Router
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class RouteLocator extends ServiceLocator
{
    /**
     * @var array Route options used to create route
     */
    protected $routeOptions = null;

    /**
     * Constructor
     *
     * Configure the locator with the given configurator
     *
     * @param IServiceLocatorConfigurator $configurator
     */
    public function __construct(IServiceLocatorConfigurator $configurator = null)
    {
        parent::__construct($configurator);
    }

    /**
     * Returns a route by name
     *
     * @param string $name Route name
     * @param array $options Route options
     * @return IRoute
     */
    public function get($name, $options = array())
    {
        $this->routeOptions = $options;
        $service = parent::get($name);
        $this->routeOptions = null;
        return $service;
    }

    /**
     * Returns route options
     *
     * @return array|null
     */
    public function getRouteOptions()
    {
        return $this->routeOptions;
    }

    /**
     * Create the given route
     *
     * @throws \RuntimeException in case route/factory class is not found or doesn't implement the IRoute interface
     * @param string $name Route name
     * @return IRoute
     */
    protected function _create($name)
    {
        if (isset($this->constructors[$name])) {
            $className = $this->constructors[$name];
        } elseif (isset($this->factories[$name])) {
            $className = $this->factories[$name];
        } else {
            throw new \RuntimeException(
                sprintf("%s(): Unable to create the route '%s': No such route is registered in route locator", __METHOD__, $name)
            );
        }

        /** @var $className IRoute|\iHMS\ServiceLocator\IServiceFactory */
        if (($interfaces = @class_implements($className)) !== false) {
            if (in_array('iHMS\Router\IRoute', $interfaces)) {
                $service = $className::factory($this->routeOptions);
            } elseif (in_array('iHMS\ServiceLocator\IServiceFactory', $interfaces)) {
                $service = $className::factory($this);
                if (!$service instanceof IRoute) {
                    throw new \RuntimeException(
                        sprintf("%s(): The %s class doesn't return the expected iHMS\Router\IRoute object", __METHOD__, $className));
                }
            } else {
                throw new \RuntimeException(
                    sprintf(
                        "%s(): The %s class doesn't implement the iHMS\Router\IRoute interface nor the iHMS\ServiceLocator\IServiceFactory interface",
                        __METHOD__, $className
                    )
                );
            }
        } else {
            throw new \RuntimeException(
                sprintf("%s(): Unable to create the route '%s'; class '%s' not found", __METHOD__, $name, $className)
            );
        }

        // Initialize the route
        foreach ($this->initializers as $initializer) {
            $initializer($service);
        }

        return $service;
    }
}
