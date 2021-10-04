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
use Odesk\Phystrix\CommandFactory;
use Odesk\Phystrix\CommandMetricsFactory;
use Odesk\Phystrix\RequestCache;
use Odesk\Phystrix\RequestLog;
use Laminas\Di\ServiceLocator;
use Odesk\Phystrix\StateStorageInterface;
use PHPUnit\Framework\TestCase;
use Tests\Odesk\Phystrix\FactoryCommandMock;

class CommandFactoryTest extends TestCase
{
    public function testGetCommand(): void
    {
        $config = new \Laminas\Config\Config([
            'default' => [
                'fallback' => ['enabled' => true]
            ]
        ]);
        $serviceLocator = new ServiceLocator();
        $stateStorage = $this->createMock(StateStorageInterface::class);
        $circuitBreakerFactory = new CircuitBreakerFactory($stateStorage);
        $commandMetricsFactory = new CommandMetricsFactory($stateStorage);
        $requestCache = new RequestCache();
        $requestLog = new RequestLog();
        $commandFactory = new CommandFactory(
            $config,
            $serviceLocator,
            $circuitBreakerFactory,
            $commandMetricsFactory,
            $requestCache,
            $requestLog
        );
        /** @var FactoryCommandMock $command */
        $command = $commandFactory->getCommand(FactoryCommandMock::class, 'test', 'hello');
        // injects constructor parameters
        $this->assertEquals('test', $command->a);
        $this->assertEquals('hello', $command->b);
        // injects the infrastructure components
        $expectedDefaultConfig = new \Laminas\Config\Config(array(
            'fallback' => array('enabled' => true)
        ), true);
        $this->assertEquals($expectedDefaultConfig, $command->getConfig());
    }

    public function testGetCommandMergesConfig(): void
    {
        $config = new \Laminas\Config\Config([
            'default' => [
                'fallback' => ['enabled' => true],
                'customData' => 12345
            ],
            FactoryCommandMock::class => [
                'fallback' => ['enabled' => false],
                'circuitBreaker' => ['enabled' => false]
            ]
        ]);
        $serviceLocator = new ServiceLocator();
        $stateStorage = $this->createMock(StateStorageInterface::class);
        $circuitBreakerFactory = new CircuitBreakerFactory($stateStorage);
        $commandMetricsFactory = new CommandMetricsFactory($stateStorage);
        $commandFactory = new CommandFactory(
            $config,
            $serviceLocator,
            $circuitBreakerFactory,
            $commandMetricsFactory,
            new RequestCache(),
            new RequestLog()
        );
        /** @var FactoryCommandMock $command */
        $command = $commandFactory->getCommand(FactoryCommandMock::class, 'test', 'hello');
        $expectedConfig = new \Laminas\Config\Config(array(
            'fallback' => array('enabled' => false),
            'circuitBreaker' => array('enabled' => false),
            'customData' => 12345
        ), true);
        $this->assertEquals($expectedConfig, $command->getConfig());
    }
}
