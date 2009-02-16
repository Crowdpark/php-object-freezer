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
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since      File available since Release 1.0.0
 */

require_once 'PHPUnit/Framework.php';
require_once 'Object/Freezer/Storage/CouchDB.php';

/**
 * Tests for the Object_Freezer_Storage_CouchDB class.
 *
 * @package    Object_Freezer
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-object-freezer/
 * @since      Class available since Release 1.0.0
 */
class Object_Freezer_Storage_CouchDB_WithLazyLoadTest extends PHPUnit_Framework_TestCase
{
    protected $freezer;
    protected $storage;

    /**
     * @covers Object_Freezer_Storage_CouchDB::__construct
     */
    protected function setUp()
    {
        if (!@fsockopen(OBJECT_FREEZER_COUCHDB_HOST, OBJECT_FREEZER_COUCHDB_PORT, $errno, $errstr)) {
            $this->markTestSkipped(
              sprintf(
                'CouchDB not running on %s:%d.',
                OBJECT_FREEZER_COUCHDB_HOST,
                OBJECT_FREEZER_COUCHDB_PORT
              )
            );
        }

        $idGenerator = $this->getMock('Object_Freezer_IdGenerator');
        $idGenerator->expects($this->any())
                    ->method('getId')
                    ->will($this->onConsecutiveCalls('a', 'b', 'c'));

        $this->freezer = new Object_Freezer($idGenerator);

        $this->storage = new Object_Freezer_Storage_CouchDB(
          'test',
          $this->freezer,
          TRUE,
          OBJECT_FREEZER_COUCHDB_HOST,
          (int)OBJECT_FREEZER_COUCHDB_PORT
        );

        $this->storage->send('PUT', '/test');
    }

    protected function tearDown()
    {
        $this->storage->send('DELETE', '/test/');
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__get
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsWorks()
    {
        $object = new C;
        $this->storage->store($object);

        $fetchedObject = $this->storage->fetch('a');

        $this->assertEquals($object->b->a->a, $fetchedObject->b->a->a);
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__call
     * @covers Object_Freezer_LazyProxy::replaceProxy
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsWorks2()
    {
        $object = new B;
        $this->storage->store($object);

        $fetchedObject = $this->storage->fetch('a');

        $this->assertEquals($object->getValuesOfA(), $fetchedObject->getValuesOfA());
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__set
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsWorks3()
    {
        $object = new C;
        $this->storage->store($object);

        $fetchedObject = $this->storage->fetch('a');

        $object->b->a->a        = 2;
        $fetchedObject->b->a->a = 2;

        $this->assertEquals($object->b->a->a, $fetchedObject->b->a->a);
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__call
     * @covers Object_Freezer_LazyProxy::replaceProxy
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsWorks4()
    {
        $object = new C;
        $this->storage->store($object);

        $fetchedObject = $this->storage->fetch('a');

        $this->assertEquals($object->b->a->getValues(), $fetchedObject->b->a->getValues());
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__get
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage::fetchArray
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInAnArrayWorks()
    {
        $object = new D;
        $this->storage->store($object);

        $fetchedObject = $this->storage->fetch('a');

        $this->assertEquals($object->array[0]->a, $fetchedObject->array[0]->a);
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__set
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage::fetchArray
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInAnArrayWorks2()
    {
        $object = new D;
        $this->storage->store($object);

        $fetchedObject = $this->storage->fetch('a');

        $object->array[0]->a        = 2;
        $fetchedObject->array[0]->a = 2;

        $this->assertEquals($object->array[0]->a, $fetchedObject->array[0]->a);
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__call
     * @covers Object_Freezer_LazyProxy::replaceProxy
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage::fetchArray
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInAnArrayWorks3()
    {
        $object = new D;
        $this->storage->store($object);

        $fetchedObject = $this->storage->fetch('a');

        $this->assertEquals($object->array[0]->getValues(), $fetchedObject->array[0]->getValues());
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__get
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage::fetchArray
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks()
    {
        $object = new E;
        $this->storage->store($object);

        $fetchedObject = $this->storage->fetch('a');

        $this->assertEquals($object->array['array'][0]->a, $fetchedObject->array['array'][0]->a);
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__set
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage::fetchArray
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks2()
    {
        $object = new E;
        $this->storage->store($object);

        $fetchedObject = $this->storage->fetch('a');

        $object->array['array'][0]->a        = 2;
        $fetchedObject->array['array'][0]->a = 2;

        $this->assertEquals($object->array['array'][0]->a, $fetchedObject->array['array'][0]->a);
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__call
     * @covers Object_Freezer_LazyProxy::replaceProxy
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage::fetchArray
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks3()
    {
        $object = new E;
        $this->storage->store($object);

        $fetchedObject = $this->storage->fetch('a');

        $this->assertEquals($object->array['array'][0]->getValues(), $fetchedObject->array['array'][0]->getValues());
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__get
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectGraphThatContainsCyclesWorks()
    {
        $root                 = new Node;
        $root->left           = new Node;
        $root->right          = new Node;
        $root->left->parent   = $root;
        $root->right->parent  = $root;
        $root->payload        = 'a';
        $root->left->payload  = 'b';
        $root->right->payload = 'c';

        $this->storage->store($root);

        $fetchedObject = $this->storage->fetch('a');

        $this->assertEquals($root->payload, $fetchedObject->payload);
        $this->assertEquals($root->left->payload, $fetchedObject->left->payload);
        $this->assertEquals($root->right->payload, $fetchedObject->right->payload);
    }

    /**
     * @covers Object_Freezer_LazyProxy::__construct
     * @covers Object_Freezer_LazyProxy::getObject
     * @covers Object_Freezer_LazyProxy::__get
     * @covers Object_Freezer_Storage::store
     * @covers Object_Freezer_Storage::fetch
     * @covers Object_Freezer_Storage::fetchArray
     * @covers Object_Freezer_Storage_CouchDB::doStore
     * @covers Object_Freezer_Storage_CouchDB::doFetch
     */
    public function testStoringAndFetchingAnObjectGraphThatContainsCyclesWorks2()
    {
        $root   = new Node2('a');
        $left   = new Node2('b', $root);
        $parent = new Node2('c', $root);

        $this->storage->store($root);

        $fetchedObject = $this->storage->fetch('a');

        $this->assertEquals($root->payload, $fetchedObject->payload);
        $this->assertEquals($root->children[0]->payload, $fetchedObject->children[0]->payload);
        $this->assertEquals($root->children[1]->payload, $fetchedObject->children[1]->payload);
    }

    protected function getFrozenObjectFromStorage($id)
    {
        $buffer = $this->storage->send('GET', '/test/' . $id);
        $buffer = $buffer['body'];

        $frozenObject = json_decode($buffer, TRUE);
        unset($frozenObject['_rev']);

        return $frozenObject;
    }
}

