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

use iHMS\Http\HeaderCollection;
use iHMS\Http\Header\IHeader;
use iHMS\Library\Parameters;

/**
 * Request class
 *
 * Class providing an interface to HTTP request.
 *
 * @package     iHMS_Http
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @see         rfc 2109, 2616 section 5
 */
class Request extends AHttpMessage
{
    /**
     * @var array Http methods (see rfc 2616, 5789)
     */
    protected $methods = array('OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT', 'PATCH');

    /**
     * @var string Http method
     */
    protected $method = null;

    /**
     * @var string Request URI
     */
    protected $requestUri = null;

    /**
     * @var string Base URL
     */
    protected $baseUrl = null;

    /**
     * @var string Base path
     */
    protected $basePath = null;

    /**
     * @var Parameters
     */
    protected $queryParameters = null;

    /**
     * @var Parameters
     */
    protected $postParameters = null;

    /**
     * @var FileParameters
     */
    protected $fileParameters = null;

    /**
     * @var ServerParameters
     */
    protected $serverParameters = null;

    /**
     * Constructor
     *
     * @param array|null $query Query parameters
     * @param array|null $post Post parameters
     * @param array|HeaderCollection|null $cookies Cookies
     * @param array|null $files Files parameters
     * @param array|null $server Server parameters
     * @param string $content Raw body data
     */
    public function __construct($query = null, $post = null, $cookies = null, $files = null, $server = null, $content = '')
    {
        if (null !== $query) {
            $this->setQuery(new Parameters($query));
        }

        if (null !== $post) {
            $this->setPost(new Parameters($post));
        }

        if (null !== $cookies) {
            $this->getHeaders()->addHeaders($cookies);
        }

        if (null !== $files) {
            $this->setFiles(new FileParameters($files));
        }

        if (null !== $server) {
            $this->setServer(new ServerParameters($server));
        }

        $this->setContent($content);
    }

    /**
     * Returns a request object created using PHP environment variables
     *
     * @static
     * @return Request
     */
    public static function createFromPhpEnvironment()
    {
        $request = new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);

        // Retrieves any post parameters from PUT, DELETE and PATCH requests
        if (
            ($header = $request->getHeader('Content-Type')) &&
            strpos($header->getFieldValue(), 'application/x-www-form-urlencoded') === 0 &&
            in_array($request->getMethod(), 'PUT', 'DELETE', 'PATCH')
        ) {
            parse_str($request->getContent(), $parameters);
            $request->setPost(new Parameters($parameters));
        }

