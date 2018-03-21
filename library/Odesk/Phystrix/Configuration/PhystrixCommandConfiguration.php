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

use Iterator;

/**
 * Phystrix command configuration class
 */
class PhystrixCommandConfiguration implements MetricsConfigurationInterface, CircuitBreakerConfigurationInterface
{
    /**
     * @var array
     */
    private $configuration;

    /**
     * @var array
     */
    private $circuitBreakerConfiguration = array();

    /**
     * @var array
     */
    private $metricsConfiguration = array();

    public function __construct(Iterator $configurationIterator)
    {
        $this->configuration = iterator_to_array($configurationIterator);
        if (array_key_exists('circuitBreaker', $this->configuration)) {
            $this->circuitBreakerConfiguration = $this->configuration['circuitBreaker'];
        }
        if (array_key_exists('metrics', $this->configuration)) {
            $this->metricsConfiguration = $this->configuration['metrics'];
        }
    }

    /**
     * @return integer
     */
    public function getHealthSnapshotIntervalInMilliseconds()
    {
        return $this->getConfigurationValue(
            $this->metricsConfiguration,
            MetricsConfigurationInterface::CONFIG_KEY_HEALTH_SNAPSHOT_INTERVAL_IN_MILLISECONDS,
            0
        );
    }

    /**
     * @return integer
     */
    public function getRollingStatisticalWindowBuckets()
    {
        return $this->getConfigurationValue(
            $this->metricsConfiguration,
            MetricsConfigurationInterface::CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_BUCKETS,
            0
        );
    }

    /**
     * @return integer
     */
    public function getRollingStatisticalWindowInMilliseconds()
    {
        return $this->getConfigurationValue(
            $this->metricsConfiguration,
            MetricsConfigurationInterface::CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_IN_MILLISECONDS,
            0
        );
    }

    /**
     * @return integer
     */
    public function getErrorThresholdPercentage()
    {
        return $this->getConfigurationValue(
            $this->circuitBreakerConfiguration,
            CircuitBreakerConfigurationInterface::CONFIG_KEY_ERROR_THRESHOLD_PERCENTAGE,
            0
        );
    }

    /**
     * @return integer
     */
    public function getRequestVolumeThreshold()
    {
        return $this->getConfigurationValue(
            $this->circuitBreakerConfiguration,
            CircuitBreakerConfigurationInterface::CONFIG_KEY_REQUEST_VOLUME_THRESHOLD,
            0
        );
    }

    /**
     * @return integer
     */
    public function getSleepWindowInMilliseconds()
    {
        return $this->getConfigurationValue(
            $this->circuitBreakerConfiguration,
            CircuitBreakerConfigurationInterface::CONFIG_KEY_SLEEP_WINDOW_IN_MILLISECONDS,
            0
        );
    }

    /**
     * @return boolean
     */
    public function isForceClosed()
    {
        return $this->getConfigurationValue(
            $this->circuitBreakerConfiguration,
            CircuitBreakerConfigurationInterface::CONFIG_KEY_FORCE_CLOSED,
            false
        );
    }

    /**
     * @return boolean
     */
    public function isForceOpened()
    {
        return $this->getConfigurationValue(
            $this->circuitBreakerConfiguration,
            CircuitBreakerConfigurationInterface::CONFIG_KEY_FORCE_OPENED,
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
        if (!array_key_exists($key, $configuration))
        {
            return $default;
        }

        return $configuration[$key];
    }
}
