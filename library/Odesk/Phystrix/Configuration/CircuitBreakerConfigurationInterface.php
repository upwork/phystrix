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
    const CONFIG_KEY_ERROR_THRESHOLD_PERCENTAGE = 'errorThresholdPercentage';
    const CONFIG_KEY_ENABLED = 'enabled';
    const CONFIG_KEY_FORCE_CLOSED = 'forceClosed';
    const CONFIG_KEY_FORCE_OPENED = 'forceOpen';
    const CONFIG_KEY_REQUEST_VOLUME_THRESHOLD = 'requestVolumeThreshold';
    const CONFIG_KEY_SLEEP_WINDOW_IN_MILLISECONDS = 'sleepWindowInMilliseconds';

    /**
     * @return integer
     */
    public function getErrorThresholdPercentage();

    /**
     * @return integer
     */
    public function getRequestVolumeThreshold();

    /**
     * @return integer
     */
    public function getSleepWindowInMilliseconds();

    /**
     * @return boolean
     */
    public function isEnabled();

    /**
     * @return boolean
     */
    public function isForceClosed();

    /**
     * @return boolean
     */
    public function isForceOpened();
}
