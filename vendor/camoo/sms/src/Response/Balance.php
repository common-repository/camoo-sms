<?php

declare(strict_types=1);

namespace Camoo\Sms\Response;

use Camoo\Sms\Entity\Money;
use Camoo\Sms\Http\Response;

/**
 * Class Balance
 *
 * @property float  $balance
 * @property string $currency
 *
 * @author CamooSarl
 */
final class Balance extends ObjectResponse
{
    public function __construct(Response $response)
    {
        parent::__construct($response);
        $this->_data = $this->_data['balance'];
    }

    public function getBalance(): float
    {
        if (array_key_exists('balance', $this->_data)) {
            return round($this->_data['balance'], 2);
        }

        return 0.00;
    }

    public function getCurrency(): ?string
    {
        if (array_key_exists('currency', $this->_data)) {
            return $this->_data['currency'];
        }

        return null;
    }

    public function getMoney(): Money
    {
        return new Money($this->getBalance(), $this->getCurrency());
    }
}
