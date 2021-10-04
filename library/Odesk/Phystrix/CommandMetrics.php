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
 * This class tracks different metrics for a command.
 *
 * Main purpose is to provide health statistics to the command's circuit breaker.
 * Some metrics are tracked for informational purposes, e.g. how effective usage of the cache has been.
 * Tracking only happens within the rolling statistical window.
 */
class CommandMetrics
{
    private MetricsCounter $counter;
    private int $healthSnapshotIntervalInMilliseconds = 1000;

    /**
     * @var HealthCountsSnapshot
     */
    private $lastSnapshot;

    /**
     * Constructor
     *
     * @param MetricsCounter $counter
     * @param integer $snapshotInterval Snapshot interval time in milliseconds
     */
    public function __construct(MetricsCounter $counter, $snapshotInterval)
    {
        $this->counter = $counter;
        $this->healthSnapshotIntervalInMilliseconds = $snapshotInterval;
    }

    public function getHealthSnapshotIntervalInMilliseconds(): int
    {
        return $this->healthSnapshotIntervalInMilliseconds;
    }

    /**
     * Increments success counter
     */
    public function markSuccess(): void
    {
        $this->counter->add(MetricsCounter::SUCCESS);
    }

    /**
     * Increments from cache counter
     */
    public function markResponseFromCache(): void
    {
        $this->counter->add(MetricsCounter::RESPONSE_FROM_CACHE);
    }

    /**
     * Increments failure counter
     */
    public function markFailure(): void
    {
        $this->counter->add(MetricsCounter::FAILURE);
    }

    /**
     * Increments fallback success counter
     */
    public function markFallbackSuccess(): void
    {
        $this->counter->add(MetricsCounter::FALLBACK_SUCCESS);
    }

    /**
     * Increments fallback failure counter
     */
    public function markFallbackFailure(): void
    {
        $this->counter->add(MetricsCounter::FALLBACK_FAILURE);
    }

    /**
     * Increments exception thrown counter
     */
    public function markExceptionThrown(): void
    {
        $this->counter->add(MetricsCounter::EXCEPTION_THROWN);
    }

    /**
     * Increments short circuited counter
     */
    public function markShortCircuited(): void
    {
        $this->counter->add(MetricsCounter::SHORT_CIRCUITED);
    }

    /**
     * Resets counters for all metrics
     * may cause some stats to be removed from reporting, see http://goo.gl/dtHN34
     */
    public function resetCounter(): void
    {
        $this->counter->reset();
    }

    /**
     * Returns rolling count for a given metrics type
     *
     * @param integer $type E.g. MetricsCounter::SUCCESS
     * @return integer
     */
    public function getRollingCount($type)
    {
        return $this->counter->get($type);
    }

    /**
     * Returns (and creates when needed) the current health metrics snapshot
     *
     * @return HealthCountsSnapshot
     */
    public function getHealthCounts()
    {
        // current time in milliseconds
        $now = microtime(true) * 1000;
        // we should make a new snapshot in case there isn't one yet or when the snapshot interval time has passed
        if (!$this->lastSnapshot
            || $now - $this->lastSnapshot->getTime() >= $this->healthSnapshotIntervalInMilliseconds) {
            $this->lastSnapshot = new HealthCountsSnapshot(
                $now,
                $this->getRollingCount(MetricsCounter::SUCCESS),
                $this->getRollingCount(MetricsCounter::FAILURE)
            );
        }

        return $this->lastSnapshot;
    }
}