        return $request;
    }

    /**
     * Set HTTP method
     *
     * @throws \InvalidArgumentException in case invalid HTTP method is provided
     * @param string $method Http method
     * @return Request
     */
    public function setMethod($method)
    {
        $method = strtoupper($method);

        if (!in_array($method, $this->methods)) {
            throw new \InvalidArgumentException(
                sprintf('%s(): Invalid HTTP method; valid methods are: %s', __METHOD__, implode(', ', $this->methods))
            );
        }

        $this->method = $method;

        return $this;
    }

    /**
     * Returns HTTP method
     *
     * @return string
     */
    public function getMethod()
    {
        if (null === $this->method) {
            $method = strtoupper($this->getServer()->get('REQUEST_METHOD', 'GET'));

            if ('POST' === $method) { // Detect PUT and DELETE methods
                if ($header = $this->getHeader('X-Http-Method-Override')) { // Google spec. header
                    $method = $header->getFieldValue();
                } else {
                    $method = $this->getPost('_method', $this->getQuery('_method', 'POST'));
                }
            }

            $this->setMethod($method);
        }

        return $this->method;
    }

    /**
     * Set request URI
     *
     * @throws \InvalidArgumentException in case the provided uri is not a string
     * @param string $uri Request URI
     * @return Request
     */
    public function setRequestUri($uri)
    {
        if (!is_string($uri)) {
            throw new \InvalidArgumentException(
                sprintf('%s(): expects a string; received %s', __METHOD__, gettype($uri))
            );
        }

        $this->requestUri = $uri;

        return $this;
    }

    /**
     * Returns request URI
     *
     * @return string
     */
    public function getRequestUri()
    {
        if (null == $this->requestUri) {
            $this->requestUri = $this->_retrieveRequestUri();
        }

        return $this->requestUri;
    }

    /**
     * Set request base path
     *
     * @param string $basePath
     * @return Request
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '/');

        return $this;
    }

    /**
     * Returns the root path from which this request is executed
     *
     * @return string
     */
    public function getBasePath()
    {
        if (null === $this->basePath) {
            $this->setBasePath($this->_retrieveBasePath());
        }

        return $this->basePath;
    }

    /**
     * Set request base URL
     *
     * @param string $baseUrl Base URL
     * @return Request
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Returns the root URL from which this request is executed
     *
     * @return string
     */
    public function getBaseUrl()
    {
        if (null === $this->baseUrl) {
            $this->setBaseUrl($this->_retrieveBaseUrl());
        }

        return $this->baseUrl;
    }

    /**
     * Returns all headers of same type or the given default value in case no header is found
     *
     * @param string $headerName Header name
     * @param mixed|null $defaultValue Default value returned in case no header is found
     * @return IHeader|HeaderCollection|mixed
     */
    public function getHeader($headerName, $defaultValue = null)
    {
        return $this->getHeaders()->getHeader($headerName) ? : $defaultValue;
    }

    /**
     * Returns request message-body
     *
     * @return string
     */
    public function getContent()
    {
        if (null === $this->content) {
            $content = file_get_contents('php://input');

            if (strlen($content) > 0) {
                $this->setContent($content);
            } else {
                $this->setContent($content);
            }
        }

        return $this->content;
    }

    /**
     * Set query parameters
     *
     * @param Parameters $parameters Query parameters
     * @return Request
     */
    public function setQuery(Parameters $parameters)
    {
        $this->queryParameters = $parameters;

        return $this;
    }

    /**
     * Returns query parameter container or value of the given query parameter
     *
     * @param string|null $name Parameter name or null to get parameter container
     * @param mixed|null $defaultValue Default value returned in case the given parameter is not found
     * @return Parameters|mixed|null
     */
    public function getQuery($name = null, $defaultValue = null)
    {
        if (null === $this->queryParameters) {
            $this->queryParameters = new Parameters();
        }

        if (null === $name) {
            return $this->queryParameters;
        }

        return $this->queryParameters->get($name, $defaultValue);
    }

    /**
     * Set post parameter container
     *
     * @param Parameters $parameters
     * @return Request
     */
    public function setPost(Parameters $parameters)
    {
        $this->postParameters = $parameters;

        return $this;
    }

    /**
     * Returns post parameter container or value of the given post parameter
     *
     * @param string|null $name Parameter name or null to get parameter container
     * @param mixed|null $defaultValue Default value returned in case the given parameter is not found
     * @return Parameters|mixed|null
     */
    public function getPost($name = null, $defaultValue = null)
    {
        if (null === $this->postParameters) {
            $this->postParameters = new Parameters();
        }

        if (null === $name) {
            return $this->postParameters;
        }

        return $this->postParameters->get($name, $defaultValue);
    }

    /**
     * Set files parameters container
     *
     * @param FileParameters $parameters
     * @return Request
     */
    public function setFiles(FileParameters $parameters)
    {
        $this->fileParameters = $parameters;

        return $this;
    }

    /**
     * Returns files parameter container or value of the given files parameter
     *
     * @param string|null $name Parameter name or null to get parameter container
     * @param mixed|null $defaultValue Default value returned in case the given parameter is not found
     * @return FileParameters|mixed|null
     */
    public function getFiles($name = null, $defaultValue = null)
    {
        if (null === $this->fileParameters) {
            $this->fileParameters = new FileParameters();
        }

        if (null === $name) {
            return $this->fileParameters;
        }

        return $this->fileParameters->get($name, $defaultValue);
    }

    /**
     * Set server parameters
     *
     * @param ServerParameters $parameters Server parameters
     * @return Request
     */
    public function setServer(ServerParameters $parameters)
    {
        $this->serverParameters = $parameters;
        $this->getHeaders()->addHeaders($parameters->getHeaders());

        // Set HTTP version
        $this->setVersion(substr($parameters->get('SERVER_PROTOCOL', '1.0'), -3));

        return $this;
    }

    /**
     * Returns server parameter container or value of the given server parameter
     *
     * @param string|null $name Parameter name or null to get parameter container
     * @param mixed|null $defaultValue Default value returned in case the given parameter is not found
     * @return ServerParameters|mixed|null
     */
    public function getServer($name = null, $defaultValue = null)
    {
        if (null === $this->serverParameters) {
            $this->serverParameters = new ServerParameters();
        }

        if (null === $name) {
            return $this->serverParameters;
        }

        return $this->serverParameters->get($name, $defaultValue);
    }

    /**
     * Returns request scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Returns the host name
     *
     * @return string
     */
    public function getHost()
    {
        if ($host = $this->getHeader('X-Forwarded-Host', false)) {
            $elements = explode(',', $host);
            $host = trim($elements[count($elements) - 1]);
        } else {
            if (!$host = $this->getHeader('Host', false)) {
                if (!$host = $this->getServer('SERVER_NAME', false)) {
                    $host = $this->getServer('SERVER_ADDR', '');
                }
            } else {
                $host = $host->getFieldValue();
            }
        }

        // Remove port number from host
        $host = preg_replace('/:\d+$/', '', $host);

        // host is lowercase as per RFC 952/2181
        return trim(strtolower($host));
    }

    /**
     * Returns the HTTP host being requested
     *
     * The port name will be appended to the host if it's non-standard.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();

        if (('http' == $scheme && $port == 80) || ('https' == $scheme && $port == 443)) {
            return $this->getHost();
        }

        return $this->getHost() . ':' . $port;
    }


    /**
     * Returns the port on which the request is made
     *
     * @return string
     */
    public function getPort()
    {
        if ($this->getHeaders()->hasHeader('X-Forwarded-Port')) {
            return $this->headers->getHeader('X-Forwarded-Port')->getFieldValue();
        }

        return $this->getServer('SERVER_PORT');
    }

    /**
     * Returns the user
     *
     * @return string|null
     * @Todo review
     */
    public function getUser()
    {
        return $this->getServer('PHP_AUTH_USER');
    }

    /**
     * Returns the password
     *
     * @return string|null
     * @Todo review
     */
    public function getPassword()
    {
        return $this->getServer('PHP_AUTH_PW');
    }

    /**
     * Return user information
     *
     * @return null|string
     */
    public function getUserInfo()
    {
        $userinfo = $this->getUser();
        $pass = $this->getPassword();

        if ('' != $pass) {
            $userinfo .= ":$pass";
        }

        return $userinfo;
    }

    /**
     * Is a secure HTTP request?
     *
     * @return bool
     */
    public function isSecure()
    {
        return (($this->getServer('HTTPS') == 'on' || $this->getServer('HTTPS') == 1) ||
            (($header = $this->getHeader('SSL_HTTPS')) && (strtolower($header->getFieldValue()) == 'on' || $header->getFieldValue() == 1)) ||
            (($header = $this->getHeader('X-Forwarded-Proto')) && strtolower($header->getFieldValue()) == 'https')
        );
    }

    /**
     * Is an OPTIONS HTTP method?
     *
     * @return bool
     */
    public function isOptions()
    {
        return ($this->getMethod() == 'OPTIONS');
    }

    /**
     * Is HEAD HTTP method?
     *
     * @return bool
     */
    public function isHead()
    {
        return ($this->getMethod() == 'HEAD');
    }

    /**
     * Is a GET HTTP method?
     *
     * @return bool
     */
    public function isGet()
    {
        return ($this->getMethod() == 'GET');
    }

    /**
     * Is a POST HTTP method?
     *
     * @return bool
     */
    public function isPost()
    {
        return ($this->getMethod() == 'POST');
    }

    /**
     * Is a PUT HTTP method?
     *
     * @return bool
     */
    public function isPut()
    {
        return ($this->getMethod() == 'PUT');
    }

    /**
     * Is a DELETE HTTP method?
     *
     * @return bool
     */
    public function isDelete()
    {
        return ($this->getMethod() == 'DELETE');
    }

    /**
     * Is a TRACE HTTP method?
     *
     * @return bool
     */
    public function isTrace()
    {
        return ($this->getMethod() == 'TRACE');
    }

    /**
     * Is a CONNECT HTTP method?
     *
     * @return bool
     */
    public function isConnect()
    {
        return ($this->getMethod() == 'CONNECT');
    }

    /**
     * Is a PATCH HTTP method?
     *
     * @return bool
     */
    public function isPatch()
    {
        return ($this->getMethod() == 'PATCH');
    }

    /**
     * Is a Javascript XMLHttpRequest?
     *
     * Should work with majority of Javascript libraries such as Prototype, script.aculo.us, MooTools, jQuery...
     *
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        return (($header = $this->getHeader('X-Requested-With')) && $header->getFieldValue() == 'XMLHttpRequest');
    }

    /**
     * Is a Flash request?
     *
     * @return bool
     */
    public function isFlashRequest()
    {
        return (($header = $this->getHeader('User-Agent')) && stripos($header->getFieldValue(), 'flash') !== false);
    }

    /**
     * Is a mobile request?
     *
     * Note: This method should be only used as fallback; It's recommended to use a full device detection database such
     * as DeviceAtlas, WURFL.
     *
     * @return bool
     */
    public function isMobileRequest()
    {
        static $isMobile = null;

        if (null === $isMobile) {
            $headerCollection = $this->getHeaders();

            if ($headerCollection->getFirstPartialMatchHeader('OperaMini') ||
                $headerCollection->getFirstMatchHeader(array('X-Wap-Profile', 'Profile'))
            ) {
                $isMobile = true;
            }

            if (!$isMobile && $header = $headerCollection->getHeader('Accept')) {
                $fieldValue = strtolower($header->getFieldValue());

                if (
                    strpos($fieldValue, 'application/vnd.wap.xhtml+xml') !== false ||
                    strpos($fieldValue, 'text/vnd.wap.wml') !== false
                ) {
                    $isMobile = true;
                }
            }

            if (!$isMobile && $header = $headerCollection->getHeader('User-Agent')) {
                $fieldValue = strtolower($header->getFieldValue());
                $devicesRegexp = '( mobi|-three|-x113|160x|240x|355x|3g_t|480x|8325rc|8352rc|848b|_h797|_mms|a1000|a615|a700|ac831|ahong|android|avantgo|b832|bc831|blackberry|blazer|brew |c-810|c500|c5005|c500foma:|c5100|c5588|c7100|c888|compal|d615|d736|d763|d88|e300|e860|el370|elaine|ems100|fly v71|foma|g83|gu1100|hiptop|htc\/|htc_touch|htil-g1|i250|i;458x|iemobile|ipad|iphone|ipod|iris|k610i|kddi|kindle|km100|ktouch|lg |lg210|lg370|lg380|lg47|lg840|lg920|lge |lgku|lgu900|m4u\/|m50|m5252|m800|m881|me701|me702|me702m-three|mg50|midp|mini 9.5|mk99|mmp|mobile|mobx|motorola|mowser|mp500|mt126|mtk |mw200|myx|n120|n210|nokia|novarra|nx250|o2|opera mini|opera mobi|p-9521|p404i|palm|palm os|phone|plucker|pocket|pre\/|psp|s210|s302|s5330|s55|s590|s700|s710|s800|s820|s920|s940|sam-r|samsu|samsung|samu3|samu4|samu5|samu6|samu7|samu9|sanyo|sk16d|sl74|sl900|smartphone|sony cmd|sonyericsson|sprint|symbian|t503|t66|t880|telco|teleca|treo|u940|up.browser|up.link|ux840|vodafone|vx10|vx1000|vx400|vx54|vx8|vx9|w398samr810|w839|wap|windows ce|windows ce; smartphone;|windows ce; iemobile|wireless|x160|x225|x320|x640|xda_|xiino)';
                $devicesArray = array(
                    '1207', '3gso', '4thp', '501i', '502i', '503i', '504i', '505i', '506i', '6310', '6590', '770s', '802s',
                    'a wa', 'abac', 'acer', 'acoo', 'acs-', 'aiko', 'airn', 'alav', 'alca', 'alco', 'amoi', 'anex', 'anny',
                    'anyw', 'aptu', 'arch', 'argo', 'aste', 'asus', 'attw', 'au-m', 'audi', 'aur ', 'aus ', 'avan', 'beck',
                    'bell', 'benq', 'bilb', 'bird', 'blac', 'blaz', 'brew', 'brvw', 'bumb', 'bw-n', 'bw-u', 'c55/', 'capi',
                    'ccwa', 'cdm-', 'cell', 'chtm', 'cldc', 'cmd-', 'cond', 'craw', 'dait', 'dall', 'dang', 'dbte', 'dc-s',
                    'devi', 'dica', 'dmob', 'doco', 'dopo', 'ds-d', 'ds12', 'el49', 'elai', 'eml2', 'emul', 'eric', 'erk0',
                    'esl8', 'ez40', 'ez60', 'ez70', 'ezos', 'ezwa', 'ezze', 'fake', 'fetc', 'fly-', 'fly_', 'g-mo', 'g1 u',
                    'g560', 'gene', 'gf-5', 'go.w', 'good', 'grad', 'grun', 'haie', 'hcit', 'hd-m', 'hd-p', 'hd-t', 'hei-',
                    'hiba', 'hipt', 'hita', 'hp i', 'hpip', 'hs-c', 'htc ', 'htc-', 'htc_', 'htca', 'htcg', 'htcp', 'htcs',
                    'htct', 'http', 'huaw', 'hutc', 'i-20', 'i-go', 'i-ma', 'i230', 'iac', 'iac-', 'iac/', 'ibro', 'idea',
                    'ig01', 'ikom', 'im1k', 'inno', 'ipaq', 'iris', 'jata', 'java', 'jbro', 'jemu', 'jigs', 'kddi', 'keji',
                    'kgt', 'kgt/', 'klon', 'kpt ', 'kwc-', 'kyoc', 'kyok', 'leno', 'lexi', 'lg g', 'lg-a', 'lg-b', 'lg-c',
                    'lg-d', 'lg-f', 'lg-g', 'lg-k', 'lg-l', 'lg-m', 'lg-o', 'lg-p', 'lg-s', 'lg-t', 'lg-u', 'lg-w', 'lg/k',
                    'lg/l', 'lg/u', 'lg50', 'lg54', 'lge-', 'lge/', 'libw', 'lynx', 'm-cr', 'm1-w', 'm3ga', 'm50/', 'mate',
                    'maui', 'maxo', 'mc01', 'mc21', 'mcca', 'merc', 'meri', 'midp', 'mio8', 'mioa', 'mits', 'mmef', 'mo01',
                    'mo02', 'mobi', 'mode', 'modo', 'mot ', 'mot-', 'moto', 'motv', 'mozz', 'mt50', 'mtp1', 'mtv ', 'mwbp',
                    'mywa', 'n100', 'n101', 'n102', 'n202', 'n203', 'n300', 'n302', 'n500', 'n502', 'n505', 'n700', 'n701',
                    'n710', 'nec-', 'nem-', 'neon', 'netf', 'newg', 'newt', 'nok6', 'noki', 'nzph', 'o2 x', 'o2-x', 'o2im',
                    'opti', 'opwv', 'oran', 'owg1', 'p800', 'palm', 'pana', 'pand', 'pant', 'pdxg', 'pg-1', 'pg-2', 'pg-3',
                    'pg-6', 'pg-8', 'pg-c', 'pg13', 'phil', 'pire', 'play', 'pluc', 'pn-2', 'pock', 'port', 'pose', 'prox',
                    'psio', 'pt-g', 'qa-a', 'qc-2', 'qc-3', 'qc-5', 'qc-7', 'qc07', 'qc12', 'qc21', 'qc32', 'qc60', 'qci-',
                    'qtek', 'qwap', 'r380', 'r600', 'raks', 'rim9', 'rove', 'rozo', 's55/', 'sage', 'sama', 'samm', 'sams',
                    'sany', 'sava', 'sc01', 'sch-', 'scoo', 'scp-', 'sdk/', 'se47', 'sec-', 'sec0', 'sec1', 'semc', 'send',
                    'seri', 'sgh-', 'shar', 'sie-', 'siem', 'sk-0', 'sl45', 'slid', 'smal', 'smar', 'smb3', 'smit', 'smt5',
                    'soft', 'sony', 'sp01', 'sph-', 'spv ', 'spv-', 'sy01', 'symb', 't-mo', 't218', 't250', 't600', 't610',
                    't618', 'tagt', 'talk', 'tcl-', 'tdg-', 'teli', 'telm', 'tim-', 'topl', 'tosh', 'treo', 'ts70', 'tsm-',
                    'tsm3', 'tsm5', 'tx-9', 'up.b', 'upg1', 'upsi', 'utst', 'v400', 'v750', 'veri', 'virg', 'vite', 'vk-v',
                    'vk40', 'vk50', 'vk52', 'vk53', 'vm40', 'voda', 'vulc', 'vx52', 'vx53', 'vx60', 'vx61', 'vx70', 'vx80',
                    'vx81', 'vx83', 'vx85', 'vx98', 'w3c ', 'w3c-', 'wap-', 'wapa', 'wapi', 'wapj', 'wapm', 'wapp', 'wapr',
                    'waps', 'wapt', 'wapu', 'wapv', 'wapy', 'webc', 'whit', 'wig ', 'winc', 'winw', 'wmlb', 'wonu', 'x700',
                    'xda-', 'xda2', 'xdag', 'yas-', 'your', 'zeto', 'zte-'
                );

                if (!$isMobile && (preg_match("/$devicesRegexp/i", $fieldValue) || in_array(substr($fieldValue, 0, 4), $devicesArray))) {
                    $isMobile = true;
                }

                if (!$isMobile) {
                    $isMobile = false;
                }
            }
        }

        return $isMobile;
    }

    /**
     * Returns HTTP message start line
     *
     * @return string
     */
    public function getStartLine()
    {
        return sprintf('%s %s HTTP/%s', $this->getMethod(), $this->getRequestUri(), $this->getVersion());
    }

    /**
     * Retrieves and returns the requested URI
     *
     * @return string The raw URI
     */
    protected function _retrieveRequestUri()
    {
        $requestUri = null;

        // Check this first so IIS will catch.
        if ($httpXRewriteUrl = $this->getHeader('X-Rewrite-Url', false)) {
            $requestUri = $httpXRewriteUrl->getFieldValue();
        }

        // Check for IIS 7.0 or later with ISAPI_Rewrite
        if ($httpXOriginalUrl = $this->getHeader('X-Original-Url', false)) {
            $requestUri = $httpXOriginalUrl->getFieldValue();
        }

        // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem).
        $iisUrlRewritten = $this->getServer('IIS_WasUrlRewritten');
        $unencodedUrl = $this->getServer('UNENCODED_URL', '');
        if ('1' == $iisUrlRewritten && '' !== $unencodedUrl) {
            return $unencodedUrl;
        }

        // HTTP proxy requests setup request URI with scheme and host [and port] + the URL path, only use URL path.
        if (!$httpXRewriteUrl) {
            $requestUri = $this->getServer('REQUEST_URI');
        }

        if ($requestUri !== null) {
            return preg_replace('#^[^:]+://[^/]+#', '', $requestUri);
        }

        // IIS 5.0, PHP as CGI.
        $origPathInfo = $this->getServer('ORIG_PATH_INFO');
        if ($origPathInfo !== null) {
            $queryString = $this->getServer('QUERY_STRING', '');
            if ($queryString !== '') {
                $origPathInfo .= '?' . $queryString;
            }
            return $origPathInfo;
        }

        return '/';
    }

    /**
     * Retrieves and returns request base URL
     *
     * @return string
     */
    protected function _retrieveBaseUrl()
    {
        $filename = $this->getServer('SCRIPT_FILENAME', '');
        $scriptName = $this->getServer('SCRIPT_NAME');
        $phpSelf = $this->getServer('PHP_SELF');
        $origScriptName = $this->getServer('ORIG_SCRIPT_NAME');

        if ($scriptName !== null && basename($scriptName) === $filename) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $filename) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $filename) {
            // 1and1 shared hosting compatibility.
            $baseUrl = $origScriptName;
        } else {
            // Backtrack up the SCRIPT_FILENAME to find the portion matching PHP_SELF.

            $baseUrl = '/';
            $basename = basename($filename);
            if ($basename) {
                $path = ($phpSelf ? trim($phpSelf, '/') : '');
                $baseUrl .= substr($path, 0, strpos($path, $basename)) . $basename;
            }
        }

        // Does the base URL have anything in common with the request URI?
        $requestUri = $this->getRequestUri();

        // Full base URL matches.
        if (0 === strpos($requestUri, $baseUrl)) {
            return $baseUrl;
        }

        // Directory portion of base path matches.
        $baseDir = str_replace('\\', '/', dirname($baseUrl));
        if (0 === strpos($requestUri, $baseDir)) {
            return $baseDir;
        }

        $truncatedRequestUri = $requestUri;

        if (false !== ($pos = strpos($requestUri, '?'))) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);

        // No match whatsoever
        if (empty($basename) || false === strpos($truncatedRequestUri, $basename)) {
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename out of the base path. $pos !== 0 makes sure
        // it is not matching a value from PATH_INFO or QUERY_STRING.
        if (strlen($requestUri) >= strlen($baseUrl) && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)
        ) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return $baseUrl;
    }

    /**
     * Retrieve and return request base path
     *
     * @return string
     */
    protected function _retrieveBasePath()
    {
        $filename = basename($this->getServer('SCRIPT_FILENAME', ''));
        $baseUrl = $this->getBaseUrl();

        // Empty base url detected
        if ($baseUrl === '') {
            return '';
        }

        // basename() matches the script filename; return the directory
        if (basename($baseUrl) === $filename) {
            return str_replace('\\', '/', dirname($baseUrl));
        }

        // Base path is identical to base URL
        return $baseUrl;
    }
}
