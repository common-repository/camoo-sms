<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase\Response;

use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Sms\Http\Response;
use Camoo\Sms\Response\TopUp;
use PHPUnit\Framework\TestCase;

class TopUpTest extends TestCase
{
    private ?\Psr\Http\Message\ResponseInterface $responseMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->responseMock = $this->createMock(ResponseInterface::class);
    }

    public function testCanHandleResponse(): void
    {
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $return = json_encode([
            'status' => 'OK',
            'message' => 'pending',
            'topup' => [
                'id' => '04186610-3bda-4f30-9aaf-e4638b00d5c2',
                'amount' => 4500,
                'currency' => 'XAF',
                'status' => 'PENDING',
                'network' => 'orange',
            ],
            'code' => 200,
        ]);
        $this->responseMock->expects($this->once())->method('getStatusCode')->willReturn(200);
        $this->responseMock->expects($this->once())->method('getJson')->willReturn(json_decode($return, true));
        $response = new Response($this->responseMock);

        $result = new TopUp($response);

        $this->assertEquals(4500, $result->getAmount());
        $this->assertSame('XAF', $result->getCurrency());
        $this->assertSame('PENDING', $result->getStatus());
        $this->assertSame('orange', $result->getNetwork());
        $this->assertSame('04186610-3bda-4f30-9aaf-e4638b00d5c2', $result->getId());
        $this->assertInstanceOf(Response::class, $result->getResponse());
    }

    public function testAmountIsZero(): void
    {
        $return = json_encode([
            'status' => 'OK',
            'message' => 'pending',
            'topup' => [
                'id' => '04186610-3bda-4f30-9aaf-e4638b00d5ak',
            ],
            'code' => 200,
        ]);
        $this->responseMock->expects($this->once())->method('getStatusCode')->willReturn(200);
        $this->responseMock->expects($this->once())->method('getJson')->willReturn(json_decode($return, true));
        $response = new Response($this->responseMock);

        $result = new TopUp($response);

        $this->assertSame(0.00, $result->getAmount());
    }
}
