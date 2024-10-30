<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase\Console;

use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Sms\Console\BulkMessageCommand;
use Camoo\Sms\Console\BulkMessageCommandHandler;
use Camoo\Sms\Console\Runner;
use Camoo\Sms\Constants;
use Camoo\Sms\Database\MySQL;
use Camoo\Sms\Entity\CallbackDto;
use Camoo\Sms\Entity\TableMapping;
use Camoo\Sms\Http\Response;
use Camoo\Sms\Lib\Utils;
use Camoo\Sms\Message;
use PHPUnit\Framework\TestCase;

/**
 * Class RunnerTest
 *
 * @author CamooSarl
 *
 * @covers \Camoo\Sms\Console\Runner
 */
class RunnerTest extends TestCase
{
    private string $sTmpName;

    private string $sTmpFile;

    private ?BulkMessageCommandHandler $handler;

    protected function setUp(): void
    {
        $this->sTmpName = 'test' . Utils::randomStr() . '.bulk';
        $this->sTmpFile = Constants::getSMSPath() . 'tmp/' . $this->sTmpName;
        $this->handler = $this->createMock(BulkMessageCommandHandler::class);
    }

    public function tearDown(): void
    {
        if (file_exists($this->sTmpFile)) {
            unlink($this->sTmpFile);
        }
    }

    public function testWithoutCommand(): void
    {
        $argv = [
            'php',
        ];
        $runner = new Runner($this->handler);
        $runner->run($argv);
        $this->handler->expects($this->never())->method('handle');
    }

    public function testWithoutArguments(): void
    {
        $argv = [
            'php',
            'line',
        ];
        $runner = new Runner($this->handler);
        $runner->run($argv);
        $this->handler->expects($this->never())->method('handle');
    }

    public function testWithMissingTmpFile(): void
    {
        $line = json_encode([[], 'fooBar', ['api_key' => 'key', 'api_secret' => 'secret']]);
        $argv = [
            'php',
            base64_encode($line),
        ];
        $runner = new Runner($this->handler);
        $runner->run($argv);
        $this->handler->expects($this->never())->method('handle');
    }

    public function testWithDirectoryTmpFile(): void
    {
        $line = serialize([new CallbackDto(), '', ['api_key' => 'key', 'api_secret' => 'secret']]);
        $argv = [
            'php',
            base64_encode($line),
        ];
        $runner = new Runner($this->handler);
        $runner->run($argv);
        $this->handler->expects($this->never())->method('handle');
    }

    /** @covers \Camoo\Sms\Console\Runner::run */
    public function testCanRun(): void
    {
        $hData = [
            'to' => ['+237612345611'],
            'message' => 'foo bar',
            'from' => 'Foo',
        ];
        file_put_contents($this->sTmpFile, json_encode($hData));
        $sPASS = serialize([[], $this->sTmpName, ['api_key' => 'key', 'api_secret' => 'secret']]);
        $argv = [
            'php',
            base64_encode($sPASS),
        ];
        $command = new BulkMessageCommand($hData, new CallbackDto());
        $this->handler->expects($this->once())->method('handle')->with($command);
        $oRunner = new Runner($this->handler);
        $oRunner->run($argv);
    }

    /** @dataProvider provideCallback */
    public function testSendBulkMessageAndIterateGenerator(CallbackDto|array $callback): void
    {
        $hData = [
            'to' => [
                ['name' => 'John Doe', 'mobile' => '+237612345678'],
                ['name' => 'Jeanne Doe', 'mobile' => '+237612345679'],
                ['name' => 'Junior Doe', 'mobile' => '+237612345680'],
            ],
            'message' => 'Hello world',
            'from' => 'UnitTest',
        ];
        file_put_contents($this->sTmpFile, json_encode($hData));

        $sPASS = serialize([$callback, $this->sTmpName, ['api_key' => 'key', 'api_secret' => 'secret']]);
        $argv = [
            'php',
            base64_encode($sPASS),
        ];

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
        $responseMock->expects($this->once())->method('getJson')->willReturn($response);

        $clientResponse = new Response($responseMock);
        $messageResponse = new \Camoo\Sms\Response\Message($clientResponse);
        $message = $this->createMock(Message::class);
        $message->expects($this->exactly(3))->method('send')->willReturn($messageResponse);

        $handler = new BulkMessageCommandHandler($message);

        $oRunner = new Runner($handler);
        $oRunner->run($argv);
    }

    public function provideCallback(): array
    {
        return [
            [
                [
                    'driver' => [MySQL::class, 'getInstance'],
                    'bulk_chunk' => 10,
                    'db_config' => null,
                    'table_mapping' => [
                        'message' => 'message',
                        'recipient' => 'to',
                        'message_id' => 'message_id',
                        'sender' => 'from',
                        'response' => 'response',
                    ],
                ],
            ],
            [
                [
                    'driver' => null,
                    'bulk_chunk' => 10,
                    'db_config' => [
                        'db_name' => 'db',
                        'db_user' => 'user',
                        'db_password' => 'pass',
                        'db_host' => 'localhost',
                        'table_sms' => 'camoo_sms_send',
                        'table_prefix' => '',
                    ],
                    'table_mapping' => null,
                ],
            ],
            [
                new CallbackDto(MySQL::getInstance(), null, new TableMapping(
                    'message',
                    'recipient',
                    'message_id',
                    'sender',
                    'response'
                )),
            ],
        ];
    }
}
