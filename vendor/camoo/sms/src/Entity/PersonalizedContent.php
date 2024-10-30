<?php

declare(strict_types=1);

namespace Camoo\Sms\Entity;

final class PersonalizedContent
{
    public function __construct(public readonly array|string $destination, public readonly ?string $message)
    {
    }
}
