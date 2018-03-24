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
use Odesk\Phystrix\Configuration\FallbackConfigurationInterface;
use Odesk\Phystrix\Configuration\MetricsConfigurationInterface;
use Odesk\Phystrix\Configuration\PhystrixCommandConfiguration;
use Odesk\Phystrix\Configuration\RequestCacheConfigurationInterface;
use Odesk\Phystrix\Configuration\RequestLogConfigurationInterface;
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
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_ERROR_THRESHOLD_PERCENTAGE => 25,
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_ENABLED => true,
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_FORCE_CLOSED => false,
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_FORCE_OPENED => true,
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_REQUEST_VOLUME_THRESHOLD => 32,
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_SLEEP_WINDOW_IN_MILLISECONDS => 5000
            ),
            'fallback' => array(
                FallbackConfigurationInterface::FB_CONFIG_KEY_ENABLED => true
            ),
            'metrics' => array(
                MetricsConfigurationInterface::MT_CONFIG_KEY_HEALTH_SNAPSHOT_INTERVAL_IN_MILLISECONDS => 2000,
                MetricsConfigurationInterface::MT_CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_BUCKETS => 10,
                MetricsConfigurationInterface::MT_CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_IN_MILLISECONDS => 10000,
            ),
            'requestCache' => array(
                RequestCacheConfigurationInterface::RC_CONFIG_KEY_ENABLED => true
            ),
            'requestLog' => array(
                RequestLogConfigurationInterface::RL_CONFIG_KEY_ENABLED => true
            )
        ));

        $this->configuration = new PhystrixCommandConfiguration($configurationArray);
    }

    public function testConfigurationAppliesCircuitBreakerConfigurationFromIterator()
    {
        $this->assertSame(25, $this->configuration->getCircuitBreakerErrorThresholdPercentage());
        $this->assertTrue($this->configuration->isCircuitBreakerEnabled());
        $this->assertFalse($this->configuration->isCircuitBreakerForceClosed());
        $this->assertTrue($this->configuration->isCircuitBreakerForceOpened());
        $this->assertSame(32, $this->configuration->getCircuitBreakerRequestVolumeThreshold());
        $this->assertSame(5000, $this->configuration->getCircuitBreakerSleepWindowInMilliseconds());
    }

    public function testConfigurationAppliesMetricsConfigurationFromIterator()
    {
        $this->assertSame(2000, $this->configuration->getMetricsHealthSnapshotIntervalInMilliseconds());
        $this->assertSame(10, $this->configuration->getMetricsRollingStatisticalWindowBuckets());
        $this->assertSame(10000, $this->configuration->getMetricsRollingStatisticalWindowInMilliseconds());
    }

    public function testConfigurationAppliesRequestCacheConfigurationFromIterator()
    {
        $this->assertTrue($this->configuration->isRequestCacheEnabled());
    }

    public function testConfigurationAppliesFallbackConfigurationFromIterator()
    {
        $this->assertTrue($this->configuration->isFallbackEnabled());
    }

    public function testConfigurationAppliesRequestLogConfigurationFromIterator()
    {
        $this->assertTrue($this->configuration->isRequestLogEnabled());
    }
}
