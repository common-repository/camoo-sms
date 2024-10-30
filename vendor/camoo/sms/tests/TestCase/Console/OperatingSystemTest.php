<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase\Console;

use Camoo\Sms\Console\OperatingSystem;
use PHPUnit\Framework\TestCase;

class OperatingSystemTest extends TestCase
{
    public function testCanGet(): void
    {
        $system = new OperatingSystem();
        $this->assertNotEmpty($system->get());
    }
}
