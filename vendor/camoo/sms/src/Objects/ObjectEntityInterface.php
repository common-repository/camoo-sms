<?php

declare(strict_types=1);

namespace Camoo\Sms\Objects;

use Valitron\Validator;

interface ObjectEntityInterface
{
    public function validatorDefault(Validator $validator): Validator;

    public function has(string $property): bool;
}
