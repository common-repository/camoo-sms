<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase\Console;

use Camoo\Sms\Console\AddBulkCommand;
use Camoo\Sms\Console\AddBulkCommandHandler;
use Camoo\Sms\Console\BackgroundProcess;
use Camoo\Sms\Entity\Credential;
use Camoo\Sms\Exception\BackgroundProcessException;
use Exception;
use PHPUnit\Framework\TestCase;

class AddBulkCommandHandlerTest extends TestCase
{
    /** @throws Exception */
    public function testCannotExecute(): void
    {
        $handler = new AddBulkCommandHandler();
        $command = new AddBulkCommand(
            new Credential('key', 'secret'),
            [],
            [],
            ''
        );
        $this->assertEquals(0, $handler->handle($command));
    }

    /** @throws Exception */
    public function testCanHandle(): void
    {
        $process = $this->createMock(BackgroundProcess::class);
        $process->expects($this->once())->method('canBackground')->will($this->returnValue(true));
        $process->expects($this->once())->method('setCommand')->willReturn($process);
        $process->expects($this->once())->method('run')->will($this->returnValue(1));
        $handler = new AddBulkCommandHandler($process);
        $command = new AddBulkCommand(
            new Credential('key', 'secret'),
            [],
            [],
        );

        $this->assertEquals(1, $handler->handle($command));
    }

    public function testThrowsException(): void
    {
        $this->expectException(BackgroundProcessException::class);
        $this->expectExceptionMessage('function "shell_exec" is required for background process');
        $process = $this->createMock(BackgroundProcess::class);
        $process->expects($this->once())->method('canBackground')->will($this->returnValue(false));
        $command = new AddBulkCommand(
            new Credential('key', 'secret'),
            [],
            [],
        );

        (new AddBulkCommandHandler($process))->handle($command);
    }
}
