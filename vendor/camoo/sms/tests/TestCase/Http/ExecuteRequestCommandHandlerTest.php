<?php

namespace CamooSms\Test\TestCase\Http;

use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Sms\Entity\Credential;
use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Http\Client;
use Camoo\Sms\Http\Command\ExecuteRequestCommand;
use Camoo\Sms\Http\Command\ExecuteRequestCommandHandler;
use Camoo\Sms\Http\Response;
use PHPUnit\Framework\TestCase;

class ExecuteRequestCommandHandlerTest extends TestCase
{
    private ?Client $client;

    private ?ClientInterface $httpClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createMock(Client::class);
        $this->httpClient = $this->createMock(ClientInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
    }

    public function testCredentialsIsNull(): void
    {
        $this->expectException(CamooSmsException::class);
        $this->expectExceptionMessage('Credentials are missing !');
        $command = new ExecuteRequestCommand('POST', 'http://localhost');
        $handler = new ExecuteRequestCommandHandler($this->client, $this->httpClient);
        $handler->handle($command);
    }

    public function testPerformThrowsException(): void
    {
        $this->expectException(CamooSmsException::class);
        $command = new ExecuteRequestCommand(
            'POST',
            'http://localhost',
            ['encrypt' => false],
            new Credential('key', 'secret')
        );
        $handler = new ExecuteRequestCommandHandler(null, $this->httpClient);
        $handler->handle($command);
    }

    public function testCanHandle(): void
    {
        $this->expectException(CamooSmsException::class);
        $command = new ExecuteRequestCommand(
            'GET',
            'http://localhost/v2/sms/view.json',
            ['id' => 2],
            new Credential('key', 'secret')
        );
        $response = $this->createMock(\Camoo\Http\Curl\Domain\Response\ResponseInterface::class);
        $data = [
            '_message' => 'succes',
            'sms' => (object)[
                'message-count' => 1,
                'messages' => [
                    0 => (object)[
                        'status' => 0,
                        'message-id' => '1661562859237661562194941475',
                        'message' => 'Hello world',
                        'to' => '+237612345678',
                        'remaining-balance' => '3857.56',
                        'message-price' => 20,
                        'client-ref' => 'abcde',
                    ],
                ],
                'code' => 200,
            ],
        ];
        $response->expects($this->once())->method('getJson')->willReturn($data);
        $this->httpClient->expects($this->once())->method('sendRequest')
            ->will($this->returnValue(new Response($response)));
        $handler = new ExecuteRequestCommandHandler(null, $this->httpClient);
        $handler->handle($command);
    }
}
