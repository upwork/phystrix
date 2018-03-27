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

    public function testConfigurationAppliesDefaultValuesIfConfigurationsAreMissing()
    {
        $this->configuration = new PhystrixCommandConfiguration(new ArrayObject(array()));
        $this->assertSame(0, $this->configuration->getCircuitBreakerErrorThresholdPercentage());
        $this->assertFalse($this->configuration->isCircuitBreakerEnabled());
        $this->assertFalse($this->configuration->isCircuitBreakerForceClosed());
        $this->assertFalse($this->configuration->isCircuitBreakerForceOpened());
        $this->assertSame(0, $this->configuration->getCircuitBreakerRequestVolumeThreshold());
        $this->assertSame(0, $this->configuration->getCircuitBreakerSleepWindowInMilliseconds());

        $this->assertSame(0, $this->configuration->getMetricsHealthSnapshotIntervalInMilliseconds());
        $this->assertSame(0, $this->configuration->getMetricsRollingStatisticalWindowBuckets());
        $this->assertSame(0, $this->configuration->getMetricsRollingStatisticalWindowInMilliseconds());

        $this->assertFalse($this->configuration->isRequestCacheEnabled());

        $this->assertFalse($this->configuration->isFallbackEnabled());

        $this->assertFalse($this->configuration->isRequestLogEnabled());
    }

    public function testConfigurationAppliesCircuitBreakerFromGivenConfiguration()
    {
        $this->assertCircuitBreakerConfiguration();
    }

    public function testConfigurationAppliesCircuitBreakerUpdatesFromGivenConfiguration()
    {
        $this->assertCircuitBreakerConfiguration();

        $this->configuration->updateConfiguration(new ArrayObject(array(
            'circuitBreaker' => array(
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_ERROR_THRESHOLD_PERCENTAGE => 10,
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_ENABLED => false,
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_FORCE_CLOSED => true,
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_FORCE_OPENED => false,
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_REQUEST_VOLUME_THRESHOLD => 22,
                CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_SLEEP_WINDOW_IN_MILLISECONDS => 1300
            )
        )));

        $this->assertSame(10, $this->configuration->getCircuitBreakerErrorThresholdPercentage());
        $this->assertFalse($this->configuration->isCircuitBreakerEnabled());
        $this->assertTrue($this->configuration->isCircuitBreakerForceClosed());
        $this->assertFalse($this->configuration->isCircuitBreakerForceOpened());
        $this->assertSame(22, $this->configuration->getCircuitBreakerRequestVolumeThreshold());
        $this->assertSame(1300, $this->configuration->getCircuitBreakerSleepWindowInMilliseconds());
    }

    public function testConfigurationAppliesMetricsFromGivenConfiguration()
    {
        $this->assertMetricsConfiguration();
    }

    public function testConfigurationAppliesMetricsUpdatesFromGivenConfiguration()
    {
        $this->assertMetricsConfiguration();

        $this->configuration->updateConfiguration(new ArrayObject(array(
            'metrics' => array(
                MetricsConfigurationInterface::MT_CONFIG_KEY_HEALTH_SNAPSHOT_INTERVAL_IN_MILLISECONDS => 500,
                MetricsConfigurationInterface::MT_CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_BUCKETS => 4,
                MetricsConfigurationInterface::MT_CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_IN_MILLISECONDS => 700,
            )
        )));

        $this->assertSame(500, $this->configuration->getMetricsHealthSnapshotIntervalInMilliseconds());
        $this->assertSame(4, $this->configuration->getMetricsRollingStatisticalWindowBuckets());
        $this->assertSame(700, $this->configuration->getMetricsRollingStatisticalWindowInMilliseconds());
    }

    public function testConfigurationAppliesRequestCacheFromGivenConfiguration()
    {
        $this->assertTrue($this->configuration->isRequestCacheEnabled());
    }

    public function testConfigurationAppliesRequestCacheUpdatesFromGivenConfiguration()
    {
        $this->assertTrue($this->configuration->isRequestCacheEnabled());

        $this->configuration->updateConfiguration(new ArrayObject(array(
            'requestCache' => array(
                RequestCacheConfigurationInterface::RC_CONFIG_KEY_ENABLED => false
            ),
        )));

        $this->assertFalse($this->configuration->isRequestCacheEnabled());
    }

    public function testConfigurationAppliesFallbackFromGivenConfiguration()
    {
        $this->assertTrue($this->configuration->isFallbackEnabled());
    }

    public function testConfigurationAppliesFallbackUpdatesFromGivenConfiguration()
    {
        $this->assertTrue($this->configuration->isFallbackEnabled());

        $this->configuration->updateConfiguration(new ArrayObject(array(
            'fallback' => array(
                FallbackConfigurationInterface::FB_CONFIG_KEY_ENABLED => false
            ),
        )));

        $this->assertFalse($this->configuration->isFallbackEnabled());
    }

    public function testConfigurationAppliesRequestLogFromGivenConfiguration()
    {
        $this->assertTrue($this->configuration->isRequestLogEnabled());
    }

    public function testConfigurationAppliesRequestLogUpdatesFromGivenConfiguration()
    {
        $this->assertTrue($this->configuration->isRequestLogEnabled());

        $this->configuration->updateConfiguration(new ArrayObject(array(
            'requestLog' => array(
                RequestLogConfigurationInterface::RL_CONFIG_KEY_ENABLED => false
            )
        )));

        $this->assertFalse($this->configuration->isRequestLogEnabled());
    }

    private function assertCircuitBreakerConfiguration()
    {
        $this->assertSame(25, $this->configuration->getCircuitBreakerErrorThresholdPercentage());
        $this->assertTrue($this->configuration->isCircuitBreakerEnabled());
        $this->assertFalse($this->configuration->isCircuitBreakerForceClosed());
        $this->assertTrue($this->configuration->isCircuitBreakerForceOpened());
        $this->assertSame(32, $this->configuration->getCircuitBreakerRequestVolumeThreshold());
        $this->assertSame(5000, $this->configuration->getCircuitBreakerSleepWindowInMilliseconds());
    }

    private function assertMetricsConfiguration()
    {
        $this->assertSame(2000, $this->configuration->getMetricsHealthSnapshotIntervalInMilliseconds());
        $this->assertSame(10, $this->configuration->getMetricsRollingStatisticalWindowBuckets());
        $this->assertSame(10000, $this->configuration->getMetricsRollingStatisticalWindowInMilliseconds());
    }
}
