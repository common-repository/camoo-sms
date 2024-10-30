<?php

declare(strict_types=1);

namespace Camoo\Sms\Interfaces;

interface LogRepositoryInterface
{
    public function save(array $data): bool;
}
