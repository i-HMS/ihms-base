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
 * @package     iHMS_Config
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        https://github.com/i-HMS
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iHMS\Config;

/**
 * Config class
 *
 * @package     iHMS_Config
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class Config implements \ArrayAccess, \countable, \Iterator
{
    /**
     * @var Config[]|array Config data
     */
    protected $data = array();

    /**
     * @var bool Is readonly config object
     */
    protected $readOnly = false;

    /**
     * @var int Number of items
     */
    protected $count = 0;

    /**
     * @var int Current position
     */
    protected $position = 0;

    /**
     * @var bool Whether or not next iteration must be skipped (Used when unsetting values during iteration)
     */
    public $skipNextIteration = false;

    /**
     * Constructor
     *
     * @param array $array Array that holds configuration data
     * @param bool $readOnly Whether the config object is readonly
     */
    public function __construct(array $array = array(), $readOnly = false)
    {
        $this->readOnly = (bool)$readOnly;

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->data[$key] = new self($value, $this->readOnly);
            } else {
                $this->data[$key] = $value;
            }
        }

        $this->count = count($this->data);
    }

    /**
     * Returns value of the given item or default value if item is not found
     *
     * @param string $itemName Item name to return
     * @param mixed $defaultValue Default value returned in case $itemName is not found
     * @return mixed
     */
    public function get($itemName, $defaultValue = null)
    {
        $data = $this->data;

        if (strpos($itemName, '.') !== false) { // Find value to return
            $dataValue = $data;
            $valueParts = explode('.', $itemName);

            foreach ($valueParts as $valuePart) {
                if (isset($dataValue[$valuePart])) {
                    $dataValue = $dataValue[$valuePart];
                } else {
                    $dataValue = $defaultValue;
                    break;
                }
            }
        } else {
            $dataValue = $data;

            if (isset($dataValue[$itemName])) {
                $dataValue = $dataValue[$itemName];
            } else {
                $dataValue = $defaultValue;
            }
        }

        return $dataValue;
    }

    /**
     * Set value of the given item if config object is not readonly
     *
     * @throws \InvalidArgumentException in case $itemName is not a string
     * @throws \RuntimeException in case config object is readonly
     * @param string $itemName Item name
     * @param string $value Item value
     * return void
     */
    public function set($itemName, $value)
    {
        if (!$this->readOnly) {
            if (is_string($itemName)) {
                $this->data[$itemName] = $value;
                $this->count++;
            } else {
                throw new \InvalidArgumentException(
                    sprintf('%s(): string expected for configuration item name; received %s', __METHOD__, gettype($itemName))
                );
            }
        } else {
            throw new \RuntimeException(
                sprintf("%s(): Cannot set value of the '%s' configuration item; config object is read only", __METHOD__, $itemName)
            );
        }
    }

    /**
     * Returns an associative array representing this config object
     *
     * @return array
     */
    public function toArray()
    {
        $data = array();

        foreach ($this->data as $key => $value) {
            /** @var $value Config */
            if ($value instanceof Config) {
                $data[$key] = $value->toArray();
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Mark the config object as readonly
     *
     * @return Config
     */
    public function setReadOnly()
    {
        $this->readOnly = true;

        return $this;
    }

    /**
     * Is readonly config object
     */
    public function isReadonly()
    {
        return $this->readOnly;
    }

    /**
     * Merge another config object with this one.
     *
     * @param Config $config
     * @return Config
     */
    public function merge(Config $config)
    {
        /** @var $item Config|mixed */
        foreach ($config as $key => $item) {
            if (array_key_exists($key, $this->data)) {
                if ($item instanceof Config && $this->data[$key] instanceof Config) {
                    $this->data[$key] = $this->data[$key]->merge(new Config($item->toArray(), !$this->isReadonly()));
                } else {
                    $this->data[$key] = $item;
                }
            } else {
                if ($item instanceof Config) {
                    $this->data[$key] = new Config($item->toArray(), !$this->isReadonly());
                } else {
                    $this->data[$key] = $item;
                }
            }
        }

        return $this;
    }

    /**
     * Defined by ArrayAccess interface - Whether an offset exists
     *
     * @param mixed $offset Offset to check for
     * @return boolean TRUE if $offset exists, FALSE otherwise
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Defined by ArrayAccess interface - Offset to retrieve
     *
     * @param mixed $offset The offset to retrieve.
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * Defined by ArrayAccess interface - Offset to set
     *
     * @param mixed $offset The offset to assign the value to
     * @param mixed $value The value to set.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
        $this->count++;
    }

    /**
     * Defined by ArrayAccess interface - Offset to unset
     *
     * @param mixed $offset The offset to unset.
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
        $this->count--;
    }

    /**
     * Defined by Countable interface - Count elements of an object
     *
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * Defined by Iterator interface - Return the current element
     *
     * @return mixed
     */
    public function current()
    {
        $this->skipNextIteration = false;
        return current($this->data);
    }

    /**
     * Defined by Iterator interface - Move forward to next element
     *
     * @return void
     */
    public function next()
    {
        if ($this->skipNextIteration) {
            $this->skipNextIteration = false;
        } else {
            next($this->data);
            $this->position++;
        }
    }

    /**
     * Defined by Iterator interface - Return the key of the current element
     *
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * Defined by Iterator interface - Checks if current position is valid
     *
     * @return boolean TRUE if current position is valid, FALSE otherwise
     */
    public function valid()
    {
        return $this->position < $this->count;
    }

    /**
     * Defined by Iterator interface - Rewind the Iterator to the first element
     *
     * @return void
     */
    public function rewind()
    {
        $this->skipNextIteration = false;
        reset($this->data);
        $this->position = 0;
    }

    /**
     * Implements isset()
     *
     * @param string $itemName Item name
     * @return boolean
     */
    public function __isset($itemName)
    {
        return isset($this->data[$itemName]);
    }

    /**
     * Implements unset() overloading
     *
     * @throws \RuntimeException when config object is readonly
     * @param  string $itemName Item name
     * @return void
     */
    public function __unset($itemName)
    {
        if (!$this->readOnly) {
            unset($this->data[$itemName]);
            $this->count = count($this->data);
            $this->skipNextIteration = true;
        } else {
            throw new \RuntimeException(
                sprintf('%s:() Cannot unset %s configuration item; Config object is read only', __METHOD__, $itemName)
            );
        }
    }
}
