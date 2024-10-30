<?php

declare(strict_types=1);

namespace Camoo\Sms\Console;

final class EncryptCommand
{
    public function __construct(public readonly string $publicKeyFile, public readonly string $message)
    {
    }
}
