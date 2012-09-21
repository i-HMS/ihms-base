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

use iHMS\Library\Message;

/**
 * AHttpMessage abstract class
 *
 * Class describing an HTTP message as follow:
 *
 * -------------------------------------------
 * | start-line (Request-Line | Status-Line) |
 * -------------------------------------------
 * | *(message-header CRLF)                  |
 * -------------------------------------------
 * | CRLF                                    |
 * -------------------------------------------
 * | [ message-body ]                        |
 * -------------------------------------------
 *
 * @package     iHMS_Http
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @see         RFC 2616 section 4
 */
abstract class AHttpMessage extends Message
{
    /**
     * @var array Supported HTTP versions
     */
    protected $supportedVersions = array('1.0', '1.1');

    /**
     * @var string HTTP version
     */
    protected $version = '1.1';

    /**
     * @var HeaderCollection HTTP message headers
     */
    protected $headers = null;

    /**
     * Set HTTP version
     *
     * @throws \InvalidArgumentException in case provided HTTP version is not supported
     * @param string $version HTTP version (1.0.|1.1)
     * @return AHttpMessage
     */
    public function setVersion($version)
    {
        if (!in_array($version, $this->supportedVersions)) {
            throw new \InvalidArgumentException(
                sprintf("%s(): Invalid HTTP version '%s'; supported versions are 1.0 and 1.1", __METHOD__, $version)
            );
        }

        $this->version = $version;

        return $this;
    }

    /**
     * Returns HTTP version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Returns HTTP message start line
     *
     * @abstract
     * @return string
     */
    abstract public function getStartLine();

    /**
     * Set message headers
     *
     * @param HeaderCollection $headers
     * @param bool $merge Whether merge new header collection with previous
     * @return AHttpMessage
     */
    public function setHeaders(HeaderCollection $headers, $merge = false)
    {
        if ($merge) {
            $headers = $this->getHeaders()->addHeaders($headers);
        }

        $this->headers = $headers;

        return $this;
    }

    /**
     * Returns message header collection
     *
     * @return HeaderCollection
     */
    public function getHeaders()
    {
        return $this->headers ? : $this->headers = new HeaderCollection();
    }

    /**
     * Allows PHP casting - Returns string representation of HTTP message
     *
     * @return string
     */
    public function __toString()
    {
        $string = $this->getStartLine() . "\r\n";
        $string .= $this->getHeaders();
        $string .= "\r\n";
        $string .= $this->getContent();

        return $string;
    }
}
