<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase;

use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Sms\Balance;
use Camoo\Sms\Entity\Credential;
use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Http\Command\ExecuteRequestCommand;
use Camoo\Sms\Http\Command\ExecuteRequestCommandHandler;
use PHPUnit\Framework\TestCase;

/**
 * Class BalanceTest
 *
 * @author CamooSarl
 *
 * @covers \Camoo\Sms\Balance
 */
class BalanceTest extends TestCase
{
    /** @covers \Camoo\Sms\Balance::get */
    public function testGetSuccess(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $httpClient = $this->createMock(ClientInterface::class);
        $handler = new ExecuteRequestCommandHandler(null, $httpClient);
        /** @var Balance|\Camoo\Sms\Objects\Balance $balance */
        $balance = Balance::create('key', 'secret', $handler);
        $return = json_encode([
            '_message' => 'succes',
            'balance' => [
                'balance' => 220,
                'currency' => 'XAF',
            ],
            'code' => 200,
        ]);

        $response->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $response->expects($this->once())->method('getJson')->willReturn(json_decode($return, true));
        $httpClient->expects($this->once())->method('sendRequest')
            ->will($this->returnValue($response));
        $result = $balance->get();
        $this->assertEquals(220, $result->getBalance());
        $this->assertSame('XAF', $result->getCurrency());
    }

    /** @covers \Camoo\Sms\Balance::get */
    public function testGetFailure(): void
    {
        $this->expectException(CamooSmsException::class);
        $handler = $this->createMock(ExecuteRequestCommandHandler::class);
        /** @var Balance|\Camoo\Sms\Objects\Balance $balance */
        $balance = Balance::create('key', 'secret', $handler);
        $command = new ExecuteRequestCommand(
            'GET',
            'https://api.camoo.cm/v1/sms/balance.json',
            [],
            new Credential('key', 'secret')
        );
        $handler->expects($this->once())->method('handle')->with($command)
            ->will($this->returnValue(new CamooSmsException('Test')));
        $balance->get();
    }
}
