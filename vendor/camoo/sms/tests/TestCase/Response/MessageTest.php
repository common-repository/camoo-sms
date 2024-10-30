<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase\Response;

use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Sms\Http\Response;
use Camoo\Sms\Response\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    private ?Message $response;

    private ?\Psr\Http\Message\ResponseInterface $responseMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->responseMock = $this->createMock(ResponseInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->response = null;
    }

    public function testCanGetViewResponse(): void
    {
        $return = file_get_contents(dirname(__DIR__, 2) . '/Fixture/sms-view.json');

        $this->responseMock->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $this->responseMock->expects($this->once())->method('getJson')->willReturn(json_decode($return, true));
        $response = new Response($this->responseMock);
        $this->response = new Message($response);
        $this->assertEquals('1661562859237661562194941475', $this->response->getId());
        $this->assertEquals('delivered', $this->response->getStatus());
        $this->assertEquals('+237612345678', $this->response->getTo());
        $this->assertEquals(17, $this->response->getMessagePrice());
        $this->assertEquals('Hello Kmer World! Déjà vu!', $this->response->getMessage());
        $this->assertEquals('2023-02-03T08:50:34+00:00', $this->response->getCreatedAt()->format(DATE_ATOM));
        $this->assertEquals('2023-02-03T10:00:40+00:00', $this->response->getStatusTime()->format(DATE_ATOM));
        $this->assertSame('CamooTest', $this->response->getSmsSender());
        $this->assertEmpty($this->response->getReference());
    }

    public function testCanGeSentResponse(): void
    {
        $return = file_get_contents(dirname(__DIR__, 2) . '/Fixture/sms-sent.json');
        $this->responseMock->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $this->responseMock->expects($this->once())->method('getJson')->willReturn(json_decode($return, true));
        $response = new Response($this->responseMock);
        $this->response = new Message($response);
        $this->assertEquals('1981562859237661562194941475', $this->response->getId());
        $this->assertEquals('0', $this->response->getStatus());
        $this->assertEquals('+237612345678', $this->response->getTo());
        $this->assertEquals(20, $this->response->getMessagePrice());
        $this->assertEquals('Hello world', $this->response->getMessage());
        $this->assertNull($this->response->getCreatedAt());
        $this->assertNull($this->response->getStatusTime());
        $this->assertNull($this->response->getSmsSender());
        $this->assertEmpty($this->response->getReference());
    }

    public function testViewMessageWithEmptyResponse(): void
    {
        $this->responseMock->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $this->responseMock->expects($this->once())->method('getJson')->willReturn(null);
        $response = new Response($this->responseMock);
        $this->response = new Message($response);
        $this->assertNull($this->response->getId());
        $this->assertEquals('sent', $this->response->getStatus());
        $this->assertNull($this->response->getTo());
        $this->assertNull($this->response->getMessagePrice());
        $this->assertNull($this->response->getMessage());
        $this->assertNull($this->response->getCreatedAt());
        $this->assertNull($this->response->getStatusTime());
        $this->assertNull($this->response->getSmsSender());
        $this->assertNull($this->response->getReference());
    }
}
