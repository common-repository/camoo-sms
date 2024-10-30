<?php

declare(strict_types=1);

namespace Camoo\Sms\Interfaces;

use Camoo\Sms\Entity\DbConfig;

interface DriversInterface
{
    public static function getInstance(?DbConfig $dbConfig = null): self;

    public function insert(string $table, array $variables = []): bool;

    public function close(): bool;

    public function getDB(): ?self;

    public function getDbConfig(): ?DbConfig;
}
