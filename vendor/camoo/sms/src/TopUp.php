<?php

declare(strict_types=1);

namespace Camoo\Sms;

use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Http\Client;
use Throwable;

class TopUp extends Base
{
    /**
     * Initiate a top-up to recharge a user account
     * Only available for MTN and Orange Mobile Money Cameroon
     *
     * @throws Exception\CamooSmsException
     */
    public function add(): Response\TopUp
    {
        try {
            $this->setResourceName(Constants::RESOURCE_TOP_UP);

            return new Response\TopUp($this->execRequest(Client::POST_REQUEST));
        } catch (Throwable $exception) {
            throw new CamooSmsException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }
    }
}
