<?php

namespace CamooSms\Test\TestCase\Entity;

use Camoo\Sms\Entity\Recipient;
use PHPUnit\Framework\TestCase;

class RecipientTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $recipient = new Recipient('237679123123', 'FooName');
        $this->assertInstanceOf(Recipient::class, $recipient);
        $this->assertSame('237679123123', $recipient->phoneNumber);
        $this->assertSame('FooName', $recipient->name);
    }
}
