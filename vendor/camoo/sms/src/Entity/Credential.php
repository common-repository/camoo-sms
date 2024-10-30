<?php

declare(strict_types=1);

namespace Camoo\Sms\Entity;

final class Credential
{
    public function __construct(public readonly string $key, public readonly string $secret)
    {
    }

    public function toArray(): array
    {
        return [
            'api_key' => $this->key ?: '',
            'api_secret' => $this->secret ?: '',
        ];
    }
}
