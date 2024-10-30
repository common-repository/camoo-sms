<?php

declare(strict_types=1);

namespace Camoo\Sms\Interfaces;

use IteratorAggregate;

interface RecipientCollectionInterface extends IteratorAggregate
{
    public function add(string $phoneNumber, ?string $name): void;
}
