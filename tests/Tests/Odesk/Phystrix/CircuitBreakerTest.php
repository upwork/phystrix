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
namespace Tests\Odesk\Phystrix;

use Laminas\Config\Config;
use Odesk\Phystrix\CircuitBreaker;
use Odesk\Phystrix\CommandMetrics;
use Odesk\Phystrix\StateStorageInterface;
use Odesk\Phystrix\HealthCountsSnapshot;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CircuitBreakerTest extends TestCase
{
    /**
     * @var MockObject|CommandMetrics
     */
    protected $metrics;

    /**
     * @var MockObject|StateStorageInterface
     */
    protected $stateStorage;

    protected function setUp(): void
    {
        $this->metrics = $this->createMock(CommandMetrics::class);
        $this->stateStorage = $this->createMock(StateStorageInterface::class);
    }

    protected function getCircuitBreaker($config = []): CircuitBreaker
    {
        $commandConfig = new Config([
            'circuitBreaker' => [
                'enabled' => true,
                'errorThresholdPercentage' => 50,
                'forceOpen' => false,
                'forceClosed' => false,
                'requestVolumeThreshold' => 50,
                'sleepWindowInMilliseconds' => 5000,
                'metrics' => [
                    'healthSnapshotIntervalInMilliseconds' => 1000,
                    'rollingStatisticalWindowInMilliseconds' => 10000,
                    'rollingStatisticalWindowBuckets' => 10,
                ]
            ],
        ], true);
        $commandConfig->merge(new Config($config, true));

        return new CircuitBreaker('TestCommand', $this->metrics, $commandConfig, $this->stateStorage);
    }


    public function testIsOpenReturnsTrueImmediately(): void
    {
        $this->stateStorage->expects($this->once())
            ->method('isCircuitOpen')
            ->with($this->equalTo('TestCommand'))
            ->willReturn(true);

        $this->metrics->expects($this->never())
            ->method('getHealthCounts');

        $this->assertTrue($this->getCircuitBreaker()->isOpen());
    }

    public function testIsOpenNotPastTheThreshold(): void
    {
        $this->stateStorage->expects($this->once())
            ->method('isCircuitOpen')
            ->with($this->equalTo('TestCommand'))
            ->willReturn(false);

        $healthCounts = $this->createMock(HealthCountsSnapshot::class);
        $healthCounts->expects($this->once())
            ->method('getTotal')
            ->willReturn(47); // total is 47, threshold is set to 50.
        $healthCounts->expects($this->never())
            ->method('getErrorPercentage'); // making sure it doesn't make it to error percentage checking logic

        $this->metrics->expects($this->once())
            ->method('getHealthCounts')
            ->willReturn($healthCounts);

        $this->assertFalse($this->getCircuitBreaker()->isOpen());
    }

    public function testIsOpenErrorPercentageNotBigEnough(): void
    {
        $healthCounts = $this->createMock(HealthCountsSnapshot::class);
        $healthCounts->expects($this->once())
            ->method('getTotal')
            ->willReturn(60); // total is 60, threshold is set to 50.
        $healthCounts->expects($this->once())
            ->method('getErrorPercentage')
            ->willReturn(49); // error percentage threshold is set to 50. 49 should not open the circuit
        $this->metrics->expects($this->once())
            ->method('getHealthCounts')
            ->willReturn($healthCounts);

        $this->assertFalse($this->getCircuitBreaker()->isOpen());
    }

    public function testIsOpenOpensCircuit(): void
    {
        $healthCounts = $this->createMock(HealthCountsSnapshot::class);
        $healthCounts->expects($this->once())
            ->method('getTotal')
            ->willReturn(60); // total is 60, threshold is set to 50.
        $healthCounts->expects($this->once())
            ->method('getErrorPercentage')
            ->willReturn(51); // error percentage threshold is set to 50. 51 should open the circuit
        $this->metrics->expects($this->once())
            ->method('getHealthCounts')
            ->willReturn($healthCounts);

        $this->stateStorage->expects($this->once())
            ->method('openCircuit')
            ->with($this->equalTo('TestCommand'), $this->equalTo(5000)); // 5000 is sleeping window

        $this->assertTrue($this->getCircuitBreaker()->isOpen());
    }

    public function testAllowSingleTest(): void
    {
        $this->stateStorage
            ->method('allowSingleTest')
            ->withConsecutive(['TestCommand', 5000], ['TestCommand', 5000])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->assertFalse($this->getCircuitBreaker()->allowSingleTest());
        $this->assertTrue($this->getCircuitBreaker()->allowSingleTest());
    }

    public function testAllowRequestForceOpen(): void
    {
        $this->stateStorage->expects($this->never())
            ->method('isCircuitOpen'); // making sure it doesn't get to checking if the circuit is open
        $circuitBreaker = $this->getCircuitBreaker(['circuitBreaker' => ['forceOpen' => true]]);
        $this->assertFalse($circuitBreaker->allowRequest());
    }

    public function testAllowRequestForceClose(): void
    {
        $this->stateStorage->expects($this->never())
            ->method('isCircuitOpen'); // making sure it doesn't get to checking if the circuit is open
        $circuitBreaker = $this->getCircuitBreaker(['circuitBreaker' => ['forceClosed' => true]]);
        $this->assertTrue($circuitBreaker->allowRequest());
    }

    public function testMarkSuccessClosesCircuitIfOpenAndResetCounter(): void
    {
        $this->stateStorage->expects($this->once())
            ->method('isCircuitOpen')
            ->with($this->equalTo('TestCommand'))
            ->willReturn(true);
        $this->stateStorage->expects($this->once())
            ->method('closeCircuit')
            ->with($this->equalTo('TestCommand'));
        $this->metrics->expects($this->once())
            ->method('resetCounter');
         $this->getCircuitBreaker()->markSuccess();
    }
}
