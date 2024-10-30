<?php

declare(strict_types=1);

namespace Camoo\Sms;

/**
 * CAMOO SARL: http://www.camoo.cm
 *
 * @copyright (c) camoo.cm
 * @license You are not allowed to sell or distribute this software without permission
 * Copyright reserved
 * File: src/Balance.php
 * Updated: Jan. 2018
 * Created by: Camoo Sarl (sms@camoo.sarl)
 * Description: CAMOO SMS LIB
 *
 * @link http://www.camoo.cm
 */

/**
 * Class Camoo\Sms\Balance
 * Get or add balance to your account
 */

use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Http\Client;
use Throwable;

class Balance extends Base
{
    /**
     * read the current user balance
     *
     * @return Response\Balance Balance
     */
    public function get(): Response\Balance
    {
        try {
            $this->setResourceName(Constants::RESOURCE_BALANCE);

            return new Response\Balance($this->execRequest(Client::GET_REQUEST, false));
        } catch (Throwable) {
            throw new CamooSmsException('Balance Request can not be performed!');
        }
    }
}
