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

/**
 * Metrics configuration interface
 */
interface MetricsConfigurationInterface
{
    const CONFIG_KEY_HEALTH_SNAPSHOT_INTERVAL_IN_MILLISECONDS = 'healthSnapshotIntervalInMilliseconds';
    const CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_BUCKETS = 'rollingStatisticalWindowBuckets';
    const CONFIG_KEY_ROLLING_STATISTICAL_WINDOW_IN_MILLISECONDS = 'rollingStatisticalWindowInMilliseconds';



    /**
     * @return integer
     */
    public function getHealthSnapshotIntervalInMilliseconds();

    /**
     * @return integer
     */
    public function getRollingStatisticalWindowBuckets();

    /**
     * @return integer
     */
    public function getRollingStatisticalWindowInMilliseconds();
}
