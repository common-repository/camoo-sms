<?php

declare(strict_types=1);

namespace Camoo\Sms\Console;

use Camoo\Sms\Constants;
use Camoo\Sms\Database\MySQL;
use Camoo\Sms\Entity\CallbackDto;
use Camoo\Sms\Entity\Credential;
use Camoo\Sms\Entity\DbConfig;
use Camoo\Sms\Entity\TableMapping;
use Camoo\Sms\Interfaces\DriversInterface;
use Camoo\Sms\Lib\Utils;
use Camoo\Sms\Message;
use Throwable;

class Runner
{
    public function __construct(private readonly ?BulkMessageCommandHandler $bulkMessageCommandHandler = null)
    {
    }

    public function run(array $argv): void
    {
        if (empty($argv[1])) {
            return;
        }

        $command = $argv[1];
        $arguments = $this->unSerializeArgument(base64_decode($command) ?: '');
        if (empty($arguments) || count($arguments) < 3) {
            return;
        }

        [$callback, $sTmpName, $credentials] = $arguments;
        $tmpFile = Constants::getSMSPath() . 'tmp/' . $sTmpName;

        if (!is_file($tmpFile) || !is_readable($tmpFile)) {
            return;
        }
        $callbackDto = $this->ensureCallback($callback);
        $tmpContent = file_get_contents($tmpFile);

        if (!empty($tmpContent) && ($bulkData = Utils::decodeJson($tmpContent, true))) {
            unlink($tmpFile);
            $this->applyBulk($credentials, $bulkData, $callbackDto);
        }
    }

    private function unSerializeArgument(string $argument): mixed
    {
        $allow = [
            TableMapping::class,
            DbConfig::class,
            MySQL::class,
            DriversInterface::class,
            CallbackDto::class,
        ];
        try {
            return unserialize($argument, ['allowed_classes' => $allow]);
        } catch (Throwable) {
            // do nothing at all
        }

        return null;
    }

    private function ensureCallback(CallbackDto|array $callback): CallbackDto
    {
        if ($callback instanceof CallbackDto) {
            return $callback;
        }

        return new CallbackDto(
            $this->assertDriver($callback['driver'] ?? null),
            $this->assertDbConfig($callback['db_config'] ?? null),
            $this->assertTableMapping($callback['table_mapping'] ?? null),
            $callback['bulk_chunk'] ?? null
        );
    }

    private function applyBulk(array $credentials, array $bulkData, CallbackDto $callback): void
    {
        $credentials = new Credential(
            $credentials['api_key'],
            $credentials['api_secret'],
        );

        $command = new BulkMessageCommand($bulkData, $callback);

        $handler = $this->bulkMessageCommandHandler ?? new BulkMessageCommandHandler(
            Message::create($credentials->key, $credentials->secret)
        );
        $generator = $handler->handle($command);

        foreach ($generator as $message) {
            if ($message->getId()) {
                echo 'MessageId is: ' . $message->getId() . PHP_EOL;
            }
        }
    }

    private function assertDriver(DriversInterface|array|null $driver): ?DriversInterface
    {
        if (is_array($driver)) {
            return call_user_func($driver);
        }

        return $driver;
    }

    private function assertDbConfig(DbConfig|array|null $dbConfig): ?DbConfig
    {
        if (is_array($dbConfig)) {
            return new DbConfig(
                $dbConfig['db_name'],
                $dbConfig['db_user'],
                $dbConfig['db_password'],
                $dbConfig['table_sms'],
                $dbConfig['table_prefix'] ?? null,
                $dbConfig['db_host'] ?? null,
            );
        }

        return $dbConfig;
    }

    private function assertTableMapping(TableMapping|array|null $tableMapping): ?TableMapping
    {
        if (is_array($tableMapping)) {
            return new TableMapping(
                $tableMapping['message'],
                $tableMapping['recipient'],
                $tableMapping['message_id'],
                $tableMapping['sender'],
                $tableMapping['response']
            );
        }

        return $tableMapping;
    }
}
