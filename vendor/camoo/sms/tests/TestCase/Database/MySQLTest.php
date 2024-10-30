<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase\Database;

use Camoo\Sms\Database\MySQL;
use Camoo\Sms\Entity\DbConfig;
use Camoo\Sms\Interfaces\DriversInterface;
use mysqli;
use PHPUnit\Framework\TestCase;

/**
 * Class MySQLTest
 *
 * @author CamooSarl
 *
 * @covers \Camoo\Sms\Database\MySQL
 */
class MySQLTest extends TestCase
{
    private ?mysqli $mysqli;

    private ?DriversInterface $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $dbConfig = new DbConfig(
            'test',
            'test',
            'topSecret',
            'camoo_sms_send',
            null,
            'localhost'
        );
        $this->mysqli = $this->createMock(mysqli::class);
        $this->driver = new MySQL($dbConfig, $this->mysqli);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->mysqli = null;
        $this->driver = null;
    }

    /** @covers       \Camoo\Sms\Database\MySQL::getInstance */
    public function testGetInstance(): void
    {
        $this->assertInstanceOf(MySQL::class, MySQL::getInstance());
        $this->assertInstanceOf(DbConfig::class, $this->driver->getDbConfig());
    }

    /** @covers       \Camoo\Sms\Database\MySQL::getDB */
    public function testGetDb(): void
    {
        $this->assertInstanceOf(MySQL::class, $this->driver->getDB());
    }

    /** @covers       \Camoo\Sms\Database\MySQL::close */
    public function testClose(): void
    {
        $this->mysqli->expects($this->once())->method('close')->willReturn(true);
        $this->assertTrue($this->driver->getDB()->close());
    }

    /** @covers       \Camoo\Sms\Database\MySQL::insert */
    public function testGetInsertSuccess(): void
    {
        $table = 'messages';

        $variables = [
            'message' => 'Foo Bar',
            'recipient' => '33612345678',
            'message_id' => '12233638',
            'sender' => 'YourCompany',
        ];
        $this->mysqli->expects($this->once())->method('query')
            ->will($this->returnValue($this->createMock(\mysqli_result::class)));
        $this->mysqli->expects($this->any())->method('escape_string')->will($this->returnValue('a mocked string'));

        $this->assertTrue($this->driver->getDB()->insert($table, $variables));
    }

    /**
     * @covers       \Camoo\Sms\Database\MySQL::escapeString
     *
     * @dataProvider stringEscapedDataProvider
     */
    public function testEscapeStr(string $str): void
    {
        $this->mysqli->expects($this->once())->method('escape_string')->will($this->returnValue(trim($str)));
        $this->assertIsString($this->driver->getDB()->escapeString($str));
    }

    /**
     * @covers       \Camoo\Sms\Database\MySQL::insert
     *
     * @dataProvider insertDataProviderFailure
     */
    public function testGetInsertFailure(array $variables): void
    {
        $table = 'messages';

        $this->mysqli->expects($this->any())->method('query')
            ->will($this->returnValue(false));
        $this->mysqli->expects($this->any())->method('escape_string')->will($this->returnValue('a mocked string'));

        $this->assertFalse($this->driver->getDB()->insert($table, $variables));
    }

    public function testCanGetError(): void
    {
        $this->driver = new MySQL(new DbConfig('name', 'user', 'password', 'table'), $this->mysqli);
        $this->assertEmpty($this->driver->getDB()->getError());
    }

    public function testGetDBReturnsNull(): void
    {
        $mysql = new MySQL();
        $this->assertNull($mysql->getDB());
    }

    public function insertDataProviderFailure(): array
    {
        return [
            [
                [],
            ],
            [

                [
                    'message' => 'Foo Bar',
                    'recipient' => '33612345678',
                    'message_id' => '12233638',
                    'sender' => 'YourCompany',
                ],
            ],

        ];
    }

    public function stringEscapedDataProvider(): array
    {
        return [
            [
                '"SELECT 1=1;" ',
            ],
            [
                ' "some good string"',
            ],

        ];
    }
}
