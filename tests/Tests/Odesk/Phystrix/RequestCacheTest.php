<?php
/**
 * This file is a part of the Phystrix library
 *
 * Copyright 2013-2014 oDesk Corporation. All Rights Reserved.
 *
 * This file is licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Tests\Odesk\Phystrix;

use Odesk\Phystrix\RequestCache;
use PHPUnit\Framework\TestCase;

class RequestCacheTest extends TestCase
{
    protected RequestCache $requestCache;

    protected function setUp(): void
    {
        $this->requestCache = new RequestCache();
    }

    public function testGetAndPut()
    {
        $result = (object) array('a' => 123);
        $this->assertNull($this->requestCache->get('TestCommand', 'cache-key-123'));
        $this->requestCache->put('TestCommand', 'cache-key-123', $result);
        $this->assertEquals($result, $this->requestCache->get('TestCommand', 'cache-key-123'));
    }

    public function testClear()
    {
        $result = (object) array('a' => 123);
        $this->requestCache->put('TestCommand', 'cache-key-123', $result);
        $this->assertEquals($result, $this->requestCache->get('TestCommand', 'cache-key-123'));
        $this->requestCache->clear('TestCommand', 'cache-key-123');
        $this->assertNull($this->requestCache->get('TestCommand', 'cache-key-123'));
    }

    public function testClearAll()
    {
        $result = (object) array('a' => 123);
        $this->requestCache->put('TestCommand', 'cache-key-123', $result);
        $this->assertEquals($result, $this->requestCache->get('TestCommand', 'cache-key-123'));
        $this->requestCache->clearAll('TestCommand');
        $this->assertNull($this->requestCache->get('TestCommand', 'cache-key-123'));
    }

    public function testExists()
    {
        $result = (object) array('a' => 123);
        $this->assertFalse($this->requestCache->exists('TestCommand', 'cache-key-123'));
        $this->requestCache->put('TestCommand', 'cache-key-123', $result);
        $this->assertTrue($this->requestCache->exists('TestCommand', 'cache-key-123'));
    }
}
