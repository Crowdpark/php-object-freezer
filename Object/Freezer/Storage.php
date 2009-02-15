<?php
/**
 * Object_Freezer
 *
 * Copyright (c) 2008-2009, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Object_Freezer
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since      File available since Release 1.0.0
 */

require_once 'Object/Freezer.php';
require_once 'Object/Freezer/LazyProxy.php';

/**
 * Abstract base class for object storage implementations.
 *
 * @package    Object_Freezer
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-object-freezer/
 * @since      Class available since Release 1.0.0
 */
abstract class Object_Freezer_Storage
{
    /**
     * @var array
     */
    protected $cache = array();

    /**
     * @var Object_Freezer
     */
    protected $freezer;

    /**
     * Constructor.
     *
     * @param Object_Freezer $freezer
     */
    public function __construct(Object_Freezer $freezer = NULL)
    {
        if ($freezer === NULL) {
            $freezer = new Object_Freezer;
        }

        $this->freezer = $freezer;
    }

    /**
     * Freezes an object and stores it in the object storage.
     *
     * @param  object $object The object that is to be stored.
     * @return string
     */
    public function store($object)
    {
        // Bail out if a non-object was passed.
        if (!is_object($object)) {
            throw Object_Freezer_Util::getInvalidArgumentException(1, 'object');
        }

        return $this->doStore($object);
    }

    /**
     * Fetches a frozen object from the object storage and thaws it.
     *
     * @param  string $id The ID of the object that is to be fetched.
     * @return object
     */
    public function fetch($id)
    {
        // Bail out if a non-string was passed.
        if (!is_string($id)) {
            throw Object_Freezer_Util::getInvalidArgumentException(1, 'string');
        }

        if (!isset($this->cache[$id])) {
            $frozenObject = $this->doFetch($id);
            $this->fetchArray($frozenObject['objects'][$id]['state']);

            $this->cache[$id] = $this->freezer->thaw($frozenObject);
        }

        return $this->cache[$id];
    }

    /**
     * Fetches a frozen array from the object storage and thaws it.
     *
     * @param array $array
     */
    protected function fetchArray(array &$array)
    {
        $keys    = array_keys($array);
        $numKeys = count($keys);

        for ($i = 0; $i < $numKeys; $i++) {
            if (is_array($array[$keys[$i]])) {
                $this->fetchArray($array[$keys[$i]]);
            }

            else if (is_string($array[$keys[$i]]) && strpos($array[$keys[$i]], '__php_object_freezer_') === 0) {
                $array[$keys[$i]] = new Object_Freezer_LazyProxy(
                  $this,
                  str_replace('__php_object_freezer_', '', $array[$keys[$i]])
                );
            }
        }
    }

    /**
     * Freezes an object and stores it in the object storage.
     *
     * @param  object $object The object that is to be stored.
     * @return string
     */
    abstract protected function doStore($object);

    /**
     * Fetches a frozen object from the object storage and thaws it.
     *
     * @param  string $id The ID of the object that is to be fetched.
     * @return object
     */
    abstract protected function doFetch($id);
}
