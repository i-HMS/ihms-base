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

use iHMS\Router\IRoute;
use iHMS\Library\IMessage as Request;

/**
 * IRouter interface
 *
 * @package     iHMS_Router
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
interface IRouter
{
    /**
     * Add multiple route at once in the route stack
     *
     * @param array $routes Routes
     * @return IRouter
     */
    public function addRoutes(array $routes);

    /**
     * Add the given route in the route stack
     *
     * @param string $name Route name
     * @param Iroute|Array $route Either an IRoute object or an array describing route options
     * @return IRouter
     */
    public function addRoute($name, $route);

    /**
     * Remove the given route from the route stack
     *
     * @param string $name Route name
     * @return IRouter
     */
    public function removeRoute($name);

    /**
     * Match the routes against the given request
     *
     * @param Request $request
     * @return RouteMatch|null
     */
    public function match(Request $request);
}
