<?php

declare(strict_types=1);

namespace Camoo\Sms\Entity;

use Camoo\Sms\Objects\Balance;
use Camoo\Sms\Objects\Message;
use Camoo\Sms\Objects\ObjectEntityInterface;
use Camoo\Sms\Objects\TopUp;

enum ObjectEntity
{
    public function getInstance(): ObjectEntityInterface
    {
        return match ($this) {
            self::MESSAGE => new Message(),
            self::BALANCE => new Balance(),
            self::TOPUP => new TopUp()
        };
    }

    case BALANCE;
    case MESSAGE;
    case TOPUP;
}
