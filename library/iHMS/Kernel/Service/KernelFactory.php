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

namespace iHMS\Kernel\Service;

use iHMS\ServiceLocator\IServiceFactory;
use iHMS\ServiceLocator\IServiceLocator;
use iHMS\Kernel\Kernel;

/**
 * KernelFactory class
 *
 * @package     iHMS_Kernel
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class KernelFactory implements IServiceFactory
{
    /**
     * Create and returns kernel service instance
     *
     * @static
     * @param IServiceLocator $serviceLocator
     * @return Kernel
     */
    public static function factory(IServiceLocator $serviceLocator)
    {

        // TODO it's normally the merged configuration from all module that is passed in constructor
        // but since we are using nested Config object, we can even have only one config service
        // that will change over the working flow and be queried such as
        // $config->get('config_parameter') to retrieve parameter from main config
        // $config->get('module_merged_config.config_parameters to retrieve parameter from merged module configuration
        return new Kernel($serviceLocator->get('Config'), $serviceLocator);
    }
}
