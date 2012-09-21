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
 * @package     iHMS_Http
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\Http;

use iHMS\Library\Parameters;
use iHMS\Http\Header\Header;

/**
 * ServerParameters class
 *
 * @package     iHMS_Http
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class ServerParameters extends Parameters
{
    /**
     * Constructor
     *
     * @param array $parameters Server parameters (i.e PHP $_SERVER global variable)
     */
    public function __construct($parameters = array())
    {
        parent::__construct($parameters);
    }

    /**
     * Returns headers
     *
     * @return mixed
     */
    public function getHeaders()
    {
        static $headers = null;

        if (null === $headers) {
            $headers = array();

            // Extract Authorization header
            if (function_exists('apache_request_headers')) {
                $apacheRequestHeader = apache_request_headers();
                if (isset($apacheRequestHeader['Authorization'])) {
                    $this['HTTP_AUTORIZATION'] = $apacheRequestHeader['Authorization'];
                }
            }

            foreach ($this as $key => $value) {
                if (strpos($key, 'HTTP_') === 0 && strpos($key, 'HTTP_COOKIE') === false) { // Retrieves headers (excluding cookies)
                    $headers[Header::normalizeFieldName(substr($key, 5))] = $value;
                } elseif (strpos($key, 'CONTENT_') === 0) { // handle CONTENT_* headers
                    $headers[Header::normalizeFieldName($key)] = $value;
                }
            }
        }

        return $headers;
    }
}
