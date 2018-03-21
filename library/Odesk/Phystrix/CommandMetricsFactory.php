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

use Odesk\Phystrix\Configuration\MetricsConfigurationInterface;

/**
 * Factory to keep track of and instantiate new command metrics objects when needed
 */
class CommandMetricsFactory
{
    const CONFIGURATION_NAMESPACE = 'metrics';

    /**
     * @var array
     */
    protected $commandMetricsByCommand = array();

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var StateStorageInterface
     */
    protected $stateStorage;

    /**
     * Constructor
     *
     * @param StateStorageInterface $stateStorage
     */
    public function __construct(StateStorageInterface $stateStorage)
    {
        $this->stateStorage = $stateStorage;
    }

    /**
     * Get command metrics instance by command key for given command config
     *
     * @param string $commandKey
     * @param MetricsConfigurationInterface $metricsConfiguration
     * @return CommandMetrics
     */
    public function get($commandKey, MetricsConfigurationInterface $metricsConfiguration)
    {
        if (!isset($this->commandMetricsByCommand[$commandKey])) {
            $statisticalWindow = $metricsConfiguration->getRollingStatisticalWindowInMilliseconds();
            $windowBuckets = $metricsConfiguration->getRollingStatisticalWindowBuckets();
            $snapshotInterval = $metricsConfiguration->getHealthSnapshotIntervalInMilliseconds();

            $counter = new MetricsCounter($commandKey, $this->stateStorage, $statisticalWindow, $windowBuckets);
            $this->commandMetricsByCommand[$commandKey] = new CommandMetrics($counter, $snapshotInterval);
        }

        return $this->commandMetricsByCommand[$commandKey];
    }

    /**
     * @param array|null $configuration
     * @param string $key
     * @return mixed|null
     */
    private function getConfigurationValue(array $configuration = null, $key)
    {
        if (null === $configuration) {
            return null;
        }

        if (null === $this->configuration) {
            if (array_key_exists(static::CONFIGURATION_NAMESPACE, $configuration)) {
                foreach ($configuration[static::CONFIGURATION_NAMESPACE] as $configurationKey => $configurationValue) {
                    $this->configuration[static::CONFIGURATION_NAMESPACE . '.' . $configurationKey]
                        = $configurationValue;
                }
            }
        }

        if (array_key_exists($key, $this->configuration)) {
            return $this->configuration[$key];
        }

        return null;
    }
}
