<?php

namespace CamooSms\Test\TestCase\Console;

use Camoo\Sms\Console\BackgroundProcess;
use Camoo\Sms\Console\OperatingSystem;
use Camoo\Sms\Exception\BackgroundProcessException;
use Camoo\Sms\Interfaces\OperatingSystemInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class BackgroundProcessTest
 *
 * @author CamooSarl
 *
 * @covers \Camoo\Sms\Console\BackgroundProcess
 */
class BackgroundProcessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->os = $this->createMock(OperatingSystemInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->os = null;
    }

    /** @dataProvider commandDataProvider */
    public function testInstance(string $command): void
    {
        $this->assertInstanceOf(
            BackgroundProcess::class,
            new BackgroundProcess($command, new OperatingSystem())
        );
    }

    /**
     * @covers \Camoo\Sms\Console\BackgroundProcess::run
     *
     * @depends testInstance
     *
     * @dataProvider commandDataProvider
     */
    public function testRunSuccess(string $command): void
    {
        $run = new BackgroundProcess($command);
        $this->assertIsInt($run->run());
    }

    /**
     * @covers \Camoo\Sms\Console\BackgroundProcess::run
     *
     * @depends testInstance
     */
    public function testRunWithNullThrowsException(): void
    {
        $this->expectException(BackgroundProcessException::class);
        $run = new BackgroundProcess(null);
        $run->run();
    }

    /**
     * @covers \Camoo\Sms\Console\BackgroundProcess::run
     *
     * @depends testInstance
     */
    public function testRunWithEmptyOSThrowsException(): void
    {
        $this->expectException(BackgroundProcessException::class);
        $this->expectExceptionMessage('Operating System cannot be determined');

        $this->os->expects($this->once())->method('get')->willReturn('');
        $process = new BackgroundProcess('whoami', $this->os);
        $process->run();
    }

    /**
     * @covers \Camoo\Sms\Console\BackgroundProcess::run
     *
     * @depends testInstance
     *
     * @testWith        ["whoami"]
     */
    public function testRunOther(string $command): void
    {
        $this->expectException(BackgroundProcessException::class);
        $this->os->expects($this->once())->method('get')->willReturn('OTHER');
        $process = new BackgroundProcess($command, $this->os);
        $process->run();
    }

    /**
     * @covers \Camoo\Sms\Console\BackgroundProcess::run
     *
     * @depends testInstance
     *
     * @testWith        ["whoami"]
     */
    public function testRunWin(string $command): void
    {
        $this->os->expects($this->once())->method('get')->willReturn('WIN2023');
        $process = new BackgroundProcess($command, $this->os);
        $this->assertSame(0, $process->run());
    }

    public function testCanSetCommand(): void
    {
        $process = new BackgroundProcess();
        $this->assertInstanceOf(BackgroundProcess::class, $process->setCommand('whoami'));
    }

    public function testCanBackground(): void
    {
        $process = new BackgroundProcess();
        $this->assertIsBool($process->canBackground());
    }

    public function commandDataProvider(): array
    {
        return [
            ['ls -lart'],
            ['whoami'],
        ];
    }
}
