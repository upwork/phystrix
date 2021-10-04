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
use Odesk\Phystrix\StateStorageInterface;
use Odesk\Phystrix\NoOpCircuitBreaker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CircuitBreakerFactoryTest extends TestCase
{
    /**
     * @var MockObject|CommandMetrics
     */
    protected $metrics;
    protected CircuitBreakerFactory $factory;

    protected static array $baseConfig = [
        'circuitBreaker' => [
            'enabled' => true,
        ]
    ];

    protected function setUp(): void
    {
        $this->factory = new CircuitBreakerFactory($this->createMock(StateStorageInterface::class));
        $this->metrics = $this->createMock(CommandMetrics::class);
    }

    public function testGetNoOpCircuitBreaker(): void
    {
        $config = self::$baseConfig;
        $config['circuitBreaker']['enabled'] = false;
        $config = new \Laminas\Config\Config($config);
        $circuitBreaker = $this->factory->get('TestCommand', $config, $this->metrics);
        $this->assertInstanceOf(NoOpCircuitBreaker::class, $circuitBreaker);
    }

    public function testGetInstantiatesOnce(): void
    {
        $config = self::$baseConfig;
        $config['circuitBreaker']['enabled'] = false;
        $config = new \Laminas\Config\Config($config);
        // this will be a NoOpCircuitBreaker
        $circuitBreaker = $this->factory->get('TestCommand', $config, $this->metrics);
        // now trying to get the same circuit breaker with a different config
        $circuitBreakerB
            = $this->factory->get('TestCommand', new \Laminas\Config\Config(self::$baseConfig), $this->metrics);
        $this->assertEquals($circuitBreaker, $circuitBreakerB);
    }

    public function testGetInjectsParameters(): void
    {
        $config = new \Laminas\Config\Config(self::$baseConfig);
        $circuitBreaker = $this->factory->get('TestCommand', $config, $this->metrics);
        $this->assertSame('TestCommand', $circuitBreaker->getCommandKey());
        $this->assertEquals($config, $circuitBreaker->getConfig());
    }
}
