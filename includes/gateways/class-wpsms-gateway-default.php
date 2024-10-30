<?php

namespace CAMOO_SMS\Gateway;

use CAMOO_SMS\Config\Bootstrap;
use CAMOO_SMS\Gateway;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Default_Gateway extends Gateway
{
    public bool $unitrial = false;

    public ?string $unit;

    public string $flash = 'enable';

    public bool $isFlash = false;

    public bool $canSendBulk = false;

    public function __construct()
    {
        $this->validateNumber = '237xxxxxxxxxx';
        parent::__construct();
    }

    public function sendSMS(): WP_Error
    {
        // Check gateway credit
        if (is_wp_error($this->getCredit())) {
            return new WP_Error('account-credit', __(
                'Your account does not credit for sending sms.',
                Bootstrap::DOMAIN_TEXT
            ));
        }

        return new WP_Error('send-sms', __('Does not set any gateway', Bootstrap::DOMAIN_TEXT));
    }

    public function getCredit(): WP_Error
    {
        // Check username and password
        if (!$this->username && !$this->password) {
            return new WP_Error('account-credit', __(
                'Username/Password does not set for this gateway',
                Bootstrap::DOMAIN_TEXT
            ));
        }

        return new WP_Error('account-credit', 0);
    }
}
