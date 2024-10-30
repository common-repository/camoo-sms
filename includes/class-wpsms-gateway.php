<?php

declare(strict_types=1);

namespace CAMOO_SMS;

use CAMOO_SMS\Config\Bootstrap;
use CAMOO_SMS\Gateway\Camoo;
use CamooSms\Gateway\Infrastructure\Enum\GatewayFactory;
use CamooSms\Gateway\Infrastructure\Exception\GatewayFactoryException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use WP_Error;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * @global $wpdb
 * CAMOO_SMS gateway class
 */
class Gateway
{
    public ?string $username = null;

    public ?string $password = null;

    public bool $hasKey = false;

    public string $validateNumber = '';

    public ?string $help = null;

    public bool $canSendBulk = true;

    public string $from = '';

    public mixed $to = null;

    public string $msg = '';

    public mixed $options;

    public static mixed $requestResponse = null;

    protected wpdb $db;

    protected string $tbPrefix;

    public function __construct()
    {
        global $wpdb;

        $this->db = $wpdb;
        $this->tbPrefix = $wpdb->prefix;
        $this->options = Option::getOptions();

        // Check option for add country code to prefix numbers
        if (isset($this->options['mobile_county_code']) && $this->options['mobile_county_code']) {
            add_filter('wp_camoo_sms_to', [$this, 'applyCountryCode']);
        }

        // Add Filters
        add_filter('wp_camoo_sms_to', [$this, 'modify_bulk_send']);
    }

    /** Initial Gateway */
    public static function initial()
    {
        // Include default gateway
        include_once WP_CAMOO_SMS_DIR . 'includes/class-wpsms-gateway.php';
        include_once WP_CAMOO_SMS_DIR . 'includes/gateways/class-wpsms-gateway-default.php';

        $gatewayName = Option::getOption('gateway_name');
        // Using default gateway if it does not set gateway in the setting
        if (empty($gatewayName)) {
            return self::getGatewayInstance('default');
        }

        if (is_file(WP_CAMOO_SMS_DIR . 'includes/gateways/class-wpsms-gateway-' . $gatewayName . '.php')) {
            include_once WP_CAMOO_SMS_DIR . 'includes/gateways/class-wpsms-gateway-' . $gatewayName . '.php';
        } elseif (is_file(WP_PLUGIN_DIR . '/camoo-sms/includes/gateways/class-wpsms-pro-gateway-' . $gatewayName . '.php')) {
            include_once WP_PLUGIN_DIR . '/camoo-sms/includes/gateways/class-wpsms-pro-gateway-' . $gatewayName . '.php';
        }

        /** @var Camoo $oCamooSMS */
        $oCamooSMS = self::getGatewayInstance($gatewayName);

        // Set username and password
        $oCamooSMS->username = Option::getOption('gateway_username');
        $oCamooSMS->password = Option::getOption('gateway_password');

        $gatewayKey = Option::getOption('gateway_key');

        // Set api key
        if ($oCamooSMS->hasKey && $gatewayKey) {
            $oCamooSMS->hasKey = (bool)$gatewayKey;
        }

        // Show gateway help configuration in gateway page
        if ($oCamooSMS->help) {
            add_action('wp_camoo_sms_after_gateway', function () use ($oCamooSMS) {
                echo ' < p class="description" > ' . $oCamooSMS->help . '</p > ';
            });
        }

        // Check unit credit gateway
        if ($oCamooSMS->unitrial === true) {
            $oCamooSMS->unit = __('Credit', 'wp - sms');
        } else {
            $oCamooSMS->unit = __('SMS', 'wp - sms');
        }

        // Set sender id
        if (!$oCamooSMS->from) {
            $oCamooSMS->from = Option::getOption('gateway_sender_id');
        }

        // SET encryption setting
        $oCamooSMS->encryptSMS = Option::getOption('encrypt_sms') == 1;

        // SET data coding
        $oCamooSMS->isUnicode = Option::getOption('send_unicode') == 1;

        if (Option::getOption('bulk_chunk')) {
            $oCamooSMS->canSendBulk = (bool)Option::getOption('bulk_chunk');
        }

        if (Option::getOption('bulk_threshold')) {
            $oCamooSMS->bulkThreshold = (int)Option::getOption('bulk_threshold');
        }

        // Unset gateway key field if not available in the current gateway class.
        add_filter('wp_camoo_sms_gateway_settings', function (mixed $filter) {
            global $oCamooSMS;

            if (!$oCamooSMS->hasKey) {
                unset($filter['gateway_key']);
            }

            return $filter;
        });

        // Return gateway object
        return $oCamooSMS;
    }

