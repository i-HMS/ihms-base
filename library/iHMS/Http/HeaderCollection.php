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
 * HeaderCollection class
 *
 * Class representing an HTTP header collection.
 *
 * @package     iHMS_Http
 * @copyright   2012 by iHMS Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class HeaderCollection implements \Iterator, \Countable
{
    /**
     * @var array|IHeader[]
     */
    protected $headers = array();

    /**
     * @var array Header field names
     */
    protected $headerFieldNames = array();

    /**
     * Constructor
     *
     * @param null|array|HeaderCollection $headers An array of header fieldName/fieldValue(s) pair(s) and/or IHeader
     *                                             object(s) or an HeaderCollection object
     */
    public function __construct($headers = null)
    {
        if (null !== $headers) {
            $this->addHeaders($headers);
        }
    }

    /**
     * Add the given header to the header collection
     *
     * @param IHeader $header
     * @return HeaderCollection
     */
    public function addHeader(IHeader $header)
    {
        $this->headers[] = $header;
        $this->headerFieldNames[] = $this->_normalizeFieldName($header->getFieldName());

        return $this;
    }

    /**
     * Add the given raw header to the header collection
     *
     * Note: Instantiation of the IHeader object is delayed until it's retrieved by either the getHeader() method
     * or the current() methods (eg. when iterating over the HeaderCollection).
     *
     * @throws \InvalidArgumentException in case header field name is not valid
     * @param string $fieldName Header field name
     * @param string $fieldValue Header field value
     * @return HeaderCollection
     */
    public function addRawHeader($fieldName, $fieldValue)
    {
        if (!is_string($fieldName) || empty($fieldName)) {
            throw new \InvalidArgumentException(
                sprintf('%s(): Header field name must be a non-empty string', __METHOD__)
            );
        }

        $this->headers[] = $fieldValue;
        $this->headerFieldNames[] = $this->_normalizeFieldName($fieldName);

        return $this;
    }

    /**
     * Add several headers at once
     *
     * @throws \InvalidArgumentException in case $headers in not an array nor an HeaderCollection object
     * @param array|HeaderCollection $headers An array of header fieldName/fieldValue(s) pair(s) and/or IHeader object(s)
     *                                        or an HeaderCollection object
     * @return HeaderCollection
     */
    public function addHeaders($headers)
    {
        if ($headers instanceof HeaderCollection) {
            $headers = $headers->toArray();
        } elseif (!is_array($headers)) {
            throw new \InvalidArgumentException(
                sprintf('%s(): expects an array or HeaderCollection object; received %s', __METHOD__, gettype($headers))
            );
        }

        foreach ($headers as $fieldName => $fieldValue) {
            if (is_scalar($fieldValue)) {
                $this->addRawHeader($fieldName, $fieldValue);
            } elseif (is_array($fieldValue)) {
                foreach ($fieldValue as $value) {
                    $this->addRawHeader($fieldName, $value);
                }
            } else {
                $this->addHeader($fieldValue);
            }
        }

        return $this;
    }

    /**
     * Returns all headers of same type
     *
     * @param string $headerName Header name
     * @return bool|HeaderCollection|IHeader
     */
    public function getHeader($headerName)
    {
        $headerName = $this->_normalizeFieldName($headerName);

        if (($indexes = array_keys($this->headerFieldNames, $headerName))) {
            if (count($indexes) > 1) {
                $headerCollection = new static();
                foreach ($indexes as $index) {
                    if (is_scalar($this->headers[$index])) {
                        $this->headers[$index] = $this->_createHeader($index);
                    }

                    $headerCollection->addHeader($this->headers[$index]);
                }

                return $headerCollection;
            } else {
                if (is_scalar($this->headers[$indexes[0]])) {
                    $this->headers[$indexes[0]] = $this->_createHeader($indexes[0]);
                }

                return $this->headers[$indexes[0]];
            }
        }

        return false;
    }

    /**
     * Returns the first header matching one of the given header names
     *
     * Note: In case the header match to several headers of same type, a header collection is returned.
     *
     * @param array $headerNames Header names to match against
     * @return bool|IHeader|HeaderCollection
     */
    public function getFirstMatchHeader($headerNames)
    {
        $headerMatches = array_intersect(
            array_map(array($this, '_normalizeFieldName'), $headerNames), $this->headerFieldNames
        );

        if (!empty($headerMatches)) {
            return $this->getHeader(array_shift($headerMatches));
        }

        return false;
    }

    /**
     * Returns the first header matching one of the given partial header names
     *
     * Note: In case the header match to several headers of same type, a header collection is returned.
     *
     * @param array|string $partialHeaderNames Partial header name(s) to match against
     * @return bool|IHeader|HeaderCollection
     * @todo UnitTest
     */
    public function getFirstPartialMatchHeader($partialHeaderNames)
    {
        foreach ((array)$partialHeaderNames as $partialName) {
            $partialHeaderNamesMatches = array_filter(
                $this->headerFieldNames,
                function ($_) use ($partialName) {
                    return (stripos($_, $partialName) !== false);
                }
            );

            if (!empty($headerPartialNames)) {
                return $this->getHeader(array_shift($partialHeaderNamesMatches));
            }
        }

        return false;
    }

    /**
     * Remove the given header object from the header collection
     *
     * @param IHeader $header
     * @return bool TRUE if the given header was found and successfuly removed, FALSE otherwise
     */
    public function removeHeader(IHeader $header)
    {
        if (($index = array_search($header, $this->headers, true)) !== false) {
            unset($this->headers[$index], $this->headerFieldNames[$index]);
            return true;
        }

        return false;
    }

    /**
     * Remove all headers of same type
     *
     * @param string $fieldName Header field name
     * @return bool TRUE if the given header type was found and successfuly removed, FALSE otherwise
     */
    public function removeHeaderByName($fieldName)
    {
        if (($indexes = array_keys($this->headerFieldNames, $this->_normalizeFieldName($fieldName)))) {
            $indexes = array_flip($indexes);
            $this->headers = array_diff_key($this->headers, $indexes);
            $this->headerFieldNames = array_diff_key($this->headerFieldNames, $indexes);

            return true;
        }

        return false;
    }

    /**
     * Removes all headers from header collection
     *
     * @return HeaderCollection
     */
    public function removeHeaders()
    {
        $this->headers = $this->headerFieldNames = array();

        return $this;
    }

    /**
     * Header collection has the given header type?
     *
     * @param string $headerName Header field name
     * @return bool
     */
    public function hasHeader($headerName)
    {
        return (in_array($this->_normalizeFieldName($headerName), $this->headerFieldNames));
    }

    /**
     * Allow PHP casting - Returns string representation of header collection
     *
     * @return string
     */
    public function __tostring()
    {
        $string = '';

        foreach ($this->toArray() as $fieldName => $fieldValue) {
            if (is_array($fieldValue)) {
                foreach ($fieldValue as $value) {
                    $string .= $fieldName . ': ' . $value . "\r\n";
                }
                continue;
            }

            $string .= $fieldName . ': ' . $fieldValue . "\r\n";
        }

        return $string;
    }

    /**
     * Returns array representation of header collection as fieldName/fieldValue(s) pairs
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();

        foreach ($this->headers as $index => $header) {
            $headerKey = $this->headerFieldNames[$index];

            if (isset($array[$headerKey]) || count(array_keys($this->headerFieldNames, $headerKey)) > 1) {
                $array[$headerKey][] = (is_scalar($header)) ? $header : $header->getFieldValue();
                continue;
            }

            $array[$headerKey] = (is_scalar($header)) ? $header : $header->getFieldValue();
        }

        return $array;
    }

    /**
     * Implements Iterator interface - Returns the current header
     *
     * @return IHeader
     */
    public function current()
    {
        $current = current($this->headers);
        return (is_scalar($current)) ? $this->_createHeader(key($this->headers)) : $current;
    }

    /**
     * Implements Iterator interface - Move forward to next header
     *
     * @return void
     */
    public function next()
    {
        next($this->headers);
    }

    /**
     * Implements Iterator interface - Returns index of the current header
     *
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return (key($this->headers));
    }

    /**
     * Implements Iterator interface -  Checks if current position is valid
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function valid()
    {
        return (current($this->headers) !== false);
    }

    /**
     * Implements Iterator interface - Rewind the Iterator to the first header
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->headers);
    }

    /**
     * Implements Countable interface - Count headers of this header collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->headerFieldNames);
    }

    /**
     * Create and returns header object
     *
     * @param int $index index of header information
     * @return Header
     */
    protected function _createHeader($index)
    {
        return $this->headers[$index] = new Header($this->headerFieldNames[$index], $this->headers[$index]);
    }

    /**
     * Returns normalized header field name
     *
     * @param string $fieldName Header field name
     * @return string Normalized Header name
     */
    protected function _normalizeFieldName($fieldName)
    {
        return Header::normalizeFieldName($fieldName);
    }
}
