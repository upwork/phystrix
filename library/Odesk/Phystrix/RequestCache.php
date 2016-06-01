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

/**
 * Object for request caching, one instance shared between all commands
 */
class RequestCache
{
    /**
     * Associative array of results per command key per cache key
     *
     * @var array
     */
    protected $cachedResults = array();

    /**
     * Clears the cache for a given commandKey only
     *
     * @param string $commandKey
     */
    public function clearAll($commandKey)
    {
        if (isset($this->cachedResults[$commandKey])) {
            unset($this->cachedResults[$commandKey]);
        }
    }

    /**
     * Clears the cache for a given cacheKey, for a given commandKey
     *
     * @param string $commandKey
     * @param string $cacheKey
     */
    public function clear($commandKey, $cacheKey)
    {
        if ($this->exists($commandKey, $cacheKey)) {
            unset($this->cachedResults[$commandKey][$cacheKey]);
        }
    }

    /**
     * Attempts to obtain cached result for a given command type
     *
     * @param string $commandKey
     * @param string $cacheKey
     * @return mixed|null
     */
    public function get($commandKey, $cacheKey)
    {
        if ($this->exists($commandKey, $cacheKey)) {
            return $this->cachedResults[$commandKey][$cacheKey];
        }

        return null;
    }

    /**
     * Puts request result into cache for a given command type
     *
     * @param string $commandKey
     * @param string $cacheKey
     * @param mixed $result
     */
    public function put($commandKey, $cacheKey, $result)
    {
        $this->cachedResults[$commandKey][$cacheKey] = $result;
    }

    /**
     * Returns true, if specified cache key exists
     *
     * @param string $commandKey
     * @param string $cacheKey
     * @return bool
     */
    public function exists($commandKey, $cacheKey)
    {
        return array_key_exists($commandKey, $this->cachedResults)
            && array_key_exists($cacheKey, $this->cachedResults[$commandKey]);
    }
}
