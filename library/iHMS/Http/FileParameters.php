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

/**
 * FileParameters class
 *
 * @package     iHMS_Http
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class FileParameters extends Parameters
{
    /**
     * Constructor
     *
     * @param array $parameters
     */
    public function __construct($parameters = array())
    {
        parent::__construct($this->mapFiles($parameters));
    }

    /**
     * Convert PHP $_FILES array style into more sane parameter=value structure
     *
     * @param array $files Files description
     * @return array
     */
    protected function mapFiles($files)
    {
        $tmpFiles = array();
        foreach ($files as $fileName => $fileParameters) {
            $tmpFiles[$fileName] = array();
            foreach ($fileParameters as $parameter => $data) {
                if (!is_array($data)) {
                    $tmpFiles[$fileName][$parameter] = $data;
                } else {
                    foreach ($data as $index => $value) {
                        $this->mapFileParameter($tmpFiles[$fileName], $parameter, $index, $value);
                    }
                }
            }
        }

        return $tmpFiles;
    }

    /**
     * Expands file parameters
     *
     * @param array &$array Reference to array of file parameters
     * @param string $parameterName Parameter name
     * @param string $index Index
     * @param array|string $value
     */
    protected function mapFileParameter(&$array, $parameterName, $index, $value)
    {
        if (!is_array($value)) {
            $array[$index][$parameterName] = $value;
        } else {
            foreach ($value as $i => $v) {
                $this->mapFileParameter($array[$index], $parameterName, $i, $v);
            }
        }
    }
}
