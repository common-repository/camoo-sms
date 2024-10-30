<?php

declare(strict_types=1);

namespace Camoo\Sms\Console;

use Camoo\Sms\Interfaces\OperatingSystemInterface;

final class OperatingSystem implements OperatingSystemInterface
{
    public function get(): string
    {
        return strtoupper(PHP_OS ?: '');
    }
}
