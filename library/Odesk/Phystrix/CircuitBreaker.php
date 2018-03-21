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

use Odesk\Phystrix\Configuration\CircuitBreakerConfigurationInterface;

/**
 * Circuit-breaker logic that is hooked into AbstractCommand execution and will stop allowing executions
 * if failures have gone past the defined threshold.
 *
 * It will then allow single retries after a defined sleepWindow until the execution succeeds
 * at which point it will again close the circuit and allow executions again.
 */
class CircuitBreaker implements CircuitBreakerInterface
{
    /**
     * @var CommandMetrics
     */
    private $metrics;

    /**
     * Circuit-Breaker config
     *
     * @var CircuitBreakerConfigurationInterface
     */
    private $circuitBreakerConfig;

    /**
     * @var StateStorageInterface
     */
    private $stateStorage;

    /**
     * String identifier of the group of commands this circuit breaker is responsible for
     *
     * @var string
     */
    private $commandKey;

    /**
     * Constructor
     *
     * @param string $commandKey
     * @param CommandMetrics $metrics
     * @param CircuitBreakerConfigurationInterface $circuitBreakerConfig
     * @param StateStorageInterface $stateStorage
     */
    public function __construct(
        $commandKey,
        CommandMetrics $metrics,
        CircuitBreakerConfigurationInterface $circuitBreakerConfig,
        StateStorageInterface $stateStorage
    ) {
        $this->commandKey = $commandKey;
        $this->metrics = $metrics;
        $this->circuitBreakerConfig = $circuitBreakerConfig;
        $this->stateStorage = $stateStorage;
    }

    /**
     * Whether the circuit is open
     *
     * @return boolean
     */
    public function isOpen()
    {
        if ($this->stateStorage->isCircuitOpen($this->commandKey)) {
            // if we're open we immediately return true and don't bother attempting to 'close' ourself
            // as that is left to allowSingleTest and a subsequent successful test to close
            return true;
        }

        $healthCounts = $this->metrics->getHealthCounts();
        if ($healthCounts->getTotal() < $this->circuitBreakerConfig->getRequestVolumeThreshold()) {
            // we are not past the minimum volume threshold for the statistical window
            // so we'll return false immediately and not calculate anything
            return false;
        }

        $allowedErrorPercentage = $this->circuitBreakerConfig->getErrorThresholdPercentage();
        if ($healthCounts->getErrorPercentage() < $allowedErrorPercentage) {
            return false;
        } else {
            $this->stateStorage->openCircuit(
                $this->commandKey,
                $this->circuitBreakerConfig->getSleepWindowInMilliseconds()
            );
            return true;
        }
    }

    /**
     * Whether a single test is allowed now
     *
     * @return boolean
     */
    public function allowSingleTest()
    {
        return $this->stateStorage->allowSingleTest(
            $this->commandKey,
            $this->circuitBreakerConfig->getSleepWindowInMilliseconds()
        );
    }

    /**
     * Whether the request is allowed
     *
     * @return boolean
     */
    public function allowRequest()
    {
        if ($this->circuitBreakerConfig->isForceOpened()) {
            return false;
        }
        if ($this->circuitBreakerConfig->isForceClosed()) {
            return true;
        }

        return !$this->isOpen() || $this->allowSingleTest();
    }

    /**
     * Marks a successful request
     *
     * @link http://goo.gl/dtHN34
     * @return void
     */
    public function markSuccess()
    {
        if ($this->stateStorage->isCircuitOpen($this->commandKey)) {
            $this->stateStorage->closeCircuit($this->commandKey);
            // may cause some stats to be removed from reporting, see http://goo.gl/dtHN34
            $this->metrics->resetCounter();
        }
    }
}
