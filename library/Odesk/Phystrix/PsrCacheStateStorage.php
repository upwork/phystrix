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

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * PSR cache driven storage for Circuit Breaker metrics statistics
 */
class PsrCacheStateStorage implements StateStorageInterface
{
    const BUCKET_EXPIRE_SECONDS = 120;

    const CACHE_PREFIX = 'phystrix_cb_';

    const OPENED_NAME = 'opened';

    const SINGLE_TEST_BLOCKED = 'single_test_blocked';

    /**
     * @var CacheItemPoolInterface
     */
    private $psrCache;

    public function __construct(CacheItemPoolInterface $psrCache)
    {
        $this->psrCache = $psrCache;
    }

    /**
     * Returns counter value for the given bucket
     *
     * @param string $commandKey
     * @param string $type
     * @param integer $index
     * @return integer
     */
    public function getBucket($commandKey, $type, $index)
    {
        $bucketName = $this->getBucketName($commandKey, $type, $index);
        $cacheItem =  $this->getCacheItem($bucketName);

        return (int) $cacheItem->get();
    }

    /**
     * Increments counter value for the given bucket
     *
     * @param string $commandKey
     * @param string $type
     * @param integer $index
     */
    public function incrementBucket($commandKey, $type, $index)
    {
        $bucketName = $this->getBucketName($commandKey, $type, $index);
        $cacheItem = $this->getCacheItem($bucketName);
        $counter = $cacheItem->get();

        if ($counter) {
            $cacheItem->set($counter + 1);
        } else {
            $cacheItem->set(1);
        }

        $this->psrCache->save($cacheItem);
    }

    /**
     * If the given bucket is found, sets counter value to 0.
     *
     * @param string $commandKey Circuit breaker key
     * @param integer $type
     * @param integer $index
     */
    public function resetBucket($commandKey, $type, $index)
    {
        $bucketName = $this->getBucketName($commandKey, $type, $index);
        $cacheItem = $this->getCacheItem($bucketName);

        $cacheItem->set(0);

        $this->psrCache->save($cacheItem);
    }

    /**
     * Marks the given circuit  as open
     *
     * @param string $commandKey Circuit key
     * @param integer $sleepingWindowInMilliseconds In how much time we should allow a single test
     */
    public function openCircuit($commandKey, $sleepingWindowInMilliseconds)
    {
        $openedKey = $this->getPrefix($commandKey . self::OPENED_NAME);
        $singleTestFlagKey = $this->getPrefix($commandKey . self::SINGLE_TEST_BLOCKED);

        $cacheItem = $this->getCacheItem($openedKey);
        $cacheItem->set(true);
        $this->psrCache->save($cacheItem);

        // the single test blocked flag will expire automatically in $sleepingWindowInMilliseconds
        // thus allowing us a single test.
        $sleepingWindowInSeconds = ceil($sleepingWindowInMilliseconds / 1000);

        $cacheItem = $this->getCacheItem($singleTestFlagKey);
        $cacheItem->set(true);
        $cacheItem->expiresAfter($sleepingWindowInSeconds);

        $this->psrCache->save($cacheItem);
    }

    /**
     * Whether a single test is allowed
     *
     * @param string $commandKey Circuit breaker key
     * @param integer $sleepingWindowInMilliseconds In how much time we should allow the next single test
     * @return boolean
     */
    public function allowSingleTest($commandKey, $sleepingWindowInMilliseconds)
    {
        $singleTestFlagKey = $this->getPrefix($commandKey . self::SINGLE_TEST_BLOCKED);
        $sleepingWindowInSeconds = ceil($sleepingWindowInMilliseconds / 1000);

        $cacheItem = $this->getCacheItem($singleTestFlagKey);
        $cacheItem->set(true);
        $cacheItem->expiresAfter($sleepingWindowInSeconds);

        return $this->psrCache->save($cacheItem);
    }

    /**
     * Whether a circuit is open
     *
     * @param string $commandKey Circuit breaker key
     * @return boolean
     */
    public function isCircuitOpen($commandKey)
    {
        $openedKey = $this->getPrefix($commandKey . self::OPENED_NAME);
        $cacheItem = $this->getCacheItem($openedKey);

        return (boolean) $cacheItem->get();
    }

    /**
     * Marks the given circuit as closed
     *
     * @param string $commandKey Circuit key
     */
    public function closeCircuit($commandKey)
    {
        $openedKey = $this->getPrefix($commandKey . self::OPENED_NAME);
        $cacheItem = $this->getCacheItem($openedKey);

        $cacheItem->set(false);

        $this->psrCache->save($cacheItem);
    }

    /**
     * Returns cache item
     *
     * @param string $key
     * @return CacheItemInterface|null
     */
    private function getCacheItem($key)
    {
        if ($this->psrCache->hasItem($key)) {
            return $this->psrCache->getItem($key);
        }
    }

    /**
     * @param string $commandKey
     * @param string $type
     * @param string $index
     * @return string
     */
    private function getBucketName($commandKey, $type, $index)
    {
        return $this->getPrefix($commandKey . '_' . $type . '_' . $index);
    }

    /**
     * @param string $name
     * @return string
     */
    private function getPrefix($name)
    {
        return self::CACHE_PREFIX . $name;
    }
}
