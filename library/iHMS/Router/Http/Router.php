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

namespace iHMS\Router\Http;

use iHMS\Router\IRouter;
use iHMS\Router\RouteLocator;
use iHMS\Router\IRoute;
use iHMS\Library\IMessage as Request;

/**
 * Router class
 *
 * @package     iHMS_Router
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class Router implements IRouter
{
    /**
     * @const Default route
     */
    const DEFAULT_ROUTE = 'DefaultRoute';

    /**
     * @var IRoute[]
     */
    protected $routeStack = null;

    /**
     * @var RouteLocator
     */
    protected $routeLocator = null;

    /**
     * Constructor
     *
     * @param array|\iHMS\Config\Config|null $config Configuration
     */
    public function __construct($config = null)
    {
        $this->setRouteLocator(
            new RouteLocator(new \iHMS\Router\Http\Route\RouteLocatorConfigurator($config))
        );
    }

    /**
     * Router factory
     *
     * Expects an array with the following structure:
     *
     * array(
     *  // Allow to add custom routes by adding them to the route locator
     *  'route_locator' => array(
     *      'constructor' => array(
     *          'route_name' => 'route_class'
     *      ),
     *      'factories' => array(
     *          'route_name' => 'route_class'
     *      )
     *  ),
     *  // Route definitions
     *  'routes' => array(
     *      'route_name1' => array(
     *          'type' => 'Default',
     *          'options' => array(
     *              'route' => ':module/:controller/:action[/*id{\d+}]',
     *              'default_parameters' => array(
     *                  'module' => 'users',
     *                  'controller' => 'profile',
     *                  'action' => 'show'
     *              )
     *          )
     *      ),
     *      'route_name2' => $routeInstance
     *  )
     * );
     *
     * @param \iHMS\Config\Config|Array $config Router configuration
     * @throws \InvalidArgumentException
     * @return Router
     */
    public static function factory($config)
    {
        if ($config instanceof \iHMS\Config\Config) {
            $config = $config->toArray();
        } elseif (!is_array($config)) {
            throw new \InvalidArgumentException(
                sprintf('%s(): expects a config object or an array; received %s', __METHOD__, gettype($config))
            );
        }

        $router = new static(isset($config['route_locator']) ? $config['route_locator'] : null);

        if (isset($config['routes'])) {
            $router->addRoutes($config['routes']);
        }

        return $router;
    }

    /**
     * Set route locator
     *
     * @param RouteLocator $routeLocator
     * @return Router
     */
    public function setRouteLocator(RouteLocator $routeLocator)
    {
        $this->routeLocator = $routeLocator;

        return $this;
    }

    /**
     * Returns route manager
     *
     * @return RouteLocator
     */
    public function getRouteLocator()
    {
        return $this->routeLocator;
    }

    /**
     * Add multiple route at once
     *
     * Expects an array with the following structure:
     *
     * array(
     *  'route_name1 => array(
     *      'type' => 'route_type',
     *      'options' => array(
     *          'route' => ':module/:controller/:action[/#id]
     *          'default_parameters' => array(
     *              'module' => 'users',
     *              'controller' => 'profile',
     *              'action' =>  'show'
     *          ),
     *      ),
     *      'route_name2' => $routeInstance
     * )
     *
     * @param array $routes Route definitions
     * @return Router
     */
    public function addRoutes(array $routes)
    {
        foreach ($routes as $name => $route) {
            $this->addRoute($name, $route);
        }

        return $this;
    }

    /**
     * Add a route in the route stack
     *
     * @param string $name Route name
     * @param Iroute|Array $route Either an IRoute object or an array describing route options
     * @return Router
     */
    public function addRoute($name, $route)
    {
        if (!$route instanceof IRoute) {
            $route = $this->getRouteLocator()->get(
                isset($route['type']) ? $route['type'] : static::DEFAULT_ROUTE,
                isset($route['options']) ? $route['options'] : array()
            );
        }

        $this->routeStack[$name] = $route;

        return $this;
    }

    /**
     * Returns the given route
     *
     * @param string $name Route name
     * @return IRoute|null
     */
    public function getRoute($name)
    {
        if ($this->hasRoute($name)) {
            return $this->routeStack[$name];
        }

        return null;
    }

    /**
     * Has the given route?
     *
     * @param string $name Route name
     * @return bool
     */
    public function hasRoute($name)
    {
        return (isset($this->routeStack[$name]));
    }

    /**
     * Remove the given route from the route stack
     *
     * @param string $name Route name
     * @return Router
     */
    public function removeRoute($name)
    {
        unset($this->routeStack[$name]);

        return $this;
    }

    /**
     * Match the routes against the given request
     *
     * @param Request $request
     * @return \iHMS\Router\RouteMatch|null
     */
    public function match(Request $request)
    {
        // Loop in LIFO order
        /** @var $route IRoute */
        foreach (array_reverse($this->routeStack) as $name => $route) {
            if ($routeMatch = $route->match($request)) {
                $routeMatch->setMatchedRouteName($name);
            }

            return $routeMatch;
        }

        return null;
    }
}
