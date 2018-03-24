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

namespace Odesk\Phystrix\Configuration;

use ArrayAccess;

/**
 * Phystrix command configuration class
 */
class PhystrixCommandConfiguration implements
    CircuitBreakerConfigurationInterface,
    FallbackConfigurationInterface,
    MetricsConfigurationInterface,
    RequestCacheConfigurationInterface,
    RequestLogConfigurationInterface
{
    /**
     * @var array
     */
    private $circuitBreakerConfiguration = array();

    /**
     * @var array
     */
    private $fallbackConfiguration = array();

    /**
     * @var array
     */
    private $metricsConfiguration = array();

    /**
     * @var array
     */
    private $requestCacheConfiguration = array();

    /**
     * @var array
     */
    private $requestLogConfiguration = array();

    public function __construct(ArrayAccess $configuration)
    {
        $this->circuitBreakerConfiguration =
            $configuration->offsetExists('circuitBreaker')
                ? $configuration->offsetGet('circuitBreaker')
                : array();

        $this->fallbackConfiguration =
            $configuration->offsetExists('fallback')
                ? $configuration->offsetGet('fallback')
                : array();

        $this->metricsConfiguration =
            $configuration->offsetExists('metrics')
                ? $configuration->offsetGet('metrics')
                : array();

        $this->requestCacheConfiguration =
            $configuration->offsetExists('requestCache')
                ? $configuration->offsetGet('requestCache')
                : array();

        $this->requestLogConfiguration =
            $configuration->offsetExists('requestLog')
                ? $configuration->offsetGet('requestLog')
                : array();
    }

    /**
     * @return integer
     */
    public function getMetricsHealthSnapshotIntervalInMilliseconds()
    {
        return $this->getConfigurationValue(
            $this->metricsConfiguration,
            MetricsConfigurationInterface::MT_CONFIG_KEY_HEALTH_SNAPSHOT_INTERVAL_IN_MILLISECONDS,
            0
        );
    }

    /**
     * @return integer
     */
    public function getMetricsRollingStatisticalWindowBuckets()
    {
        return $this->getConfigurationValue(
            $this->metricsConfiguration,
            MetricsConfigurationInterface::MT_CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_BUCKETS,
            0
        );
    }

    /**
     * @return integer
     */
    public function getMetricsRollingStatisticalWindowInMilliseconds()
    {
        return $this->getConfigurationValue(
            $this->metricsConfiguration,
            MetricsConfigurationInterface::MT_CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_IN_MILLISECONDS,
            0
        );
    }

    /**
     * @return integer
     */
    public function getCircuitBreakerErrorThresholdPercentage()
    {
        return $this->getConfigurationValue(
            $this->circuitBreakerConfiguration,
            CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_ERROR_THRESHOLD_PERCENTAGE,
            0
        );
    }

    /**
     * @return integer
     */
    public function getCircuitBreakerRequestVolumeThreshold()
    {
        return $this->getConfigurationValue(
            $this->circuitBreakerConfiguration,
            CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_REQUEST_VOLUME_THRESHOLD,
            0
        );
    }

    /**
     * @return integer
     */
    public function getCircuitBreakerSleepWindowInMilliseconds()
    {
        return $this->getConfigurationValue(
            $this->circuitBreakerConfiguration,
            CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_SLEEP_WINDOW_IN_MILLISECONDS,
            0
        );
    }

    /**
     * @return boolean
     */
    public function isCircuitBreakerEnabled()
    {
        return $this->getConfigurationValue(
            $this->circuitBreakerConfiguration,
            CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_ENABLED,
            false
        );
    }

    /**
     * @return boolean
     */
    public function isCircuitBreakerForceClosed()
    {
        return $this->getConfigurationValue(
            $this->circuitBreakerConfiguration,
            CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_FORCE_CLOSED,
            false
        );
    }

    /**
     * @return boolean
     */
    public function isCircuitBreakerForceOpened()
    {
        return $this->getConfigurationValue(
            $this->circuitBreakerConfiguration,
            CircuitBreakerConfigurationInterface::CB_CONFIG_KEY_FORCE_OPENED,
            false
        );
    }

    /**
     * @return boolean
     */
    public function isRequestCacheEnabled()
    {
        return $this->getConfigurationValue(
            $this->requestCacheConfiguration,
            RequestCacheConfigurationInterface::RC_CONFIG_KEY_ENABLED,
            false
        );
    }

    /**
     * @return boolean
     */
    public function isFallbackEnabled()
    {
        return $this->getConfigurationValue(
            $this->fallbackConfiguration,
            FallbackConfigurationInterface::FB_CONFIG_KEY_ENABLED,
            false
        );
    }

    /**
     * @return boolean
     */
    public function isRequestLogEnabled()
    {
        return $this->getConfigurationValue(
            $this->requestLogConfiguration,
            RequestLogConfigurationInterface::RL_CONFIG_KEY_ENABLED,
            false
        );
    }

    /**
     * @param array $configuration
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    private function getConfigurationValue(array $configuration, $key, $default = null)
    {
        if (!array_key_exists($key, $configuration)) {
            return $default;
        }

        return $configuration[$key];
    }
}
