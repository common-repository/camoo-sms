<?php

declare(strict_types=1);

namespace Camoo\Sms\Objects;

use Valitron\Validator;

final class TopUp extends Base
{
    private const MIN_TOPUP_AMONT = 3000;

    /**
     * Phonenumber.
     * Only available for MTN and Orange Mobile Money Cameroon
     */
    public string $phonenumber;

    /** amount that should be recharged */
    public int|float $amount;

    public function validatorDefault(Validator $validator): Validator
    {
        $validator
            ->rule('required', ['phonenumber', 'amount']);
        $validator
            ->rule('integer', 'amount');
        $this->canTopUpCM($validator, 'phonenumber');
        $this->isAllowedAmount($validator);

        return $validator;
    }

    private function isAllowedAmount(Validator $validator): void
    {
        $validator->rule(
            fn (string $field, mixed $value): bool => $field === 'amount' && $value >= self::MIN_TOPUP_AMONT,
            'amount'
        )->message('{amount} is not allowed!');
    }
}
