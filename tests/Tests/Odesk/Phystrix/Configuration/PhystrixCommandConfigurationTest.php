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
namespace Tests\Odesk\Phystrix\Configuration;

use ArrayObject;
use Odesk\Phystrix\Configuration\CircuitBreakerConfigurationInterface;
use Odesk\Phystrix\Configuration\MetricsConfigurationInterface;
use Odesk\Phystrix\Configuration\PhystrixCommandConfiguration;
use PHPUnit_Framework_TestCase;

class PhystrixCommandConfigurationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PhystrixCommandConfiguration
     */
    private $configuration;

    public function setUp()
    {
        $configurationArray = new ArrayObject(array(
            'circuitBreaker' => array(
                CircuitBreakerConfigurationInterface::CONFIG_KEY_ERROR_THRESHOLD_PERCENTAGE => 25,
                CircuitBreakerConfigurationInterface::CONFIG_KEY_ENABLED => true,
                CircuitBreakerConfigurationInterface::CONFIG_KEY_FORCE_CLOSED => false,
                CircuitBreakerConfigurationInterface::CONFIG_KEY_FORCE_OPENED => true,
                CircuitBreakerConfigurationInterface::CONFIG_KEY_REQUEST_VOLUME_THRESHOLD => 32,
                CircuitBreakerConfigurationInterface::CONFIG_KEY_SLEEP_WINDOW_IN_MILLISECONDS => 5000
            ),
            'metrics' => array(
                MetricsConfigurationInterface::CONFIG_KEY_HEALTH_SNAPSHOT_INTERVAL_IN_MILLISECONDS => 2000,
                MetricsConfigurationInterface::CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_BUCKETS => 10,
                MetricsConfigurationInterface::CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_IN_MILLISECONDS => 10000,
            )
        ));

        $this->configuration = new PhystrixCommandConfiguration($configurationArray->getIterator());
    }

    public function testConfigurationAppliesCircuitBreakerConfigurationFromIterator()
    {
        $this->assertSame(25, $this->configuration->getErrorThresholdPercentage());
        $this->assertTrue($this->configuration->isEnabled());
        $this->assertFalse($this->configuration->isForceClosed());
        $this->assertTrue($this->configuration->isForceOpened());
        $this->assertSame(32, $this->configuration->getRequestVolumeThreshold());
        $this->assertSame(5000, $this->configuration->getSleepWindowInMilliseconds());
    }

    public function testConfigurationAppliesMetricsConfigurationFromIterator()
    {
        $this->assertSame(2000, $this->configuration->getHealthSnapshotIntervalInMilliseconds());
        $this->assertSame(10, $this->configuration->getRollingStatisticalWindowBuckets());
        $this->assertSame(10000, $this->configuration->getRollingStatisticalWindowInMilliseconds());
    }
}