    /** @return false|int */
    public function log(array $options, string $status = 'sent'): mixed
    {
        $hData = [
            'sender' => $options['sender'],
            'message' => $options['message'],
            'recipient' => is_array($options['to']) ? implode(',', $options['to']) : $options['to'],
            'response' => var_export($options['response'], true),
            'status' => $status,
        ];
        if (array_key_exists('message_id', $options)) {
            $hData['message_id'] = $options['message_id'];
        }
        if (array_key_exists('reference', $options)) {
            $hData['reference'] = $options['reference'];
        }

        return $this->db->insert($this->tbPrefix . 'camoo_sms_send', $hData);
    }

    /** Apply Country code to prefix numbers */
    public function applyCountryCode(array $recipients = []): array
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        $country = $this->options['mobile_county_code'];
        $numbers = [];

        foreach ($recipients as $number) {
            // Remove zero from first number
            $number = ltrim($number, '0');

            try {
                // phone must begin with '+'
                $numberProto = $phoneUtil->parse($number, '');
                $countryCode = $numberProto->getCountryCode();
            } catch (NumberParseException) {
                $countryCode = null;
            }

            // Add country code to prefix number
            if ($countryCode === null) {
                $numbers[] = $country . $number;
            } else {
                // number already prefixed
                $numbers[] = $number;
            }
        }

        return $numbers;
    }

    public static function gateway(): mixed
    {
        $sCamoo = ' (' . __('Recommended', Bootstrap::DOMAIN_TEXT) . ')';

        $gateways = [
            '' => [
                'default' => __('Please select your gateway', Bootstrap::DOMAIN_TEXT),
            ],
            'camoo' => [
                'camoo' => 'camoo.cm' . $sCamoo,
            ],
        ];

        return apply_filters('wpcamoosms_gateway_list', $gateways);
    }

    public static function status(): ?string
    {
        global $oCamooSMS;

        //Check that, Are we in the Gateway CAMOO_SMS tab setting page or not?
        $canManageStatus = isset($_REQUEST['page']) &&
            isset($_REQUEST['tab']) &&
            $_REQUEST['page'] === 'wp-camoo-sms-settings' &&
            $_REQUEST['tab'] === 'gateway';

        if (!is_admin() || $canManageStatus === false) {
            return null;
        }
        // Get credit
        $result = $oCamooSMS->getCredit();

        if (is_wp_error($result)) {
            // Set error message
            self::$requestResponse = var_export($result->get_error_message(), true);

            // Update credit
            update_option('wp_camoo_sms_gateway_credit', 0);

            // Return html
            return '<div class="wpsms-no-credit"><span class="dashicons dashicons-no"></span> ' .
                __('Deactive!', 'wp-camoo-sms') . '</div>';
        }
        // Update credit
        if (!is_object($result)) {
            update_option('wp_camoo_sms_gateway_credit', $result);
        }
        self::$requestResponse = var_export($result, true);

        // Return html
        return '<div class="wpsms-has-credit"><span class="dashicons dashicons-yes"></span> ' .
            __('Active!', 'wp-camoo-sms') . '</div>';
    }

    public static function response(): mixed
    {
        return self::$requestResponse;
    }

    public static function help(): ?string
    {
        global $oCamooSMS;

        // Get gateway help
        return $oCamooSMS->help;
    }

    public static function from(): string
    {
        global $oCamooSMS;

        // Get gateway from
        return $oCamooSMS->from ?? '';
    }

    public static function bulk_status(): string
    {
        global $oCamooSMS;

        // Get bulk status
        if ($oCamooSMS->canSendBulk === true) {
            // Return html
            return '<div class="wpsms-has-credit"><span class="dashicons dashicons-yes"></span> ' .
                __('Supported', 'wp-camoo-sms') . '</div>';
        }
        // Return html
        return '<div class="wpsms-no-credit"><span class="dashicons dashicons-no"></span> ' .
            __('Does not support!', 'wp-camoo-sms') . '</div>';
    }

    public static function credit(): WP_Error|int|float
    {
        global $oCamooSMS;
        $result = $oCamooSMS->getCredit();

        if (is_wp_error($result)) {
            update_option('wp_camoo_sms_gateway_credit', 0);

            return 0;
        }

        if (!is_object($result)) {
            update_option('wp_camoo_sms_gateway_credit', $result);
        }

        return $result;
    }

    /**
     * Modify destination number
     *
     * @return array/string
     */
    public function modify_bulk_send(array $to): array
    {
        global $oCamooSMS;
        if (!$oCamooSMS->canSendBulk) {
            return [$to[0]];
        }

        return $to;
    }

    public static function can_bulk_send(): bool
    {
        global $oCamooSMS;

        return $oCamooSMS->canSendBulk;
    }

    private static function getGatewayInstance(string $gatewayName): Gateway
    {
        $name = ucfirst($gatewayName);
        foreach (GatewayFactory::cases() as $factory) {
            if ($factory->name === $name) {
                return $factory->getInstance();
            }
        }
        throw new GatewayFactoryException(__('Gateway instance cannot be found!', 'wp-camoo-sms'));
    }
}
