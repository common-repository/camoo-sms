<?php

declare(strict_types=1);

namespace Camoo\Sms\Response;

use Camoo\Sms\Http\Response;

/**
 * Class TopUp
 *
 * @property float|int $amount
 * @property string    $currency
 * @property string    $network
 * @property string    $status
 * @property string    $id
 *
 * @author CamooSarl
 */
class TopUp extends ObjectResponse
{
    public function __construct(private readonly Response $response)
    {
        parent::__construct($response);
        $this->_data = $this->_data['topup'];
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getAmount(): float|int
    {
        $amount = $this->_data['amount'] ?? 0.00;

        return round($amount, 2);
    }

    public function getCurrency(): ?string
    {
        return  $this->_data['currency'] ?? '';
    }

    public function getNetwork(): string
    {
        return  $this->_data['network'] ?? '';
    }

    public function getStatus(): string
    {
        return  $this->_data['status'] ?? '';
    }

    public function getId(): string
    {
        return  $this->_data['id'] ?? '';
    }
}
