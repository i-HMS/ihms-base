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
 * @package     iHMS_ModuleHandler
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\ModuleHandler;

use iHMS\EventDispatcher\Event;
use iHMS\ModuleHandler\ABootstrapper as Bootstrapper;

/**
 * ModuleEvent class
 *
 * @package     iHMS_ModuleHandler
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class ModuleEvent extends Event
{
    /**#@+
     * Module events triggered by the event dispatcher
     */
    const BEFORE_LOAD_MODULES_EVENT = 'beforeLoadModules';
    const LOAD_MODULES_EVENT = 'loadModules';
    const AFTER_LOAD_MODULES_EVENT = 'afterLoadModules';
    const RESOLVE_MODULE_EVENT = 'resolveModule';
    const BEFORE_LOAD_MODULE_EVENT = 'beforeLoadModule';
    const LOAD_MODULE_EVENT = 'loadModule';
    const AFTER_LOAD_MODULE_EVENT = 'afterLoadModule';
    /**#@-*/

    /**
     * @var string Module name
     */
    protected $moduleName;

    /**
     * @var Bootstrapper
     */
    protected $moduleBootstrapper;

    /**
     * Set module name
     *
     * @param string $name Module name
     * @return ModuleEvent
     */
    public function setModuleName($name)
    {
        $this->moduleName = $name;

        return $this;
    }

    /**
     * Returns module name
     *
     * @return string Module name
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * Set module bootstrapper
     *
     * @param Bootstrapper $bootstrapper
     * @return ModuleEvent
     */
    public function setModuleBootstrapper(ABootstrapper $bootstrapper)
    {
        $this->moduleBootstrapper = $bootstrapper;

        return $this;
    }

    /**
     * Returns module bootstrapper
     *
     * @return ABootstrapper
     */
    public function getModuleBootstrapper()
    {
        return $this->moduleBootstrapper;
    }
}
