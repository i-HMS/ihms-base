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

namespace iHMS\Http\Header;

use iHMS\Http\HeaderCollection;

/**
 * Header class
 *
 * class describing an HTTP header (see rfc 2616 section 4.2)
 *
 * @package     iHMS_Http
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class Header implements IHeader
{
    /**
     * @var string Header field name
     */
    protected $fieldName;

    /**
     * @var string Header field value
     */
    protected $fieldValue;

    /**
     * Constructor
     *
     * @param null $fieldName Header field name
     * @param null $fieldValue Header field value
     */
    public function __construct($fieldName = null, $fieldValue = null)
    {
        if ($fieldName) {
            $this->setFieldName($fieldName);
        }

        if ($fieldValue) {
            $this->setFieldValue($fieldValue);
        }
    }

    /**
     * Set header field name
     *
     * @throws \InvalidArgumentException
     * @param string $fieldName Header field name
     * @return Header
     */
    public function setFieldName($fieldName)
    {
        if (!is_string($fieldName) || empty($fieldName)) {
            throw new \InvalidArgumentException(sprintf('%s(): Header name must be a non-empty string', __METHOD__));
        }

        $fieldName = static::normalizeFieldName($fieldName);

        // Validate header name
        if (!preg_match('/^[a-z][a-z0-9-]*$/i', $fieldName)) {
            throw new \InvalidArgumentException(
                sprintf('%s(): Header name must start with a letter, and consist of only letters, numbers, and dashes', __METHOD__)
            );
        }

        $this->fieldName = $fieldName;

        return $this;
    }

    /**
     * Returns header field name
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Set header field value
     *
     * @param string $fieldValue Header field value
     * @return Header
     */
    public function setFieldValue($fieldValue)
    {
        $fieldValue = (string)$fieldValue;

        if (empty($fieldValue) || preg_match('/^\s+$/', $fieldValue)) {
            $fieldValue = '';
        }

        $this->fieldValue = $fieldValue;

        return $this;
    }

    /**
     * Returns header field value
     *
     * @return string
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * Allow PHP casting - returns string representation of HTTP header
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getFieldName() . ': ' . $this->getFieldValue();
    }

    /**
     * Normalize the given header field name
     *
     * @static
     * @param string $fieldName Header field name
     * @return mixed|string
     */
    public static function normalizeFieldName($fieldName)
    {
        $fieldName = str_replace(array('-', '_'), ' ', strtolower($fieldName));

        switch ($fieldName) {
            case 'content md5':
                $fieldName = 'Content-MD5';
                break;
            case 'te':
                $fieldName = 'TE';
                break;
            case 'www authenticate':
                $fieldName = 'WWW-Authenticate';
                break;
            default:
                $fieldName = str_replace(' ', '-', ucwords($fieldName));
        }

        return $fieldName;
    }
}
