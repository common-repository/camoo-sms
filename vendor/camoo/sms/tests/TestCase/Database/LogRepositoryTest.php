<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase\Database;

use Camoo\Sms\Database\Repository\LogRepository;
use Camoo\Sms\Entity\DbConfig;
use Camoo\Sms\Interfaces\DriversInterface;
use PHPUnit\Framework\TestCase;

final class LogRepositoryTest extends TestCase
{
    public function testSaveWithoutDataReturnsFalse(): void
    {
        $dbConf = new DbConfig('name', 'user', 'password', 'table');
        $log = new LogRepository($dbConf);
        $this->assertFalse($log->save([]));
    }

    public function testSaveWithoutConfigTableReturnsFalse(): void
    {
        $dbConf = new DbConfig('name', 'user', 'password', '');
        $log = new LogRepository($dbConf);
        $this->assertFalse($log->save(['id' => 1]));
    }

    public function testCanSave(): void
    {
        $driver = $this->createMock(DriversInterface::class);
        $driver->expects($this->once())->method('getDB')->willReturn($driver);
        $driver->expects($this->once())->method('insert')
            ->with('table', ['id' => 1])
            ->willReturn(true);

        $dbConf = new DbConfig('name', 'user', 'password', 'table');
        $log = new LogRepository($dbConf, $driver);
        $this->assertTrue($log->save(['id' => 1]));
    }

    public function testCanNotSaveWhenDBReturnsNull(): void
    {
        $driver = $this->createMock(DriversInterface::class);
        $driver->expects($this->once())->method('getDB')->willReturn(null);
        $dbConf = new DbConfig('name', 'user', 'password', 'table');
        $log = new LogRepository($dbConf, $driver);
        $this->assertFalse($log->save(['id' => 1]));
    }
}
