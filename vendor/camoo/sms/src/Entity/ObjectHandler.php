<?php

declare(strict_types=1);

namespace Camoo\Sms\Entity;

use Camoo\Sms\Balance;
use Camoo\Sms\Http\Command\ExecuteRequestCommandHandler;
use Camoo\Sms\Message;
use Camoo\Sms\ObjectHandlerInterface;
use Camoo\Sms\TopUp;

enum ObjectHandler
{
    public function getInstance(?ExecuteRequestCommandHandler $handler): ObjectHandlerInterface
    {
        return match ($this) {
            self::MESSAGE => new Message($handler),
            self::BALANCE => new Balance($handler),
            self::TOPUP => new TopUp($handler)
        };
    }

    case BALANCE;
    case MESSAGE;
    case TOPUP;
}
