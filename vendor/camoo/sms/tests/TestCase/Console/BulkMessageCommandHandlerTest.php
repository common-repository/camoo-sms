<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase\Console;

use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Sms\Console\BulkMessageCommand;
use Camoo\Sms\Console\BulkMessageCommandHandler;
use Camoo\Sms\Entity\CallbackDto;
use Camoo\Sms\Entity\DbConfig;
use Camoo\Sms\Exception\BulkSendException;
use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Http\Command\ExecuteRequestCommandHandler;
use Camoo\Sms\Http\Response;
use Camoo\Sms\Interfaces\DriversInterface;
use Camoo\Sms\Message;
use PHPUnit\Framework\TestCase;

final class BulkMessageCommandHandlerTest extends TestCase
{
    private ?BulkMessageCommand $command;

    private ?ResponseInterface $response = null;

    private ?ClientInterface $client = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = $this->createMock(ResponseInterface::class);
        $this->client = $this->createMock(ClientInterface::class);
        $this->driver = $this->createMock(DriversInterface::class);
        $this->dbConfig = new DbConfig('dbName', 'dbUser', 'password', 'table');
        $this->command = new BulkMessageCommand(
            [
                'to' => [
                    ['name' => 'John Doe', 'mobile' => '+237612345678'],
                    ['name' => 'Jeanne Doe', 'mobile' => '+237612345679'],
                    ['name' => 'Junior Doe', 'mobile' => '+237612345680'],
                ],
                'message' => 'Hello world',
                'from' => 'UnitTest',
            ],
            new CallbackDto($this->driver, $this->dbConfig)
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->command = null;
        $this->response = null;
        $this->client = null;
    }

    public function provideDifferentRecipients(): array
    {
        return [
            'Multi-Dimensional-Array' => [
                [
                    ['name' => 'John Doe', 'mobile' => '+237612345678'],
                    ['name' => 'Jeanne Doe', 'mobile' => '+237612345679'],
                    ['name' => 'Junior Doe', 'mobile' => '+237612345680'],
                ],
                3,
            ],
            'Non-Multi-dimensional' => [
                [
                    '+237612345678',
                    '+237612345675',
                ],
                1, // because we send one message to all the recipients
            ],
        ];
    }

