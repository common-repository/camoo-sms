<?php

declare(strict_types=1);

namespace Camoo\Sms\Http\Command;

use Camoo\Sms\Entity\Credential;

final class ExecuteRequestCommand
{
    public function __construct(
        public readonly string $type,
        public readonly string $endpoint,
        public readonly array $data = [],
        public readonly ?Credential $credential = null,
        public readonly array $headers = [],
    ) {
    }
}
