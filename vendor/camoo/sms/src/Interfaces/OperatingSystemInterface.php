<?php

declare(strict_types=1);

namespace Camoo\Sms\Interfaces;

interface OperatingSystemInterface
{
    public function get(): string;
}
