<?php

namespace CamooSms\Test\TestCase\Http;

use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Http\Curl\Domain\Response\ResponseInterface;
use Camoo\Sms\Exception\HttpClientException;
use Camoo\Sms\Exception\RateLimitException;
use Camoo\Sms\Http\Client as HttpClient;
use Camoo\Sms\Http\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class HttpClientTest
 *
 * @author CamooSarl
 *
 * @covers \Camoo\Sms\Http\Client
 */
class HttpClientTest extends TestCase
{
    private ?ClientInterface $oClient = null;

    private ?ResponseInterface $response = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oClient = $this->createMock(ClientInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->oClient = null;
        $this->response = null;
    }

    /** @dataProvider instanceDataProvider */
    public function testCanCreateInstance(string $endpoint, array $hAuth, int $timeout): void
    {
        $GLOBALS['wp_version'] = '5.8.1';
        $this->assertInstanceOf(HttpClient::class, new HttpClient($endpoint, $hAuth, $timeout));
    }

    /** @dataProvider instanceDataProviderFailure1 */
    public function testInstanceFailure1(string $endpoint, array $hAuth, int $timeout): void
    {
        $this->expectException(HttpClientException::class);
        new HttpClient($endpoint, $hAuth, $timeout);
    }

    public function instanceDataProvider(): array
    {
        return [
            ['https://api.camoo.cm/sms.json', ['api_key' => '3i3i', 'api_secret' => '3ueuu4'], 0],
            ['https://api.camoo.cm/sms.xml', ['api_key' => '3ifff3i', 'api_secret' => '3ueuu4'], 10],
        ];
    }

    /**
     * @dataProvider instanceDataProvider
     *
     * @depends testCanCreateInstance
     */
    public function testPerformRequestSuccess(string $endpoint, array $hAuth, int $timeout): void
    {
        $this->response->expects($this->exactly(1))->method('getStatusCode')->will($this->returnValue(200));

        $this->oClient->expects($this->once())->method('sendRequest')->willReturn($this->response);
        $this->response->expects($this->any())->method('getJson')->willReturn(['data' => 'hello']);

        $http = new HttpClient($endpoint, $hAuth, $timeout);
        $result = $http->performRequest(HttpClient::GET_REQUEST, [], [], $this->oClient);
        $this->assertInstanceOf(Response::class, $result);
    }

    /**
     * @dataProvider instanceDataProviderFailure3
     *
     * @depends testCanCreateInstance
     */
    public function testCannotValidateRequest(string $endpoint, array $hAuth): void
    {
        $this->expectException(HttpClientException::class);
        $http = new HttpClient($endpoint, $hAuth);
        $http->performRequest(HttpClient::POST_REQUEST);
    }

    /**
     * @dataProvider instanceDataProvider
     *
     * @depends testCanCreateInstance
     */
    public function testPerformRequestFailure2(string $endpoint, array $hAuth, int $timeout): void
    {
        $this->expectException(HttpClientException::class);

        $this->response->expects($this->exactly(2))->method('getStatusCode')->will($this->returnValue(404));
        $this->oClient->expects($this->once())->method('sendRequest')->willReturn($this->response);
        $http = new HttpClient($endpoint, $hAuth, $timeout);
        $http->performRequest(HttpClient::GET_REQUEST, [], [], $this->oClient);
    }

    /**
     * @dataProvider instanceDataProvider
     *
     * @depends testCanCreateInstance
     */
    public function testThrowRateLimitException(string $endpoint, array $hAuth, int $timeout): void
    {
        $resetTime = time() + 10;
        $this->expectException(RateLimitException::class);
        $this->expectExceptionMessage('{"limit":10,"remaining":8,"reset":' . $resetTime . '}');

        $this->response->expects($this->exactly(2))->method('getStatusCode')->will($this->returnValue(429));
        $this->response->expects($this->exactly(3))->method('getHeaderLine')
            ->willReturnOnConsecutiveCalls(10, 8, $resetTime);
        $this->oClient->expects($this->once())->method('sendRequest')->willReturn($this->response);
        $http = new HttpClient($endpoint, $hAuth, $timeout);
        $http->performRequest(HttpClient::GET_REQUEST, [], [], $this->oClient);
    }

    /**
     * @dataProvider instanceDataProvider
     *
     * @depends testCanCreateInstance
     */
    public function testPerformRequestFailure3(string $endpoint, array $hAuth, int $timeout): void
    {
        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Exception');

        $this->oClient->expects($this->once())->method('sendRequest')
            ->will($this->throwException(new RuntimeException('Exception')));

        if (!defined('WP_CAMOO_SMS_VERSION')) {
            define('WP_CAMOO_SMS_VERSION', 1);
        }

        $http = new HttpClient($endpoint, $hAuth, $timeout);
        $http->performRequest(HttpClient::GET_REQUEST, [], [], $this->oClient);
    }

    public function instanceDataProviderFailure1(): array
    {
        return [
            ['https://api.camoo.cm', ['api_key' => '3i3i', 'api_secret' => '3ueuu4'], -10],
            ['https://api.camoo.cm', ['api_key' => '3ifff3i', 'api_secret' => '3ueuu4'], -1],
        ];
    }

    public function instanceDataProviderFailure3(): array
    {
        return [
            ['https://api.camoo.cm', ['api_key' => '3i3i', 'api_secret' => '3ueuu4']],
            ['https://api.camoo.cm', ['api_key' => '3ifff3i', 'api_secret' => '3ueuu4']],
        ];
    }
}
