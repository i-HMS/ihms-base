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
 * @package     iHMS_Loader
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\Loader;

/**
 * AutoloaderFactory class
 *
 * Class allowing to create/retrieve autoloader class implementing the iHMS\Loader\ISplAutoloader interface. This class
 * also acts as an autoloader registry.
 *
 * @package     iHMS_Loader
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class AutoloaderFactory
{
    /**
     * @const string Default loader
     */
    const DEFAULT_LOADER = 'iHMS\Loader\UniversalLoader';

    /**
     * @var ISplAutoloader[] All loaders registered using the factory
     */
    protected static $loaders = array();

    /**
     * @var string Default loader instance
     */
    protected static $defaultLoader = null;

    /**
     * Factory for autoloaders
     *
     * Expects an array with the following structure:
     *
     * <code>
     * array(
     *     '<autoloader class name>' => $autoloaderOptions,
     * )
     * </code>
     *
     * The factory will then loop through and instantiate each autoloader with the specified options, and register each
     * with the spl_autoloader. If no options is passed in, the default autoloader will be registered. Also if an
     * autoloader is already instantiated, options will be added to it.
     *
     * Note that the class names must be resolvable on the include_path or via the iHMS library, using PSR-0 rules
     * (unless the class has already been loaded).
     *
     * @throws \InvalidArgumentException
     * @param array $options Pairs of autoloader_class_name/autoloader_options
     * @return void
     */
    public static function factory(array $options = null)
    {
        if (null !== $options) {
            foreach ($options as $class => $loaderOptions) {
                if (!isset(static::$loaders[$class])) { // Autoloader not already instantiated
                    $loader = static::getDefaultLoader();

                    // Trying to load the given autoloader with default autoloader
                    if (!class_exists($class) && !$loader->autoload($class)) {
                        throw new \InvalidArgumentException(
                            sprintf('%s(): Unable to load the autoloader class "%s"', __METHOD__, $class)
                        );
                    }

                    if ($class === static::DEFAULT_LOADER) {
                        $loader->setOptions($loaderOptions);
                    } else {
                        $loader = new $class($loaderOptions);

                        if (!$loader instanceof ISplAutoloader) {
                            throw new \InvalidArgumentException(
                                sprintf('%s(): autoloader class %s must implement the iHMS\Loader\ISplAutoloader interface', __METHOD__, $class)
                            );
                        }
                    }

                    $loader->register(); // Register the loader on the spl autoloader registry
                    static::$loaders[$class] = $loader;
                } else { // Autoloader instance already there, we are so simply add options for it
                    static::$loaders[$class]->setOptions($loaderOptions);
                }
            }
        } elseif (!isset(static::$loaders[static::DEFAULT_LOADER])) {
            // No options passed in, we so create default autoloader instance
            $loader = static::getDefaultLoader();
            $loader->register();
            static::$loaders[static::DEFAULT_LOADER] = $loader;
        }
    }

    /**
     * Return instance of given autoloader or false if not found
     *
     * @param string $classname Autoloader classname
     * @return bool|ISplAutoloader
     */
    public static function getAutoloader($classname)
    {
        if(isset(static::$loaders[$classname])) {
            return static::$loaders[$classname];
        }

        return false;
    }

    /**
     * Create and returns instance of default loader
     *
     * @return ISplAutoloader
     */
    protected static function getDefaultLoader()
    {
        if (null === static::$defaultLoader) {
            if (!class_exists(static::DEFAULT_LOADER)) {
                // Retrieves filename from the classname
                $defaultLoader = substr(strrchr(static::DEFAULT_LOADER, '\\'), 1);
                require_once __DIR__ . "/$defaultLoader.php";
            }

            static::$defaultLoader = new UniversalLoader();
        }

        return static::$defaultLoader;
    }
}
