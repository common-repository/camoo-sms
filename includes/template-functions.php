<?php

declare(strict_types=1);

use Camoo\Sms\Response\Message;
use CAMOO_SMS\Config\Bootstrap;
use CAMOO_SMS\Newsletter;
use CAMOO_SMS\Option;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Show SMS newsletter form.
 */
function wp_camoo_sms_subscribes(): void
{
    Newsletter::loadNewsLetter();
}

/**
 * Get option value.
 */
function wp_camoo_sms_get_option(mixed $option, ?string $settingName = null): mixed
{
    return Option::getOption($option, $settingName);
}

/**
 * Send SMS.
 *
 * @return Message|int|WP_Error|null
 */
function wp_camoo_sms_send(string|array $to, string $message, bool $isFlash = false)
{
    global $oCamooSMS;
    if (empty($to)) {
        return new WP_Error('send-sms', __('To parameter cannot be empty', Bootstrap::DOMAIN_TEXT));
    }
    $oCamooSMS->isFlash = $isFlash;
    $oCamooSMS->to = is_array($to) ? $to : [$to];
    $oCamooSMS->msg = $message;

    return $oCamooSMS->sendSMS();
}
