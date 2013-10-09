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
use Zend\Di\ServiceLocator;

class CommandFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCommand()
    {
        $config = new \Zend\Config\Config(array(
            'default' => array(
                'fallback' => array('enabled' => true)
            )
        ));
        $serviceLocator = new ServiceLocator();
        $stateStorage = $this->getMock('Odesk\Phystrix\StateStorageInterface');
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
        $command = $commandFactory->getCommand('Tests\Odesk\Phystrix\FactoryCommandMock', 'test', 'hello');
        // injects constructor parameters
        $this->assertEquals('test', $command->a);
        $this->assertEquals('hello', $command->b);
        // injects the infrastructure components
        $expectedDefaultConfig = new \Zend\Config\Config(array(
            'fallback' => array('enabled' => true)
        ), true);
        $this->assertAttributeEquals($expectedDefaultConfig, 'config', $command);
        $this->assertAttributeEquals($circuitBreakerFactory, 'circuitBreakerFactory', $command);
        $this->assertAttributeEquals($serviceLocator, 'serviceLocator', $command);
        $this->assertAttributeEquals($requestCache, 'requestCache', $command);
        $this->assertAttributeEquals($requestLog, 'requestLog', $command);
    }

    public function testGetCommandMergesConfig()
    {
        $config = new \Zend\Config\Config(array(
            'default' => array(
                'fallback' => array('enabled' => true),
                'customData' => 12345
            ),
            'Tests\Odesk\Phystrix\FactoryCommandMock' => array(
                'fallback' => array('enabled' => false),
                'circuitBreaker' => array('enabled' => false)
            )
        ));
        $serviceLocator = new ServiceLocator();
        $stateStorage = $this->getMock('Odesk\Phystrix\StateStorageInterface');
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
        $command = $commandFactory->getCommand('Tests\Odesk\Phystrix\FactoryCommandMock', 'test', 'hello');
        $expectedConfig = new \Zend\Config\Config(array(
            'fallback' => array('enabled' => false),
            'circuitBreaker' => array('enabled' => false),
            'customData' => 12345
        ), true);
        $this->assertAttributeEquals($expectedConfig, 'config', $command);
    }
}
