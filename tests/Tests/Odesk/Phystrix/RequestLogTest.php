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
use Odesk\Phystrix\RequestLog;
use PHPUnit\Framework\TestCase;

class RequestLogTest extends TestCase
{
    protected RequestLog $requestLog;

    protected function setUp(): void
    {
        $this->requestLog = new RequestLog();
    }

    public function testAddAndGet(): void
    {
        $commandA = $this->createMock(AbstractCommand::class);
        $commandB = $this->createMock(AbstractCommand::class);
        $this->assertEmpty($this->requestLog->getExecutedCommands());
        $this->requestLog->addExecutedCommand($commandA);
        $this->requestLog->addExecutedCommand($commandB);
        $this->assertEquals(array($commandA, $commandB), $this->requestLog->getExecutedCommands());
    }

    public function testReadableEmptyLog(): void
    {
        $this->assertSame('', $this->requestLog->getExecutedCommandsAsString());
    }

    public function testReadableLogWithExecutedCommands(): void
    {
        $this->addExecutedCommand('commandA', 100, [AbstractCommand::EVENT_FAILURE]);
        $this->addExecutedCommand('commandA', 50, [AbstractCommand::EVENT_SUCCESS]);
        $this->addExecutedCommand('commandA', 15, [AbstractCommand::EVENT_SUCCESS]);
        $this->addExecutedCommand('commandB', -1, []);
        $this->assertSame(
            'commandA[FAILURE][100ms], commandA[SUCCESS][65ms]x2, commandB[Executed][0ms]',
            $this->requestLog->getExecutedCommandsAsString()
        );
    }

    protected function addExecutedCommand($commandKey, $executionTime, array $events): void
    {
        $command = $this->createMock(AbstractCommand::class);
        $command->expects($this->once())
            ->method('getCommandKey')
            ->willReturn($commandKey);
        $command->expects($this->once())
            ->method('getExecutionTimeInMilliseconds')
            ->willReturn($executionTime);
        $command->expects($this->once())
            ->method('getExecutionEvents')
            ->willReturn($events);
        $this->requestLog->addExecutedCommand($command);
    }
}
