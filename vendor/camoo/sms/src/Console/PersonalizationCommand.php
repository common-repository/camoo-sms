<?php

declare(strict_types=1);

namespace Camoo\Sms\Console;

class PersonalizationCommand
{
    public function __construct(public readonly string|array $destination, public readonly ?string $message)
    {
    }
}
