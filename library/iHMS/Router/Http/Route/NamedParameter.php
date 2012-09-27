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

namespace iHMS\Router\Http\Route;

use \iHMS\Router\IRoute;
use iHMS\Router\RouteMatch;
use iHMS\Library\IMessage as Request;

/**
 * IRoute interface
 *
 * Route matching against request URI path.
 *
 * Route definition examples:
 *
 * /static/route/path                       Static only
 * /static/:module/:action/:controller      Static + alnum named parameters
 * /:module/:controller/:action/#id         Alnum named parameters + numeric named parameter
 * /:module/:controller/:action/[/#id]      Alnum named parameters + optional numeric named parameter
 * /:module/:controller/:action/*id{\d+}    Alnum named parameters + regexp named parameter
 * /:module/:controller/:action/*id         Alnum named parameters + wildcard (any char except delimiter) named parameter
 *
 * Note: Any segment can be marked as optional by surrounding it with square brackets.
 *
 * @package     iHMS_Router
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @Todo allow custom delimiter to allow route such as :module-:action-:controller
 */
class NamedParameter implements IRoute
{
    /**
     * @var string Route definition as entered by user
     */
    protected $routeDefinition = null;

    /**
     * @var string Route regexp used to match against request URI
     */
    protected $regexp = null;

    /**
     * @var array Route segments
     */
    protected $segments = null;

    /**
     * @var array Default parameters
     */
    protected $defaultParameters = array();

    /**
     * @var array Map of named parameters
     */
    protected $namedParameterMap = array();

    /**
     * Constructor
     *
     * @param string $routeDefinition Route definition
     * @param array $defaultParameters
     */
    public function __construct($routeDefinition, array $defaultParameters = array())
    {
        $this->routeDefinition = trim((string)$routeDefinition, '/');
        $this->defaultParameters = $defaultParameters;
    }

    /**
     * Factory
     *
     * @throws \InvalidArgumentException in case route option is not provided
     * @param array $options Route options
     * @return NamedParameter
     */
    public static function factory(array $options)
    {
        if (!isset($options['route'])) {
            throw new \InvalidArgumentException(sprintf('%s(): Missing "route" option', __METHOD__));
        }

        if (!isset($options['default_parameters'])) {
            $options['default_parameters'] = array();
        }

        return new static($options['route'], $options['default_parameters']);
    }

    /**
     * Match the route against the given request URI path
     *
     * @param Request $request
     * @return RouteMatch|null
     */
    public function match(Request $request)
    {
        /** @var $request \iHMS\Http\Request */
        $path = strtok(trim($request->getRequestUri(), '/'), '?');

        if (preg_match('(^' . $this->getRegexp() . '$)', $path, $matches)) {
            $parameters = array();
            foreach ($this->namedParameterMap as $index => $name) {
                if (isset($matches[$index]) && $matches[$index] !== '') {
                    $parameters[$name] = urldecode($matches[$index]);
                }
            }

            return new RouteMatch(array_merge($this->defaultParameters, $parameters));
        }

        return null;
    }

    /**
     * Returns the route regexp used to macth against request uri
     *
     * @return string
     */
    protected  function getRegexp()
    {
        if (null === $this->regexp) {
            $this->regexp = $this->buildRegexp($this->getSegments());
        }

        return $this->regexp;
    }

    /**
     * Build route segments from route definition
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function getSegments()
    {
        if (null === $this->segments) {
            $routeDefinition = $this->routeDefinition;
            $position = 0;
            $length = strlen($routeDefinition);
            $segments = array();
            $levelSegments = array(&$segments);
            $level = 0;

            while ($position < $length) {
                preg_match('(\G(?P<static>[^:#*\[\]]*)(?P<token>[:#*\[\]]|$))', $routeDefinition, $matches, 0, $position);
                $position += strlen($matches[0]);

                if (!empty($matches['static'])) {
                    $levelSegments[$level][] = array('static', $matches['static']);
                }

                if (in_array($matches['token'], array(':', '#', '*'))) {
                    $type = $matches['token'];

                    if (!preg_match('(\G(?P<dynamic>[^:#*/\[\]\{\}]+)(?:\{(?P<regexp>(:?.+))\})?)', $routeDefinition, $matches, 0, $position)) {
                        throw new \RuntimeException(sprintf('%s(): Bad route definition: %s', __METHOD__, $routeDefinition));
                    }

                    if (!empty($matches['regexp'])) {
                        $levelSegments[$level][] = array('regexp', $matches['dynamic'], $matches['regexp']);
                    } else {
                        $levelSegments[$level][] = array($type, $matches['dynamic']);
                    }

                    $position += strlen($matches[0]);
                } elseif ($matches['token'] === '[') {
                    $levelSegments[$level][] = array('optional', array());
                    $levelSegments[$level + 1] = &$levelSegments[$level][count($levelSegments[$level]) - 1][1];
                    $level++;
                } elseif ($matches['token'] === ']') {
                    unset($levelSegments[$level]);
                    $level--;

                    if ($level < 0) {
                        throw new \RuntimeException(sprintf('%s(): Found closing bracket without matching opening bracket', __METHOD__));
                    }
                } else {
                    break;
                }
            }

            if ($level > 0) {
                throw new \RuntimeException('Found unbalanced brackets');
            }

            $this->segments = $segments;
        }

        return $this->segments;
    }

    /**
     * Build route regexp used to match against request URI path
     *
     * @throws \RuntimeException in case of invalid segment type
     * @param array $segments Route segments
     * @param int $groupIndex Group index
     * @return string Route regexp
     */
    protected function buildRegexp($segments, &$groupIndex = 1)
    {
        $regex = '';
        foreach ($segments as $segment) {
            switch ($segment[0]) {
                case 'static':
                    $regex .= preg_quote($segment[1]);
                    break;
                case ':': // alnum constraint
                    $groupName = '?P<param' . $groupIndex . '>';
                    $regex .= '(' . $groupName . '[-\d\w]+)';
                    $this->namedParameterMap['param' . $groupIndex++] = $segment[1];
                    break;
                case '#': // numeric constraint
                    $groupName = '?P<param' . $groupIndex . '>';
                    $regex .= '(' . $groupName . '\d+)';
                    $this->namedParameterMap['param' . $groupIndex++] = $segment[1];
                    break;
                case '*': // wildcard constraint (all except delimiter)
                    $groupName = '?P<param' . $groupIndex . '>';
                    $regex .= '(' . $groupName . '[^/]+)';
                    $this->namedParameterMap['param' . $groupIndex++] = $segment[1];
                    break;
                case 'regexp': // regexp constraint
                    $groupName = '?P<param' . $groupIndex . '>';
                    $regex .= '(' . $groupName . $segment[2] . ')';
                    $this->namedParameterMap['param' . $groupIndex++] = $segment[1];
                    break;
                case 'optional':
                    $regex .= '(?:' . $this->buildRegexp($segment[1], $groupIndex) . ')?';
                    break;
                default:
                    throw new \RuntimeException(sprintf('%s(): Unknown named parameter type', __METHOD__));
            }
        }

        return $regex;
    }
}
