<?php

declare(strict_types=1);

namespace Camoo\Sms\Entity;

final class DbConfig
{
    private const DEFAULT_HOST = 'localhost';

    public function __construct(
        public readonly string $dbName,
        public readonly string $dbUser,
        public readonly string $password,
        public readonly string $tableName,
        public readonly ?string $prefix = null,
        public readonly ?string $host = self::DEFAULT_HOST,
    ) {
    }
}
