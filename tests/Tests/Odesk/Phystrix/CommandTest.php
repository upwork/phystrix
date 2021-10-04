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

use Odesk\Phystrix\AbstractCommand;
use Odesk\Phystrix\Exception\RuntimeException;
use Odesk\Phystrix\RequestCache;
use Odesk\Phystrix\RequestLog;
use Laminas\Config\Config;
use Odesk\Phystrix\CircuitBreakerFactory;
use Odesk\Phystrix\CircuitBreakerInterface;
use Odesk\Phystrix\CommandMetricsFactory;
use Odesk\Phystrix\CommandMetrics;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Odesk\Phystrix\Exception\BadRequestException;

class CommandTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $circuitBreakerFactory;

    /**
     * @var MockObject|CircuitBreakerInterface
     */
    protected $circuitBreaker;

    /**
     * @var MockObject|CommandMetricsFactory
     */
    protected $commandMetricsFactory;

    /**
     * @var MockObject|CommandMetrics
     */
    protected $commandMetrics;
    protected CommandMock $command;
    protected RequestLog $requestLog;

    protected function setUp(): void
    {
        $this->circuitBreakerFactory = $this->createMock(CircuitBreakerFactory::class);
        $this->circuitBreaker = $this->createMock(CircuitBreakerInterface::class);
        $this->commandMetricsFactory = $this->createMock(CommandMetricsFactory::class);
        $this->commandMetrics = $this->createMock(CommandMetrics::class);

        $this->command = new CommandMock();
        $this->command->setCommandMetricsFactory($this->commandMetricsFactory);
        $this->command->setRequestCache(new RequestCache());
        $this->requestLog = new RequestLog();
        $this->command->setRequestLog($this->requestLog);
        $this->command->setCircuitBreakerFactory($this->circuitBreakerFactory);
        $this->command->setConfig(new Config([
            'fallback' => [
                'enabled' => true,
            ],
            'requestCache' => [
                'enabled' => true,
            ],
            'requestLog' => [
                'enabled' => true,
            ],
        ], true));
    }

    /**
     * Something many tests need
     *
     * @param bool $allowRequest (Optional) Whether CB should allow the request
     */
    protected function setUpCommonExpectations($allowRequest = true)
    {
        $this->circuitBreakerFactory->expects($this->once())
            ->method('get')
            ->with('Tests\Odesk\Phystrix\CommandMock')
            ->will($this->returnValue($this->circuitBreaker));
        $this->circuitBreaker->expects($this->once())
            ->method('allowRequest')
            ->will($this->returnValue($allowRequest));
        $this->commandMetricsFactory->expects($this->atLeastOnce())
            ->method('get')
            ->with('Tests\Odesk\Phystrix\CommandMock')
            ->will($this->returnValue($this->commandMetrics));
    }

    /**
     * Makes the command run for 555 milliseconds
     */
    protected function setUpExecutionDelayExpectations()
    {
        // testing execution time was recorded correctly
        global $globalUnitTestPhystrixMicroTime;
        $globalUnitTestPhystrixMicroTime = 1369861562.1266;
        $this->command->simulateDelay = true;
    }

    public function testSetTestMergesConfig(): void
    {
        $command = new CommandMock();
        $command->setConfig(new Config(['a' => 1], true));
        $command->setConfig(new Config(['b' => 2], true));
        $this->assertEquals(new Config(['a' => 1, 'b' => 2], true), $command->getConfig());

        $command->setConfig(new Config(['c' => 3], true), false); // false to skip merge
        $this->assertEquals(new Config(['c' => 3], true), $command->getConfig());
    }

    public function testExecuteDefaultCommandKey()
    {
        // default command key is the class name
        $this->setUpCommonExpectations();
        $this->command->execute();
    }

    public function testExecutePresetCommandKey()
    {
        $this->commandMetricsFactory->expects($this->atLeastOnce())
            ->method('get')
            ->with('PresetTestCommandKey')
            ->will($this->returnValue($this->commandMetrics));
        $reflection = new \ReflectionClass('Tests\Odesk\Phystrix\CommandMock');
        $property = $reflection->getProperty('commandKey');
        $property->setAccessible(true);
        $property->setValue($this->command, 'PresetTestCommandKey');
        $this->commandMetricsFactory->expects($this->atLeastOnce())
            ->method('get')
            ->with('PresetTestCommandKey')
            ->will($this->returnValue($this->commandMetrics));

        $this->circuitBreakerFactory->expects($this->once())
            ->method('get')
            ->with('PresetTestCommandKey')
            ->will($this->returnValue($this->circuitBreaker));
        $this->circuitBreaker->expects($this->once())
            ->method('allowRequest')
            ->will($this->returnValue(true));

        $this->command->execute();
    }

    public function testExecute()
    {
        $this->setUpCommonExpectations();
        $this->setUpExecutionDelayExpectations();
        $this->circuitBreaker->expects($this->once())
            ->method('markSuccess');
        $this->commandMetrics->expects($this->once())
            ->method('markSuccess');
        $this->assertEmpty($this->requestLog->getExecutedCommands());
        $this->assertEquals(null, $this->command->getExecutionTimeInMilliseconds());
        $this->assertEquals('run result', $this->command->execute());
        $this->assertEquals(array(AbstractCommand::EVENT_SUCCESS), $this->command->getExecutionEvents());
        $this->assertEquals(array($this->command), $this->requestLog->getExecutedCommands());
        $this->assertEquals(555, $this->command->getExecutionTimeInMilliseconds());
    }

    public function testRequestLogOff()
    {
        $this->setUpCommonExpectations();
        $this->command->setConfig(new Config(array(
            'requestLog' => array(
                'enabled' => false,
            ),
        )));
        $this->assertEmpty($this->requestLog->getExecutedCommands());
        $this->assertEquals('run result', $this->command->execute());
        $this->assertEmpty($this->requestLog->getExecutedCommands());
    }

    /**
     * Ensure command does not break when configured to log, though one hasn't been injected
     *
     * @dataProvider configBoolProvider
     *
     * @param bool $logEnabled  whether config is set to use request log
     */
    public function testRequestLogNotInjected($logEnabled): void
    {
        // Duplicate some of the class setup in order to bypass requestLog generation
        $command = new CommandMock();
        $command->setCommandMetricsFactory($this->commandMetricsFactory);
        $command->setCircuitBreakerFactory($this->circuitBreakerFactory);
        $command->setRequestCache(new RequestCache());

        $command->setConfig(new Config(array(
            'requestLog' => array(
                'enabled' => $logEnabled,
            ),
        ), true));

        $this->setUpCommonExpectations();

        $this->assertEquals('run result', $this->command->execute());
    }

    /**
     * Ensure command does not break when configured to cache, though cache hasn't been injected
     *
     * @dataProvider configBoolProvider
     *
     * @param bool $cacheEnabled  whether config is set to use request log
     */
    public function testRequestCacheNotInjected($cacheEnabled)
    {
        // Duplicate some of the class setup in order to bypass requestLog generation
        $command = new CommandMock();
        $command->setCommandMetricsFactory($this->commandMetricsFactory);
        $command->setCircuitBreakerFactory($this->circuitBreakerFactory);
        $command->setRequestLog($this->requestLog);

        $command->setConfig(new Config(array(
            'requestCache' => array(
                'enabled' => $cacheEnabled,
            ),
        ), true));

        $this->setUpCommonExpectations();

        $this->assertEquals('run result', $this->command->execute());
    }

    public function configBoolProvider(): array
    {
        return [
            'config enabled'  => [true],
            'config disabled' => [false],
        ];
    }

    public function testExecuteRequestNotAllowed(): void
    {
        $this->setUpCommonExpectations(false);

        $this->commandMetrics->expects($this->once())
            ->method('markShortCircuited');
        $this->circuitBreaker->expects($this->never())
            ->method('markSuccess');
        $this->commandMetrics->expects($this->never())
            ->method('markSuccess');
        $this->commandMetrics->expects($this->never())
            ->method('markFailure');

        $this->assertEquals('fallback result', $this->command->execute());
        $this->assertEquals(
            array(AbstractCommand::EVENT_SHORT_CIRCUITED, AbstractCommand::EVENT_FALLBACK_SUCCESS),
            $this->command->getExecutionEvents()
        );
        // execution time is not recorded
        $this->assertEquals(null, $this->command->getExecutionTimeInMilliseconds());
    }

    public function testExecuteRunException()
    {
        $this->setUpCommonExpectations();
        $this->setUpExecutionDelayExpectations();
        $this->commandMetrics->expects($this->never()) // because fallback is enabled and working
            ->method('markExceptionThrown');
        $this->commandMetrics->expects($this->once())
            ->method('markFallbackSuccess');
        $this->commandMetrics->expects($this->once())
            ->method('markFailure');
        $this->command->throwException = true;
        $this->assertEquals('fallback result', $this->command->execute());
        $this->assertEquals(
            array(AbstractCommand::EVENT_FAILURE, AbstractCommand::EVENT_FALLBACK_SUCCESS),
            $this->command->getExecutionEvents()
        );
        $this->assertEquals(555, $this->command->getExecutionTimeInMilliseconds());
        $this->assertEquals(new \DomainException('could not run'), $this->command->getExecutionException());
    }

    public function testExecuteFallbackFailed()
    {
        $this->setUpCommonExpectations();
        $this->commandMetrics->expects($this->never())
            ->method('markFallbackSuccess');
        $this->commandMetrics->expects($this->once())
            ->method('markFallbackFailure');
        $this->commandMetrics->expects($this->once())
            ->method('markExceptionThrown');
        $this->commandMetrics->expects($this->once())
            ->method('markFailure');
        $this->command->throwException = true;
        $this->command->throwExceptionInFallback = true;
        try {
            $this->command->execute();
            $this->fail('Exception expected');
        } catch (\Exception $exception) {}
        $this->assertInstanceOf('Odesk\Phystrix\Exception\RuntimeException', $exception);
        $this->assertEquals('could not run and failed retrieving fallback', $exception->getMessage());
        $this->assertEquals(
            array(
                AbstractCommand::EVENT_FAILURE,
                AbstractCommand::EVENT_FALLBACK_FAILURE,
                AbstractCommand::EVENT_EXCEPTION_THROWN
            ),
            $this->command->getExecutionEvents()
        );
        $this->assertEquals($exception->getPrevious(), $this->command->getExecutionException());
    }

    public function testRuntimeExceptionPopulated()
    {
        $this->setUpCommonExpectations();
        $this->command->throwException = true;
        $this->command->throwExceptionInFallback = true;
        try {
            $this->command->execute();
            $this->fail('Odesk\Phystrix\Exception\RuntimeException was expected');
        } catch (RuntimeException $exception) {
            $this->assertInstanceOf('Odesk\Phystrix\Exception\RuntimeException', $exception);
            $this->assertInstanceOf('DomainException', $exception->getPrevious());
            $this->assertInstanceOf('DomainException', $exception->getFallbackException());
            $this->assertEquals('Tests\Odesk\Phystrix\CommandMock', $exception->getCommandClass());
            $this->assertEquals('could not run and failed retrieving fallback', $exception->getMessage());
            $this->assertEquals('error falling back', $exception->getFallbackException()->getMessage());
        }
    }

    public function testBadRequestExceptionTracksNoMetrics()
    {
        $this->setUpCommonExpectations();
        $this->commandMetrics->expects($this->never())
            ->method('markFallbackSuccess');
        $this->commandMetrics->expects($this->never())
            ->method('markFallbackFailure');
        $this->commandMetrics->expects($this->never())
            ->method('markExceptionThrown');
        $this->commandMetrics->expects($this->never())
            ->method('markFailure');
        $this->command->throwBadRequestException = true;
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('special treatment');
        $this->command->execute();
        // no events logged in this case
        $this->assertEquals(array(), $this->command->getExecutionEvents());
        // execution time is not recorded
        $this->assertEquals(null, $this->command->getExecutionTimeInMilliseconds());
    }

    public function testShortCircuitedExceptionMessage(): void
    {
        $this->setUpCommonExpectations(false);
        $this->command->throwException = true;
        $this->command->throwExceptionInFallback = true;
        try {
            $this->command->execute();
            $this->fail('Odesk\Phystrix\Exception\RuntimeException was expected');
        } catch (RuntimeException $exception) {
            $this->assertInstanceOf('Odesk\Phystrix\Exception\RuntimeException', $exception);
            $this->assertEquals('Short-circuited and failed retrieving fallback', $exception->getMessage());
        }
    }

    public function testExecuteFallbackDisabled()
    {
        $this->setUpCommonExpectations();
        $this->command->setConfig(new Config(array('fallback' => array('enabled' => false))));
        $this->commandMetrics->expects($this->never()) // because fallback is disabled
            ->method('markFallbackSuccess');
        $this->commandMetrics->expects($this->never()) // because fallback is disabled
            ->method('markFallbackFailure');
        $this->commandMetrics->expects($this->once())
            ->method('markExceptionThrown');
        $this->commandMetrics->expects($this->once())
            ->method('markFailure');
        $this->command->throwException = true;
        try {
            $this->command->execute();
            $this->fail('Exception expected');
        } catch (\Exception $exception) {}
        $this->assertInstanceOf('Odesk\Phystrix\Exception\RuntimeException', $exception);
        $this->assertEquals('could not run and fallback disabled', $exception->getMessage());
        $this->assertEquals(
            array(
                AbstractCommand::EVENT_FAILURE,
                AbstractCommand::EVENT_EXCEPTION_THROWN
            ),
            $this->command->getExecutionEvents()
        );
    }

    public function testRequestCacheHit()
    {
        $this->commandMetricsFactory->expects($this->atLeastOnce())
            ->method('get')
            ->with('Tests\Odesk\Phystrix\CommandMock')
            ->will($this->returnValue($this->commandMetrics));
        /** @var RequestCache|\PHPUnit_Framework_MockObject_MockObject $requestCache */
        $requestCache = $this->createMock(RequestCache::class);
        $requestCache->expects($this->once())
            ->method('exists')
            ->with('Tests\Odesk\Phystrix\CommandMock', 'test-cache-key')
            ->will($this->returnValue(true));
        $requestCache->expects($this->once())
            ->method('get')
            ->with('Tests\Odesk\Phystrix\CommandMock', 'test-cache-key')
            ->will($this->returnValue('result from cache'));
        $this->command->cacheKey = 'test-cache-key';
        $this->command->setRequestCache($requestCache);
        $this->circuitBreakerFactory->expects($this->never())->method('get');
        $this->commandMetrics->expects($this->once())->method('markResponseFromCache');
        $this->assertEquals('result from cache', $this->command->execute());
        $this->assertEquals(array(AbstractCommand::EVENT_RESPONSE_FROM_CACHE), $this->command->getExecutionEvents());
    }

    /**
     * Test case for cache miss scenario
     */
    public function testRequestCacheMiss()
    {
        $this->setUpCommonExpectations();
        /** @var RequestCache|\PHPUnit_Framework_MockObject_MockObject $requestCache */
        $requestCache = $this->createMock(RequestCache::class);
        $requestCache->expects($this->once())
            ->method('exists')
            ->with('Tests\Odesk\Phystrix\CommandMock', 'test-cache-key')
            ->will($this->returnValue(false));
        $requestCache->expects($this->never())
            ->method('get');
        $requestCache->expects($this->once())
            ->method('put')
            ->with('Tests\Odesk\Phystrix\CommandMock', 'test-cache-key', 'run result');
        $this->command->cacheKey = 'test-cache-key';
        $this->command->setRequestCache($requestCache);
        $this->commandMetrics->expects($this->never())->method('markResponseFromCache');
        $this->assertEquals('run result', $this->command->execute());
        $this->assertEquals(array('SUCCESS'), $this->command->getExecutionEvents());
    }

    public function testSavesResultToCache()
    {
        $this->setUpCommonExpectations();
        /** @var RequestCache|\PHPUnit_Framework_MockObject_MockObject $requestCache */
        $requestCache = $this->createMock(RequestCache::class);
        $requestCache->expects($this->once())
            ->method('put')
            ->with('Tests\Odesk\Phystrix\CommandMock', 'test-cache-key', 'run result');
        $this->command->cacheKey = 'test-cache-key';
        $this->command->setRequestCache($requestCache);
        $this->assertEquals('run result', $this->command->execute());
    }

    public function testRequestCacheDisabled()
    {
        $this->setUpCommonExpectations();
        $this->command->setConfig(new Config(array('requestCache' => array('enabled' => false))));
        /** @var RequestCache|\PHPUnit_Framework_MockObject_MockObject $requestCache */
        $requestCache = $this->createMock(RequestCache::class);
        $requestCache->expects($this->never())
            ->method('get');
        $requestCache->expects($this->never())
            ->method('put');
        $this->command->cacheKey = 'test-cache-key';
        $this->command->setRequestCache($requestCache);
        $this->assertEquals('run result', $this->command->execute());
    }


    public function testRequestCacheGetCacheKeyNotImplemented()
    {
        $this->setUpCommonExpectations();
        /** @var RequestCache|\PHPUnit_Framework_MockObject_MockObject $requestCache */
        $requestCache = $this->createMock(RequestCache::class);
        $requestCache->expects($this->never())
            ->method('get');
        $requestCache->expects($this->never())
            ->method('put');
        $this->command->setRequestCache($requestCache);
        $this->assertEquals('run result', $this->command->execute());
    }
}
