<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase\Response;

use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Sms\Entity\Money;
use Camoo\Sms\Http\Response;
use Camoo\Sms\Response\Balance;
use PHPUnit\Framework\TestCase;

class BalanceTest extends TestCase
{
    private ?Balance $response;

    private ?\Psr\Http\Message\ResponseInterface $responseMock;

    protected function setUp(): void
    {
        parent::setUp();
        $json = file_get_contents(dirname(__DIR__, 2) . '/Fixture/balance.json');
        $this->responseMock = $mock = $this->createMock(ResponseInterface::class);
        $mock->expects($this->once())->method('getStatusCode')->willReturn(200);
        $mock->expects($this->once())->method('getJson')->willReturn(json_decode($json, true));

        $response = new Response($mock);
        $this->response = new Balance($response);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->response = null;
        $this->responseMock = null;
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Balance::class, $this->response);
    }

    public function testCanGetBalance(): void
    {
        $this->assertSame(3704.56, $this->response->getBalance());
    }

    public function testCanGetCurrency(): void
    {
        $this->assertEquals('XAF', $this->response->getCurrency());
    }

    public function testFailure(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $json = '{"message":"KO","balance":{},"code":201}';
        $responseMock->expects($this->once())->method('getStatusCode')->willReturn(200);
        $responseMock->expects($this->once())->method('getJson')->willReturn(json_decode($json, true));
        $response = new Response($responseMock);
        $this->response = new Balance($response);
        $this->assertNull($this->response->getCurrency());
        $this->assertSame(0.00, $this->response->getBalance());
    }

    public function testCanGetMoney(): void
    {
        $money = $this->response->getMoney();
        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame($money->value, $this->response->getBalance());
        $this->assertEquals($money->currency, $this->response->getCurrency());
    }
}
