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

use iHMS\Http\Header\IHeader;
use iHMS\Http\Header\Header;

/**
 * Response class
 *
 * Class providing an interface to HTTP response.
 *
 * @package     iHMS_Http
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @see         rfc 6225, 2616 section 6
 */
class Response extends AHttpMessage
{
    /**
     * Response status codes and their associated reason phrases
     *
     * @see RFC 2616 section 6.1.1 and {@link http://en.wikipedia.org/wiki/List_of_HTTP_status_codes}
     * @var array
     */
    protected $statusCodesAndReasonPhrases = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required'
    );

    /**
     * @var int Response status code
     */
    protected $statusCode;

    /**
     * @var string Custom reason phrase
     */
    protected $customReasonPhrase = null;

    /**
     * @var bool Flag indicating whether response message headers were sent
     */
    protected $areHeadersSent = false;

    /**
     * @var bool Flag indicating whether response message body has been sent
     */
    protected $isBodySent = false;

    /**
     * Constructor
     *
     * @param string $content Response message content
     * @param int $status Response status code
     * @param HeaderCollection $headers
     */
    public function __construct($content = '', $status = 200, HeaderCollection $headers = null)
    {
        if(null !== $headers) {
            $this->setHeaders($headers);
        }

        $this->setContent($content);
        $this->setStatusCode($status);
    }

    /**
     * Set response status code
     *
     * @throws \InvalidArgumentException in case HTTP response status code is invalid
     * @param int $code Response status code
     * @param string $reasonPhrase Custom reason pĥrase
     * @return Response
     */
    public function setStatusCode($code, $reasonPhrase = null)
    {
        if (!isset($this->statusCodesAndReasonPhrases[$code])) {
            throw new \InvalidArgumentException(sprintf("%s(): Invalid response status code", __METHOD__));
        }

        if (null !== $reasonPhrase) {
            $this->customReasonPhrase = trim($reasonPhrase);
        }

        $this->statusCode = (int)$code;

        return $this;
    }

    /**
     * Returns response status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Returns response message status line
     *
     * @return string
     */
    public function getStartLine()
    {
        return sprintf(
            'HTTP/%s %d %s',
            $this->version,
            $this->statusCode,
            $this->customReasonPhrase ? : $this->statusCodesAndReasonPhrases[$this->statusCode]
        );
    }

    /**
     * Send response message headers
     *
     * @return Response
     */
    public function sendHeaders()
    {

        if (!$this->areHeadersSent) {
            header($this->getStartLine());
            header("HTTP/{$this->version} {$this->statusCode} {$this->statusCodesAndReasonPhrases[$this->statusCode]}");

            foreach ($this->getHeaders() as $header) {
                header($header, false);
            }

            $this->areHeadersSent = true;
        }

        return $this;
    }

    /**
     * Returns value of flag indicating whether response message headers were sent
     *
     * @return bool
     */
    public function areHeaderSent()
    {
        return $this->areHeadersSent;
    }

    /**
     * Returns response message body
     *
     * @return string
     */
    public function getBody()
    {
        $body = $this->getContent();
        $transferEncoding = $this->getHeaders()->getHeader('Transfer-Encoding');

        if ($transferEncoding) {
            if (strtolower($transferEncoding->getFieldValue()) == 'chunked') {
                $body = $this->decodeChunkedBody($body);
            }
        }

        $contentEncoding = $this->getHeaders()->getHeader('Content-Encoding');

        if ($contentEncoding) {
            $contentEncoding = $contentEncoding->getFieldValue();

            if ($contentEncoding == 'gzip' || $contentEncoding == 'x-gzip') {
                $body = $this->decodeGzip($body);
            } elseif ($contentEncoding == 'deflate') {
                $body = $this->decodeDeflate($body);
            }
        }

        return $body;
    }

    /**
     * Send response message body
     *
     * @return Response
     * @toto
     */
    public function sendBody()
    {
        if (!$this->isBodySent) {
            echo $this->content;
            $this->isBodySent = true;
        }

        return $this;
    }

    /**
     * Returns value of flag indicating whether response message body has been sent
     *
     * @return bool
     */
    public function isBodySent()
    {
        return $this->isBodySent;
    }

    /**
     * Send response message (including message headers and message body)
     *
     * @return Response
     */
    public function send()
    {
        $this->sendHeaders()->sendBody();

        return $this;
    }

    /**
     * Set cookie
     *
     * @see RFC 6265
     * @throws \InvalidArgumentException in case $name is not string or empty
     * @param string $name
     * @param string $value [optional]
     * @param int $expires [optional]
     * @param string $path [optional]
     * @param string $domain [optional]
     * @param bool $secure [optional]
     * @param bool $httpOnly [optional]
     * @return Response
     */
    public function setCookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httpOnly = null)
    {
        if (!is_string($name) || $name === '') {
            throw new \InvalidArgumentException(sprintf('%s(): Cookie name must be an non-empty string', __METHOD__));
        } elseif (preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new \InvalidArgumentException(sprintf('%s(): Cookie name contains invalid characters.', __METHOD__));
        }

        if (strpos($value, '"') !== false) {
            $value = '"' . urlencode(str_replace('"', '', $value)) . '"';
        } else {
            $value = urlencode($value);
        }

        $fieldValue = $name . '=' . $value;

        if ($expires !== null) {
            if (is_string($expires)) {
                $expires = strtotime($expires);
            } elseif (!is_int($expires)) {
                throw new \InvalidArgumentException('Invalid expires time specified');
            }

            $fieldValue .= '; Expires=' . gmdate('D, d-M-Y H:i:s', (int)$expires) . ' GMT';
        }

        if ($path) {
            $fieldValue .= '; Path=' . $path;
        }

        if ($domain) {
            $fieldValue .= '; Domain=' . $domain;
        }

        if ($secure) {
            $fieldValue .= '; Secure';
        }

        if ($httpOnly) {
            $fieldValue .= '; HttpOnly';
        }

        $this->getHeaders()->addRawHeader('Set-Cookie', $fieldValue);

        return $this;
    }

    /**
     * Returns cookies
     *
     * @return bool|IHeader|HeaderCollection
     */
    public function getCookies()
    {
        return $this->getHeaders()->getHeader('Set-Cookie');
    }

    /**
     * Decode a "chunked" transfer-encoded body and return the decoded text
     *
     * @throws \RuntimeException in case body is not chunked message
     * @param  string $body Chunked body
     * @return string
     */
    protected function decodeChunkedBody($body)
    {
        $decBody = '';

        while (trim($body)) {
            if (!preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $body, $m)) {
                throw new \RuntimeException(
                    sprintf("%s(): Error parsing body - doesn't seem to be a chunked message", __METHOD__)
                );
            }

            $length = hexdec(trim($m[1]));
            $cut = strlen($m[0]);
            $decBody .= substr($body, $cut, $length);
            $body = substr($body, $cut + $length + 2);
        }

        return $decBody;
    }

    /**
     * Decode a gzip encoded message (when Content-encoding = gzip)
     *
     * Note: Requires PHP with zlib support
     *
     * @throws \RuntimeException in case PHP zlib extension is not available
     * @param  string $body
     * @return string
     */
    protected function decodeGzip($body)
    {
        if (!function_exists('gzinflate')) {
            throw new \RuntimeException(
                sprintf('%s(): PHP zlib extension is required in order to decode "gzip" encoding', __METHOD__)
            );
        }

        return gzinflate(substr($body, 10));
    }

    /**
     * Decode a zlib deflated message (when Content-encoding = deflate)
     *
     * Note: Requires PHP with zlib support
     *
     * @throws \RuntimeException in case PHP zlib extension is not available
     * @param  string $body
     * @return string
     */
    protected function decodeDeflate($body)
    {
        if (!function_exists('gzuncompress')) {
            throw new \RuntimeException(
                sprintf('%s(): PHP zlib extension is required in order to decode "deflate" encoding', __METHOD__)
            );
        }

        $zlibHeader = unpack('n', substr($body, 0, 2));

        if ($zlibHeader[1] % 31 == 0) {
            return gzuncompress($body);
        } else {
            return gzinflate($body);
        }
    }
}
