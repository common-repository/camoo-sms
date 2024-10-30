<?php

declare(strict_types=1);

namespace Camoo\Sms\Http;

use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Http\Curl\Domain\Entity\Configuration;
use Camoo\Http\Curl\Infrastructure\Client as CamooClient;
use Camoo\Http\Curl\Infrastructure\Request;
use Camoo\Sms\Constants;
use Camoo\Sms\Exception\HttpClientException;
use Camoo\Sms\Exception\RateLimitException;
use Throwable;
use Valitron\Validator;

/**
 * Class Client
 */
class Client
{
    public const GET_REQUEST = 'GET';

    public const POST_REQUEST = 'POST';

    private const RATE_LIMIT_STATUS = 429;

    protected ?string $endpoint = null;

    protected array $userAgent = [];

    protected array $requestVerbs = [self::GET_REQUEST => 'query', self::POST_REQUEST => 'form_params'];

    private int $timeout = Constants::CLIENT_TIMEOUT;

    private array $authentication;

    private array $headers = [];

    private Configuration $clientConfiguration;

    /**
     * @param int $timeout > 0
     *
     * @throws HttpClientException if timeout settings are invalid
     */
    public function __construct(string $endpoint, array $hAuthentication, int $timeout = 0)
    {
        $this->endpoint = $endpoint;
        $this->authentication = $hAuthentication;
        $this->addUserAgentString($this->getFromInfo());
        $this->addUserAgentString(Constants::getPhpVersion());

        if (!is_int($timeout) || $timeout < 0) {
            throw new HttpClientException(sprintf(
                'Connection timeout must be an int >= 0, got "%s".',
                is_object($timeout) ? get_class($timeout) : gettype($timeout) . ' ' . var_export($timeout, true)
            ));
        }
        if (!empty($timeout)) {
            $this->timeout = $timeout;
        }
        $this->clientConfiguration = new Configuration($this->timeout);
    }

    public function addUserAgentString(string $userAgent): void
    {
        $this->userAgent[] = $userAgent;
    }

    /** @throws HttpClientException */
    public function performRequest(
        string $method,
        array $data = [],
        array $headers = [],
        ?ClientInterface $client = null
    ): Response {
        $this->setHeader($headers);
        //VALIDATE HEADERS
        $hHeaders = $this->getHeaders();
        $requestMethod = strtoupper($method);
        $oValidator = new Validator(array_merge([
            'request' => $requestMethod,
            'response_format' => $this->getFormat(),
        ], $hHeaders));

        if (empty($this->validatorDefault($oValidator))) {
            throw new HttpClientException(json_encode($oValidator->errors()));
        }

        return $this->applyRequest($hHeaders, $data, $requestMethod, $client);
    }

    /** @return string userAgentString */
    protected function getUserAgentString(): string
    {
        return implode(' ', $this->userAgent);
    }

    protected function getAuthKeys(): array
    {
        return $this->authentication;
    }

    protected function setHeader(array $option = []): void
    {
        $this->headers += $option;
    }

    protected function getHeaders(): array
    {
        $default = [];
        if ($hAuth = $this->getAuthKeys()) {
            $default = [
                'X-Api-Key' => $hAuth['api_key'],
                'X-Api-Secret' => $hAuth['api_secret'],
                'User-Agent' => $this->getUserAgentString(),
            ];
        }

        return $this->headers += $default;
    }

    protected function getFormat(): string
    {
        $asEndPoint = explode('.', $this->endpoint);

        return end($asEndPoint);
    }

    protected function getFromInfo(): string
    {
        $identity = 'CamooSms/ApiClient/';
        if (defined('WP_CAMOO_SMS_VERSION')) {
            $sWPV = '';
            global $wp_version;
            if ($wp_version) {
                $sWPV = $wp_version;//@codeCoverageIgnore
            }
            $identity = 'WP' . $sWPV . '/CamooSMS' . WP_CAMOO_SMS_VERSION . Constants::DS;
        }

        return $identity . Constants::CLIENT_VERSION;
    }

    private function applyRequest(array $headers, array $data, string $type, ?ClientInterface $client = null): Response
    {
        try {
            $request = new Request($this->clientConfiguration, $this->endpoint, $headers, $data, $type);
            $client = $client ?? new CamooClient($this->clientConfiguration);
            $response = $client->sendRequest($request);
            if ($response->getStatusCode() === 200) {
                return new Response($response, $this->getFormat());
            }
        } catch (Throwable $exception) {
            throw new HttpClientException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }
        if ($response->getStatusCode() === self::RATE_LIMIT_STATUS) {
            throw new RateLimitException(
                json_encode([
                    'limit' => $response->getHeaderLine('x-ratelimit-limit'),
                    'remaining' => $response->getHeaderLine('x-ratelimit-remaining'),
                    'reset' => $response->getHeaderLine('x-ratelimit-reset'),
                ]),
                self::RATE_LIMIT_STATUS
            );
        }
        throw new HttpClientException('Request cannot be performed successfully !');
    }

    /** Validate request params */
    private function validatorDefault(Validator $validator): bool
    {
        $validator->rule('required', ['X-Api-Key', 'X-Api-Secret', 'response_format']);
        $validator->rule('optional', ['User-Agent']);
        $validator->rule('in', 'response_format', ['json', 'xml']);

        return $validator->rule('in', 'request', array_keys($this->requestVerbs))->validate();
    }
}
