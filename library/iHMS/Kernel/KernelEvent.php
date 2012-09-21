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
 * @package     iHMS_Kernel
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\Kernel;

use iHMS\EventDispatcher\Event;
use iHMS\Http\Request;
use iHMS\Http\Response;
use iHMS\Router\IRouter;

/**
 * KernelEvent class
 *
 * @package     iHMS_Kernel
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class KernelEvent extends Event
{
    /**#@+
     * Kernel events triggered by the event dispatcher
     */
    const BEFORE_BOOTSTRAP_EVENT = 'beforeBootstrap';
    const BOOTSTRAP_EVENT = 'bootstrap';
    const AFTER_BOOTSTRAP_EVENT = 'afterBootstrap';
    const ROUTE_EVENT = 'route';
    const DISPATCH_EVENT = 'dispatch';
    const RENDER_EVENT = 'render';
    /**#@-*/

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var IRouter
     */
    protected $router;

    /**
     * Set request object
     *
     * @param Request $request
     * @return KernelEvent
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Returns request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set response
     *
     * @param Response $response
     * @return KernelEvent
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Returns response
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set router
     *
     * @param IRouter $router
     * @return KernelEvent
     */
    public function setRouter(IRouter $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Returns router
     *
     * @return IRouter
     */
    public function getRouter()
    {
        return $this->router;
    }
}
