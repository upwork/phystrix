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
 * Circuit-Breaker configuration interface
 */
interface CircuitBreakerConfigurationInterface
{
    const CB_CONFIG_KEY_ERROR_THRESHOLD_PERCENTAGE = 'errorThresholdPercentage';
    const CB_CONFIG_KEY_ENABLED = 'enabled';
    const CB_CONFIG_KEY_FORCE_CLOSED = 'forceClosed';
    const CB_CONFIG_KEY_FORCE_OPENED = 'forceOpen';
    const CB_CONFIG_KEY_REQUEST_VOLUME_THRESHOLD = 'requestVolumeThreshold';
    const CB_CONFIG_KEY_SLEEP_WINDOW_IN_MILLISECONDS = 'sleepWindowInMilliseconds';

    /**
     * @return integer
     */
    public function getCircuitBreakerErrorThresholdPercentage();

    /**
     * @return integer
     */
    public function getCircuitBreakerRequestVolumeThreshold();

    /**
     * @return integer
     */
    public function getCircuitBreakerSleepWindowInMilliseconds();

    /**
     * @return boolean
     */
    public function isCircuitBreakerEnabled();

    /**
     * @return boolean
     */
    public function isCircuitBreakerForceClosed();

    /**
     * @return boolean
     */
    public function isCircuitBreakerForceOpened();
}
