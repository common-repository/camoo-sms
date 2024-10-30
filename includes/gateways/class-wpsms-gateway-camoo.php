<?php

namespace CAMOO_SMS\Gateway;

use Camoo\Sms\Balance;
use Camoo\Sms\Base;
use Camoo\Sms\Database\MySQL;
use Camoo\Sms\Entity\CallbackDto;
use Camoo\Sms\Entity\DbConfig;
use Camoo\Sms\Entity\TableMapping;
use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Message;
use CAMOO_SMS\Config\Bootstrap;
use CAMOO_SMS\Gateway;
use Throwable;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Camoo extends Gateway
{
    public bool $unitrial = false;

    public ?string $unit;

    public string $flash = 'enable';

    public bool $isFlash = false;

    public bool $encryptSMS = false;

    public bool $isUnicode = false;

    public array $clearObject = [Base::class, 'clear'];

    public array $balanceObj = [Balance::class, 'create'];

    public int $bulkThreshold = 50;

    public int $bulkChunk = 50;

    public string $smsRoute = 'premium';

    public function __construct()
    {
        parent::__construct();

        $this->validateNumber = '+2376XXXXYYY';
        $this->help = 'WordPress SMS API Sending SMS via the CAMOO SMS gateway';
        $this->hasKey = true;
    }

    public function sendSMS(): \Camoo\Sms\Response\Message|int|null|WP_Error
    {
        /**
         * Modify sender number
         *
         * @param string $this ->from sender number.
         *
         * @since 3.4
         */
        $this->from = apply_filters('wp_camoo_sms_from', $this->from);

        /**
         * Modify Receiver number
         *
         * @param array $this ->to receiver number
         */
        $this->to = apply_filters('wp_camoo_sms_to', $this->to);

        /**
         * Modify text message
         *
         * @param string $this ->msg text message.
         *
         * @since 3.4
         */
        $this->msg = apply_filters('wp_camoo_sms_msg', $this->msg);
        /** @var Message&\Camoo\Sms\Objects\Message $message */
        $message = Message::create($this->username, $this->password);
        try {
            $message->from = $this->from;
            $message->to = $this->to;
            $message->message = $this->msg;
            if ($this->isFlash === true) {
                $message->type = 'flash';
            }
            if ($this->smsRoute === 'classic') {
                $message->route = 'classic';
            }

            if ($this->isUnicode !== true) {
                $message->datacoding = 'plain';
            }

            if ($this->encryptSMS === true) {
                $message->encrypt = true;
            }
            // Notify URL
            $message->notify_url = esc_url($this->getNotifyUrl());

            $logData = [
                'sender' => $this->from,
                'message' => $this->msg,
                'to' => $this->to,
            ];
            if (!empty($this->to) && is_array($this->to) && count($this->to) >= $this->bulkThreshold) {
                $callBack = new CallbackDto(
                    MySQL::getInstance(),
                    new DbConfig(
                        DB_NAME,
                        DB_USER,
                        DB_PASSWORD,
                        'camoo_sms_send',
                        $this->tbPrefix,
                        DB_HOST
                    ),
                    new TableMapping(
                        'message',
                        'recipient',
                        'message_id',
                        'sender',
                        'response'
                    ),
                    $this->bulkChunk
                );
                $result = $message->sendBulk($callBack);
            } else {
                $result = $message->send();
                $logData['message_id'] = $result->getId();
                $logData['response'] = $result;
                $this->log($logData);
            }

            /**
             * Run hook after send sms.
             *
             * @param string $result result output.
             */
            do_action('wp_camoo_sms_send', $result);

            return $result;
        } catch (Throwable $e) {
            $logData['response'] = $e->getMessage();
            $this->log($logData, 'error');

            return new WP_Error('send-sms', $e->getMessage());
        }
    }

    public function getCredit(): WP_Error|int|float
    {
        // Check username and password
        if (!$this->username || !$this->password) {
            return new WP_Error('account-credit', __(
                'Username/Password does not set for this gateway',
                Bootstrap::DOMAIN_TEXT
            ));
        }
        if (property_exists($this, 'clearObject')) {
            call_user_func($this->clearObject);
        }

        /** @var Balance $balance */
        $balance = call_user_func_array($this->balanceObj, [$this->username, $this->password]);

        try {
            $ohBalance = $balance->get();
        } catch (CamooSmsException $exception) {
            return new WP_Error('account-credit', $exception->getMessage());
        }

        return $ohBalance->balance;
    }

    private function getNotifyUrl(): string
    {
        $params = ['rest_route' => '/camoo/v1/status'];

        return add_query_arg($params, get_home_url());
    }
}
