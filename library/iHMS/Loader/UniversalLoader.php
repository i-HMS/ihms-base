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
 * @package     iHMS_
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\Loader;

/** @see iHMS\Loader\ISplAutoloader */
require_once __DIR__ . '/ISplAutoloader.php';

/**
 * UniversalLoader class
 *
 * class implementing an universal autoloader for PHP >= 5.3.
 *
 * This autoloader is able to load classes that use either:
 *
 * * The technical interoperability standards for PHP 5.3 namespaces and
 *    class names (http://groups.google.com/group/php-standards/web/psr-0-final-proposal);
 *
 *  * The PEAR naming convention for classes (http://pear.php.net/).
 *
 * @package     iHMS_Loader
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class UniversalLoader implements ISplAutoloader
{
    /**
     * @var array class prefixes
     */
    protected $prefixes = array();

    /**
     * @var array Pairs of class to filename map
     */
    protected $classMap = array();

    /**
     * @var bool flag indicating whether searching the include path should be enabled (eg. for PEAR packages)
     */
    protected $useIncludePath = false;

    /**
     * Set autoloader options
     *
     * @throws \InvalidArgumentException in case invalid option is provided
     * @param array $options Autoloader options
     * @return UniversalLoader
     */
    public function setOptions(array $options)
    {
        foreach ($options as $option => $value) {
            switch ($option) {
                case 'prefixes':
                case 'namespaces':
                    foreach ($value as $k => $v) {
                        $this->add($k, $v);
                    }
                    break;
                case 'classMap':
                    $this->addClassMap($value);
                    break;
                case 'useIncludePath':
                    $this->setUseIncludePath($value);
                    break;
                default:
                    throw new \InvalidArgumentException(
                        sprintf('%s(): Invalid autoloader option "%s"', __METHOD__, $options)
                    );
            }
        }

        return $this;
    }

    /**
     * Register a namespace or prefix
     *
     * @param string $prefix The class prefix
     * @param array|string $path The location(s) of the class
     * @return UniversalLoader
     */
    public function add($prefix, $path)
    {
        // If namespace has already been specified, add path to array of possible paths -- don't overwrite
        if (isset($this->prefixes[$prefix])) {
            $path = array_merge($this->prefixes[$prefix], (array)$path);
        }

        $this->prefixes[$prefix] = (array)$path;

        return $this;
    }

    /**
     * Returns all registered prefixes
     *
     * @return array
     */
    public function getPrefixes()
    {
        return $this->prefixes;
    }

    /**
     * Add class map
     *
     * @param array $classMap Pairs of class to filename map
     */
    public function addClassMap(array $classMap)
    {
        if ($this->classMap) {
            $this->classMap = array_merge($this->classMap, $classMap);
        } else {
            $this->classMap = $classMap;
        }
    }

    /**
     * Returns class map
     *
     * @return array
     */
    public function getClassMap()
    {
        return $this->classMap;
    }

    /**
     * Set value of flag indicating whether searching the include path should be enabled
     *
     * @param bool $flag
     * @return UniversalLoader
     */
    public function setUseIncludePath($flag)
    {
        $this->useIncludePath = (bool)$flag;
    }

    /**
     * Returns value of flag indicating whether searching the include path should be enabled
     *
     * @return bool
     */
    public function getUseInludePath()
    {
        return $this->useIncludePath;
    }

    /**
     * Register the autoloader on the spl_autoload registry
     *
     * @return bool true on success or false on failure
     */
    public function register()
    {
        return spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Un-register the autoloader from the spl_autoload registry
     *
     * @return bool true on success or false on failure
     */
    public function unregister()
    {
        return spl_autoload_unregister(array($this, 'autoload'));
    }

    /**
     * Try to autoload the given class
     *
     * @param string $class Class name
     * @return bool true on success, false otherwise
     */
    public function autoload($class)
    {
        $file = false;

        if (isset($this->classMap[$class])) {
            $file = $this->classMap[$class];
        } else {
            if (false !== $pos = strrpos($class, '\\')) { // namespaced class name
                $classPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 0, $pos)) . DIRECTORY_SEPARATOR;
                $className = substr($class, $pos + 1);
            } else { // PEAR-like class name
                $classPath = null;
                $className = $class;
            }

            $classPath .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

            foreach ($this->prefixes as $prefix => $dirs) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($dirs as $dir) {
                        if (file_exists($dir . DIRECTORY_SEPARATOR . $classPath)) {
                            $file = $dir . DIRECTORY_SEPARATOR . $classPath;
                        }
                    }
                }
            }

            if ($this->useIncludePath) {
                $file = stream_resolve_include_path($classPath);
            }

            $this->classMap[$class] = false;
        }

        if ($file) {
            include $file;
            return true;
        }

        return false;
    }
}
