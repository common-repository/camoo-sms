<?php

declare(strict_types=1);

namespace Camoo\Sms\Entity;

final class RateLimitInfo
{
    public function __construct(public readonly int $limit, public readonly int $remaining, public readonly int $reset)
    {
    }
}
