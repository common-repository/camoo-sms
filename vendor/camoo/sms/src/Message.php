<?php

declare(strict_types=1);

namespace Camoo\Sms;

/**
 * CAMOO SARL: http://www.camoo.cm
 *
 * @copyright (c) camoo.cm
 * @license You are not allowed to sell or distribute this software without permission
 * Copyright reserved
 * File: src/Message.php
 * Updated: Jan. 2018
 * Created by: Camoo Sarl (sms@camoo.sarl)
 * Description: CAMOO SMS LIB
 *
 * @link http://www.camoo.cm
 */

/**
 * Class Camoo\Sms\Message handles the methods and properties of sending an SMS message.
 */

use Camoo\Sms\Entity\CallbackDto;
use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Exception\RateLimitException;
use Camoo\Sms\Http\Client;
use Camoo\Sms\Response\Message as MessageResponse;
use Exception;
use Throwable;

class Message extends Base
{
    /**
     * Send Message
     *
     * @throws CamooSmsException
     *
     * @return Response\Message Message Response
     */
    public function send(): MessageResponse
    {
        try {
            $response = $this->execRequest(Client::POST_REQUEST);
        } catch (RateLimitException $exception) {
            throw  $exception;
        } catch (Throwable $exception) {
            throw new CamooSmsException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }

        return new MessageResponse($response);
    }

    /**
     * view a message sent
     *
     * @throws CamooSmsException
     *
     * @return Response\Message Message
     */
    public function view(): MessageResponse
    {
        try {
            $this->setResourceName(Constants::RESOURCE_VIEW);

            return new Response\Message(
                $this->execRequest(Client::GET_REQUEST, true, Constants::RESOURCE_VIEW)
            );
        } catch (Throwable $exception) {
            throw new CamooSmsException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }
    }

    /**
     * sends bulk message
     *
     * @throws Exception
     */
    public function sendBulk(CallbackDto|array $callBack = []): ?int
    {
        return $this->execBulk($callBack) ?: null;
    }
}
