<?php

declare(strict_types=1);

namespace Camoo\Sms;

interface ObjectHandlerInterface
{
    public static function create(): object;

    public function getData(string $validator = 'default'): array;

    public function getEndPointUrl(): string;

    public function execRequest(string $type, bool $withData = true, ?string $validator = null): Http\Response;
}
