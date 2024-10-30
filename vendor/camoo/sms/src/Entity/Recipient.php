<?php

declare(strict_types=1);

namespace Camoo\Sms\Entity;

final class Recipient
{
    public function __construct(public readonly string $phoneNumber, public readonly ?string $name = null)
    {
    }
}
