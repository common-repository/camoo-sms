<?php

namespace CamooSms\Test\TestCase;

use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Sms\Base;
use Camoo\Sms\Entity\CallbackDto;
use Camoo\Sms\Entity\Credential;
use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Http\Client;
use Camoo\Sms\Http\Command\ExecuteRequestCommand;
use Camoo\Sms\Http\Command\ExecuteRequestCommandHandler;
use Camoo\Sms\Http\Response;
use Camoo\Sms\Message;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageTest
 *
 * @author CamooSarl
 *
 * @covers \Camoo\Sms\Message
 */
class MessageTest extends TestCase
{
    private ?Base $base;

    private ?Client $client;

    public function setUp(): void
    {
        $this->base = $this->getMockBuilder(Message::class)
            ->onlyMethods(['execRequest'])
            ->getMock();
        $this->client = $this->createMock(Client::class);
    }

    public function tearDown(): void
    {
        $this->base->clear();
        $this->base = null;
        $this->client = null;
    }

    /** @covers \Camoo\Sms\Message::send */
    public function testSendSuccess(): void
    {
        $handler = $this->createMock(ExecuteRequestCommandHandler::class);
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

        /** @var Message|\Camoo\Sms\Objects\Message $message */
        $message = Message::create('key', 'secret', $handler);

        $message->from = 'YourCompany';
        $message->to = '+237612345678';
        $message->message = 'Hello world';

        $command = new ExecuteRequestCommand('POST', 'https://api.camoo.cm/v1/sms.json', [
            'from' => 'YourCompany',
            'message' => 'Hello world',
            'to' => ['+237612345678'],
        ], new Credential('key', 'secret'));

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $responseMock->expects($this->once())->method('getJson')->willReturn($response);

        $handler->expects($this->any())->method('handle')->with($command)
            ->will($this->returnValue(new Response($responseMock)));

        $result = $message->send();

        $this->assertEquals('1661562859237661562194941475', $result->getId());
        $this->assertEquals('+237612345678', $result->getTo());
        $this->assertEquals('Hello world', $result->getMessage());
    }

    /**
     * @covers \Camoo\Sms\Message::send
     *
     * @dataProvider createDataProvider
     */
    public function testSendFailure(string $apikey, string $apisecret): void
    {
        $this->expectException(CamooSmsException::class);
        $oMessage = Message::create($apikey, $apisecret);
        $oMessage->from = 'YourCompany';
        $oMessage->tel = '+237612345678';
        $oMessage->message = 'Hello Kmer World! Déjà vu!';
        $oMessage->send();
    }

    /** @covers \Camoo\Sms\Message::view */
    public function testViewSuccess(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $httpClient = $this->createMock(ClientInterface::class);
        $handler = new ExecuteRequestCommandHandler(null, $httpClient);

        /** @var Message|\Camoo\Sms\Objects\Message $oMessage */
        $oMessage = Message::create('key', 'secret', $handler);
        $oMessage->id = '12293kp';

        $return = [
            '_message' => 'succes',
            'sms' => [
                'message-count' => 1,
                'messages' => [
                    [
                        'status' => 0,
                        'message-id' => '12293kp',
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

        $response->expects($this->exactly(3))->method('getStatusCode')->willReturn(200);
        $response->expects($this->once())->method('getJson')->willReturn($return);
        $httpClient->expects($this->once())->method('sendRequest')
            ->will($this->returnValue($response));

        $result = $oMessage->view();
        $this->assertSame('12293kp', $result->getId());
    }

    /**
     * @covers \Camoo\Sms\Message::view
     *
     * @dataProvider createDataProvider
     */
    public function testViewFailure($apikey, $apisecret): void
    {
        $this->expectException(CamooSmsException::class);
        $oMessage = Message::create($apikey, $apisecret);
        $oMessage->to = '12293kp';
        $oMessage->view();
    }

    /**
     * @covers \Camoo\Sms\Message::view
     *
     * @dataProvider createDataProvider
     */
    public function testSendBulkFailureFalse($apikey, $apisecret): void
    {
        /** @var Message|\Camoo\Sms\Objects\Message $oMessage */
        $oMessage = Message::create($apikey, $apisecret);
        $this->assertNull($oMessage->sendBulk(new CallbackDto()));
    }

    /**
     * @covers \Camoo\Sms\Message::view
     *
     * @dataProvider createDataProvider
     */
    public function testSendBulkSucess($apikey, $apisecret): void
    {
        /** @var Message|\Camoo\Sms\Objects\Message $oMessage */
        $oMessage = Message::create($apikey, $apisecret);
        $oMessage->to = ['+237612345678', '+237612345679', '+237612345610', '+33689764530', '+4917612345671'];
        $this->assertIsInt($oMessage->sendBulk());
    }

    public function createDataProvider(): array
    {
        return [
            ['fgfgfgfkjf', 'fhkjdfh474gudghjdg74tj4uzt64'],
            ['f9033gfgfgfkjf', '283839383fhkjdfh474gudghjdg74tj4uzt64'],
        ];
    }
}
