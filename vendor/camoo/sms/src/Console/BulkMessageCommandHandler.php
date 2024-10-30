<?php

declare(strict_types=1);

namespace Camoo\Sms\Console;

use Camoo\Sms\Base;
use Camoo\Sms\Constants;
use Camoo\Sms\Database\Repository\LogRepository;
use Camoo\Sms\Entity\RateLimitInfo;
use Camoo\Sms\Entity\TableMapping;
use Camoo\Sms\Exception\BulkSendException;
use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Exception\RateLimitException;
use Camoo\Sms\Interfaces\LogRepositoryInterface;
use Camoo\Sms\Lib\Utils;
use Camoo\Sms\Message;
use Camoo\Sms\Objects\FieldMapper;
use Camoo\Sms\Response;
use Generator;
use Throwable;

class BulkMessageCommandHandler
{
    private const MAX_ASYNC_RETRY = 3;

    private int $asyncRetry = 0;

    private int $counter = 0;

    private int $batchLoop = 2;

    private int $batch = 1;

    public function __construct(
        private readonly Message|Base $message,
        private readonly ?LogRepositoryInterface $logRepository = null
    ) {
    }

    /** @return Generator<Message> */
    public function handle(BulkMessageCommand $command): Generator
    {
        $data = $command->data;
        $destinations = $this->getDestinations($data['to'], $command->callback?->bulkChunkLimit);
        unset($data['to']);
        $rawMessage = !empty($data['message']) ? $data['message'] : null;
        foreach ($destinations as $destination) {
            $this->counter++;
            try {
                $personalizedContent = (new PersonalizationCommandHandler())
                    ->handle(new PersonalizationCommand($destination, $rawMessage));
                $data['message'] = $personalizedContent->message;
                $response = $this->send($data, $personalizedContent->destination);
                $this->doLog($response, $command, $data['from']);
                yield $response;
            } catch (CamooSmsException $exception) {
                @error_log('ERROR occurred (' . $exception->getMessage() . ') during sending SMS to' .
                is_array($destination) ? implode(',', $destination) : $destination);
                continue;
            }
            if ($this->counter === $this->batchLoop) {
                $this->batchLoop = $this->batchLoop + $this->batch;
                @usleep(4000000);
            }
        }
    }

    private function send(array $data, string|array $destination): Response\Message
    {
        call_user_func(Constants::CLEAR_OBJECT);
        /** @var Message|Response\Message $message */
        $message = $this->message;
        foreach ($data as $key => $value) {
            $message->{$key} = $value;
        }

        $message->to = $destination;

        $response = $this->asyncSend($message);
        $this->asyncRetry = 0;

        return $response;
    }

    private function asyncSend(Message|Response\Message $message): Response\Message
    {
        try {
            $response = $message->send();
        } catch (RateLimitException $exception) {
            if ($this->asyncRetry > self::MAX_ASYNC_RETRY) {
                throw new BulkSendException('Too many retries');
            }
            $limitInfo = json_decode($exception->getMessage(), false);
            $rateLimitInfo = new RateLimitInfo(
                $limitInfo->limit ?? 1,
                $limitInfo->remaining ?? 0,
                $limitInfo->reset ?? time() - 1
            );
            $wait = $rateLimitInfo->reset - time();
            if ($wait >= 0 && sleep($wait) === 0) {
                $this->asyncRetry++;
                $response = $this->asyncSend($message);
            } else {
                throw new BulkSendException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
            }
        }

        return $response;
    }

    private function doLog(Response\Message $response, BulkMessageCommand $command, string $sender): void
    {
        $callback = $command->callback;
        if ($callback->dbConfig === null && $callback->driver?->getDbConfig() === null) {
            return;
        }

        $tableMapping = $callback?->tableMapping ?? TableMapping::default();
        $entryDataMapper = new FieldMapper($tableMapping, $response, $sender);
        try {
            $repository = $this->logRepository ?? new LogRepository($callback->dbConfig, $callback->driver);
            $repository->save($entryDataMapper->get());
        } catch (Throwable $exception) {
            error_log('ERROR: Handle bulk sms SMS:: ' . $exception->getMessage());
        }
    }

    private function getDestinations(string|array $to, ?int $limit = null): array|string
    {
        $limit = $limit ?? Constants::SMS_MAX_RECIPIENTS;
        $xTo = $to;
        $bIsMultiArray = Utils::isMultiArray($xTo);
        if (is_array($xTo) && !$bIsMultiArray) {
            $xTo = array_unique($xTo);
        }

        return $limit > 1 && !$bIsMultiArray ? array_chunk($xTo, $limit, true) : $xTo;
    }
}
