<?php

declare(strict_types=1);

namespace Camoo\Sms\Lib;

use stdClass;

final class NormalizeMessageResponse
{
    public function __construct(private readonly ?array $data)
    {
    }

    public function get(bool $associative = true): array|stdClass|null
    {
        if (empty($this->data['sms'])) {
            return null;
        }

        $value = $this->data;

        $sms = $value['sms'] ?? null;
        unset($value['sms']);
        $messages = $sms['messages'] ?? null;
        unset($sms['messages']);

        $value = Utils::normaliseKeys($value, $associative);
        if ($sms = Utils::normaliseKeys($sms, $associative)) {
            !$associative ? $value->sms = $sms : $value['sms'] = $sms;
            if ($messages = Utils::normaliseKeys($messages, $associative)) {
                !$associative ? $value->sms->messages = $messages : $value['sms']['messages'] = $messages;
            }
        }

        return $value;
    }
}
