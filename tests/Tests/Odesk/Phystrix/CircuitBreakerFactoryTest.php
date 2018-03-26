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

use Odesk\Phystrix\CircuitBreakerFactory;
use Odesk\Phystrix\CommandMetrics;
use Odesk\Phystrix\Configuration\PhystrixCommandConfiguration;
use PHPUnit_Framework_MockObject_MockObject;

class CircuitBreakerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CircuitBreakerFactory
     */
    protected $factory;

    /**
     * @var CommandMetrics
     */
    protected $metrics;

    protected function setUp()
    {
        $this->factory = new CircuitBreakerFactory($this->getMock('Odesk\Phystrix\StateStorageInterface'));
        $this->metrics = $this->getMock('Odesk\Phystrix\CommandMetrics', array(), array(), '', false);
    }

    public function testGetNoOpCircuitBreaker()
    {
        $circuitBreaker = $this->factory->get('TestCommand', $this->getConfig(false), $this->metrics);
        $this->assertInstanceOf('Odesk\Phystrix\NoOpCircuitBreaker', $circuitBreaker);
    }

    public function testGetInstantiatesOnce()
    {
        // this will be a NoOpCircuitBreaker
        $circuitBreaker = $this->factory->get('TestCommand', $this->getConfig(false), $this->metrics);
        // now trying to get the same circuit breaker with a different config
        $circuitBreakerB
            = $this->factory->get('TestCommand', $this->getConfig(), $this->metrics);
        $this->assertEquals($circuitBreaker, $circuitBreakerB);
    }

    public function testGetInjectsParameters()
    {
        $config = $this->getConfig();
        $circuitBreaker = $this->factory->get('TestCommand', $config, $this->metrics);
        $this->assertAttributeEquals('TestCommand', 'commandKey', $circuitBreaker);
        $this->assertAttributeEquals($config, 'circuitBreakerConfig', $circuitBreaker);
        $this->assertAttributeInstanceOf('Odesk\Phystrix\CommandMetrics', 'metrics', $circuitBreaker);
        $this->assertAttributeInstanceOf('Odesk\Phystrix\StateStorageInterface', 'stateStorage', $circuitBreaker);
    }

    /**
     * @param bool $isEnabled
     * @return PHPUnit_Framework_MockObject_MockObject|PhystrixCommandConfiguration
     */
    private function getConfig($isEnabled = true)
    {
        $config = $this->getMockBuilder('Odesk\Phystrix\Configuration\PhystrixCommandConfiguration')
            ->disableOriginalConstructor()
            ->setMethods(array(
                'isCircuitBreakerEnabled'
            ))
            ->getMock();

        $config->method('isCircuitBreakerEnabled')->willReturn($isEnabled);

        return $config;
    }
}
