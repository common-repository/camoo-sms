<?php

declare(strict_types=1);

namespace Camoo\Sms\Console;

use Camoo\Sms\Entity\CallbackDto;
use Camoo\Sms\Entity\Credential;

final class AddBulkCommand
{
    public function __construct(
        public readonly Credential $credentials,
        public readonly array $data,
        public readonly CallbackDto|array $callback,
        public readonly ?string $binPath = null,
    ) {
    }
}
