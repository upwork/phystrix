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
namespace Odesk\Phystrix;

use ReflectionClass;
use Laminas\Config\Config;
use Laminas\Di\LocatorInterface;

/**
 * All commands must be created through this factory.
 * It injects all dependencies required for Circuit Breaker logic etc.
 */
class CommandFactory
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var CircuitBreakerFactory
     */
    protected $circuitBreakerFactory;

    /**
     * @var CommandMetricsFactory
     */
    protected $commandMetricsFactory;

    /**
     * @var RequestCache
     */
    protected $requestCache;

    /**
     * @var RequestLog
     */
    protected $requestLog;

    /**
     * Constructor
     *
     * @param Config $config
     * @param LocatorInterface $serviceLocator
     * @param CircuitBreakerFactory $circuitBreakerFactory
     * @param CommandMetricsFactory $commandMetricsFactory
     * @param RequestCache $requestCache
     * @param RequestLog $requestLog
     */
    public function __construct(
        Config $config,
        LocatorInterface $serviceLocator,
        CircuitBreakerFactory $circuitBreakerFactory,
        CommandMetricsFactory $commandMetricsFactory,
        RequestCache $requestCache = null,
        RequestLog $requestLog = null
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->config = $config;
        $this->circuitBreakerFactory = $circuitBreakerFactory;
        $this->commandMetricsFactory = $commandMetricsFactory;
        $this->requestCache = $requestCache;
        $this->requestLog = $requestLog;
    }

    /**
     * Instantiates and configures a command
     *
     * @param string $class
     * @return AbstractCommand
     */
    public function getCommand($class)
    {
        $parameters = func_get_args();
        array_shift($parameters);

        $reflection = new ReflectionClass($class);
        /** @var AbstractCommand $command */
        $command = empty($parameters) ?
            $reflection->newInstance() :
            $reflection->newInstanceArgs($parameters);

        $command->setCircuitBreakerFactory($this->circuitBreakerFactory);
        $command->setCommandMetricsFactory($this->commandMetricsFactory);
        $command->setServiceLocator($this->serviceLocator);
        $command->initializeConfig($this->config);

        if ($this->requestCache) {
            $command->setRequestCache($this->requestCache);
        }

        if ($this->requestLog) {
            $command->setRequestLog($this->requestLog);
        }

        return $command;
    }
}