    /** @dataProvider provideDifferentRecipients */
    public function testCanHandle(iterable $to, int $postSentCount): void
    {
        $response = [
            '_message' => 'succes',
            'sms' => [
                'message-count' => 1,
                'messages' => [
                    0 => [
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

        $this->command = new BulkMessageCommand(
            [
                'to' => $to,
                'message' => 'Hello world',
                'from' => 'UnitTest',
            ],
            new CallbackDto($this->driver, $this->dbConfig)
        );

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $responseMock->expects($this->once())->method('getJson')->willReturn($response);

        $clientResponse = new Response($responseMock);
        $messageResponse = new \Camoo\Sms\Response\Message($clientResponse);
        $message = $this->createMock(Message::class);
        $message->expects($this->exactly($postSentCount))->method('send')->willReturn($messageResponse);
        $this->driver->expects($this->exactly($postSentCount))->method('getDB')->willReturn($this->driver);
        $this->driver->expects($this->exactly($postSentCount))->method('insert')->with('table', [
            'message' => 'Hello world',
            'recipient' => '+237612345678',
            'message_id' => '1661562859237661562194941475',
            'sender' => 'UnitTest',
            'response' => json_encode(array_merge(['status' => 'OK'], $response))])->willReturn(true);
        $handler = new BulkMessageCommandHandler($message);
        $sent = $handler->handle($this->command);
        $this->assertSame($postSentCount, iterator_count($sent));
    }

    public function testHandleContinueOnError(): void
    {
        $response = [
            '_message' => 'succes',
            'sms' => [
                'message-count' => 1,
                'messages' => [
                    0 => [
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

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $responseMock->expects($this->exactly(1))->method('getJson')->willReturn($response);

        $clientResponse = new Response($responseMock);

        $messageResponse = new \Camoo\Sms\Response\Message($clientResponse);
        $message = $this->createMock(Message::class);
        $message->expects($this->exactly(3))->method('send')->willReturnOnConsecutiveCalls(
            $messageResponse,
            $messageResponse,
            self::throwException(new CamooSmsException('Oops!'))
        );
        $this->driver->expects($this->exactly(2))->method('getDB')->willReturn($this->driver);
        $this->driver->expects($this->exactly(2))->method('insert')->with('table', [
            'message' => 'Hello world',
            'recipient' => '+237612345678',
            'message_id' => '1661562859237661562194941475',
            'sender' => 'UnitTest',
            'response' => json_encode(array_merge(['status' => 'OK'], $response))])->willReturn(true);
        $handler = new BulkMessageCommandHandler($message);
        $sent = $handler->handle($this->command);
        $this->assertSame(2, iterator_count($sent));
    }

    public function testCanHandleButNotLogDbWhenDbConfigIsMissing(): void
    {
        $command = new BulkMessageCommand(
            [
                'to' => [
                    ['name' => 'John Doe', 'mobile' => '+237612345678'],

                ],
                'message' => 'Hello world',
                'from' => 'UnitTest',
            ],
            new CallbackDto($this->driver, null)
        );

        $response = [
            '_message' => 'succes',
            'sms' => [
                'message-count' => 1,
                'messages' => [
                    0 => [
                        'status' => 0,
                        'message-id' => '166156285923766156219494148',
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

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $responseMock->expects($this->once())->method('getJson')->willReturn($response);

        $clientResponse = new Response($responseMock);
        $messageResponse = new \Camoo\Sms\Response\Message($clientResponse);
        $message = $this->createMock(Message::class);
        $message->expects($this->once())->method('send')->willReturn($messageResponse);
        $this->driver->expects($this->never())->method('getDB');
        $this->driver->expects($this->never())->method('insert');
        $handler = new BulkMessageCommandHandler($message);
        $sent = $handler->handle($command);
        $this->assertSame(1, iterator_count($sent));
    }

    public function testCanHandleButCatchDbWhenDbError(): void
    {
        $command = new BulkMessageCommand(
            [
                'to' => [
                    ['name' => 'John Doe', 'mobile' => '+237612345678'],

                ],
                'message' => 'Hello world',
                'from' => 'UnitTest',
            ],
            new CallbackDto($this->driver, $this->dbConfig)
        );

        $response = [
            '_message' => 'succes',
            'sms' => [
                'message-count' => 1,
                'messages' => [
                    0 => [
                        'status' => 0,
                        'message-id' => '166156285923766156219494199',
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

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $responseMock->expects($this->once())->method('getJson')->willReturn($response);

        $clientResponse = new Response($responseMock);
        $messageResponse = new \Camoo\Sms\Response\Message($clientResponse);
        $message = $this->createMock(Message::class);
        $message->expects($this->once())->method('send')->willReturn($messageResponse);
        $this->driver->expects($this->once())->method('getDB')->willReturn($this->driver);
        $this->driver->expects($this->once())->method('insert')->with('table', [
            'message' => 'Hello world',
            'recipient' => '+237612345678',
            'message_id' => '166156285923766156219494199',
            'sender' => 'UnitTest',
            'response' => json_encode(array_merge(['status' => 'OK'], $response))])->willThrowException(new \Exception('Unknown exception'));
        $handler = new BulkMessageCommandHandler($message);
        $sent = $handler->handle($command);
        $this->assertSame(1, iterator_count($sent));
    }

    public function testThrowsBulkSendException(): void
    {
        $resetTime = time() + 2;
        $this->expectException(BulkSendException::class);

        $this->response->expects($this->any())->method('getStatusCode')->will($this->returnValue(429));
        $this->client->expects($this->any())->method('sendRequest')->willReturn($this->response);
        $this->response->expects($this->any())->method('getHeaderLine')
            ->willReturnOnConsecutiveCalls(10, 8, $resetTime);

        $sendHandler = new ExecuteRequestCommandHandler(null, $this->client);
        $message = Message::create('key', 'secret', $sendHandler);

        $handler = new BulkMessageCommandHandler($message);
        iterator_to_array($handler->handle($this->command));
    }
}
