<?php

declare(strict_types=1);

namespace Camoo\Sms\Http\Command;

use Camoo\Http\Curl\Domain\Client\ClientInterface;
use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Exception\HttpClientException;
use Camoo\Sms\Http\Client;
use Camoo\Sms\Http\Response;
use Camoo\Sms\Lib\Utils;

class ExecuteRequestCommandHandler
{
    public function __construct(
        private readonly ?Client $client = null,
        private readonly ?ClientInterface $httpClient = null
    ) {
    }

    public function handle(ExecuteRequestCommand $command): Response
    {
        if (null === $command->credential) {
            throw new CamooSmsException('Credentials are missing !');
        }
        $client = $this->client ?? new Client($command->endpoint, $command->credential->toArray());
        $data = $command->data;

        if (array_key_exists('encrypt', $data)) {
            unset($data['encrypt']);
        }
        if (array_key_exists('to', $data)) {
            $data['to'] = Utils::formatRecipient($data['to']);
        }

        try {
            return $client->performRequest($command->type, $data, $command->headers, $this->httpClient);
        } catch (HttpClientException $exception) {
            throw new CamooSmsException(
                ['_error' => $exception->getMessage()],
                $exception->getCode(),
                $exception->getPrevious()
            );
        }
    }
}
