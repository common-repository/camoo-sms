<?php

namespace CAMOO_SMS;

use CAMOO_SMS\Config\Bootstrap;
use EDD_Payment;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use WC_Countries;
use WC_Order;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Integrations
{
    public $sms;

    public $date;

    public $options;

    public $cf7_data;

    public function __construct()
    {
        global $oCamooSMS;

        $this->sms = $oCamooSMS;
        $this->date = CAMOO_SMS_CURRENT_DATE;
        $this->options = Option::getOptions();
        // Contact Form 7
        if (isset($this->options['cf7_metabox'])) {
            add_filter('wpcf7_editor_panels', [$this, 'cf7_editor_panels']);
            add_action('wpcf7_after_save', [$this, 'wpcf7_save_form']);
            add_action('wpcf7_before_send_mail', [$this, 'wpcf7_camoosms_handler']);
        }

        // SmobilPay for e-commerce
        if (!empty($this->options['enkap_notify_status_changed'])) {
            add_action('smobilpay_after_status_change', [$this, 'informCustomerBySmobilpay'], 10, 2);
        }

        // Woocommerce
        if (isset($this->options['wc_notif_new_order'])) {
            add_action('woocommerce_new_order', [$this, 'wc_new_order']);
        }

        // EDD
        if (isset($this->options['edd_notif_new_order'])) {
            add_action('edd_complete_purchase', [$this, 'edd_new_order']);
        }

        // Woocommerce inform customer
        if (isset($this->options['wc_notify_status_changed'])) {
            add_action('woocommerce_order_status_changed', [$this, 'informCustomerByWoocommerceStatusChanged'], 10, 4);
        }
    }

    public function cf7_editor_panels($panels): array
    {
        $new_page = [
            'camoosms' => [
                'title' => __('CAMOO SMS Notification', Bootstrap::DOMAIN_TEXT),
                'callback' => [$this, 'cf7_setup_form'],
            ],
        ];

        return array_merge($panels, $new_page);
    }

    public function cf7_setup_form($form)
    {
        $cf7_options = Option::getOptions('wpcf7_camoosms_' . $form->id());
        $cf7_options_field = Option::getOptions('wpcf7_camoosms_form' . $form->id());

        if (!isset($cf7_options['phone'])) {
            $cf7_options['phone'] = '';
        }
        if (!isset($cf7_options['message'])) {
            $cf7_options['message'] = '';
        }
        if (!isset($cf7_options_field['phone'])) {
            $cf7_options_field['phone'] = '';
        }
        if (!isset($cf7_options_field['message'])) {
            $cf7_options_field['message'] = '';
        }

        include_once dirname(__DIR__) . '/includes/templates/wpcf7-form.php';
    }

    public function wpcf7_save_form($form)
    {
        update_option('wpcf7_camoosms_' . $form->id(), sanitize_text_field($_POST['wpcf7-sms']));
        update_option('wpcf7_camoosms_form' . $form->id(), sanitize_text_field($_POST['wpcf7-sms-form']));
    }

    public function wpcf7_camoosms_handler($form)
    {
        $cf7_options = Option::getOptions('wpcf7_camoosms_' . $form->id());
        $cf7_options_field = Option::getOptions('wpcf7_camoosms_form' . $form->id());
        $this->set_cf7_data();

        if ($cf7_options['message'] && $cf7_options['phone']) {
            $this->sms->to = explode(',', $cf7_options['phone']);

            $this->sms->msg = preg_replace_callback('/%([a-zA-Z0-9._-]+)%/', function ($matches) {
                foreach ($matches as $item) {
                    if (isset($this->cf7_data[$item])) {
                        return $this->cf7_data[$item];
                    }
                }

                return '';
            }, $cf7_options['message']);

            $this->sms->sendSMS();
        }

        if ($cf7_options_field['message'] && $cf7_options_field['phone']) {
            $to = preg_replace_callback('/%([a-zA-Z0-9._-]+)%/', function ($matches) {
                foreach ($matches as $item) {
                    if (isset($this->cf7_data[$item])) {
                        return $this->cf7_data[$item];
                    }
                }

                return '';
            }, $cf7_options_field['phone']);

            $this->sms->to = [$to];

            $this->sms->msg = preg_replace_callback('/%([a-zA-Z0-9._-]+)%/', function ($matches) {
                foreach ($matches as $item) {
                    if (isset($this->cf7_data[$item])) {
                        return $this->cf7_data[$item];
                    }
                }

                return '';
            }, $cf7_options_field['message']);

            $this->sms->sendSMS();
        }
    }

    public function wc_new_order(int $order_id)
    {
        $order = new WC_Order($order_id);
        $this->sms->to = [$this->options['admin_mobile_number']];
        $template_vars = [
            '%order_id%' => $order_id,
            '%status%' => $order->get_status(),
            '%order_number%' => $order->get_order_number(),
        ];
        $message = str_replace(
            array_keys($template_vars),
            array_values($template_vars),
            $this->options['wc_notif_new_order_template']
        );
        $this->sms->msg = $message;

        $this->sms->sendSMS();
    }

    public function edd_new_order()
    {
        $this->sms->to = [$this->options['admin_mobile_number']];
        $this->sms->msg = $this->options['edd_notif_new_order_template'];
        $this->sms->sendSMS();
    }

    public function informCustomerBySmobilpay(string $identification, string $shopType): void
    {
        $notify_shop_type = $this->options['enkap_notify_shop_type'];
        if (empty($notify_shop_type) || !in_array($shopType, $notify_shop_type) ||
            empty($this->options['enkap_notify_status_changed_template'])) {
            return;
        }

        $payment = $this->getPaymentHistory($identification, $shopType);
        if (empty($payment)) {
            return;
        }

        $userId = 0;
        $amount = 'XAF ';
        if ($shopType === 'wc') {
            $order = new WC_Order($payment->wc_order_id);
            $userId = $order->get_user_id();
            $amount .= $order->get_total();
        }
        if ($shopType === 'edd') {
            $order = new EDD_Payment($payment->edd_order_id);
            $userId = $order->user_id;
            $amount .= $order->total;
        }

        $phone = $this->get_customer_phone($userId, $shopType);

        if (empty($phone)) {
            return;
        }

        $firstName = get_user_meta($userId, 'first_name', true);
        $lastName = get_user_meta($userId, 'last_name', true);

        $this->sms->to = [$phone];
        $template_vars = [
            '%merchant_reference_id%' => $payment->merchant_reference_id,
            '%order_transaction_id"%' => $payment->order_transaction_id,
            '%status%' => $payment->status,
            '%amount%' => $amount,
            '%first_name%' => $firstName,
            '%last_name%' => $lastName,
        ];
        $message = str_replace(
            array_keys($template_vars),
            array_values($template_vars),
            $this->options['enkap_notify_status_changed_template']
        );
        $this->sms->msg = $message;

        $this->sms->sendSMS();
    }

    public function informCustomerByWoocommerceStatusChanged(
        int $orderId,
        ?string $fromStatus,
        ?string $toStatus,
        WC_Order $order
    ): void {
        $wc_notify_status_type = $this->options['wc_notify_status_type'];
        if (empty($wc_notify_status_type) || !in_array($toStatus, $wc_notify_status_type) ||
            empty($this->options['wc_notify_status_changed_template'])) {
            return;
        }

        $amount = $order->get_currency() . ' ';
        $userId = $order->get_user_id();
        $amount .= $order->get_total();

        $phone = $this->get_customer_phone($userId, 'wc');

        if (empty($phone)) {
            return;
        }

        $firstName = get_user_meta($userId, 'first_name', true);
        $lastName = get_user_meta($userId, 'last_name', true);

        $this->sms->to = [$phone];
        $template_vars = [
            '%order_id%' => $orderId,
            '%new_status%' => $toStatus,
            '%old_status%' => $fromStatus,
            '%amount%' => $amount,
            '%first_name%' => $firstName,
            '%last_name%' => $lastName,
        ];
        $message = str_replace(
            array_keys($template_vars),
            array_values($template_vars),
            $this->options['wc_notify_status_changed_template']
        );
        $this->sms->msg = $message;

        $this->sms->sendSMS();
    }

    private function set_cf7_data()
    {
        foreach ($_POST as $index => $key) {
            if (is_array($key)) {
                $this->cf7_data[$index] = implode(', ', $key);
            } else {
                $this->cf7_data[$index] = $key;
            }
        }
    }

    private function getPaymentHistory(string $identification, string $shopType)
    {
        global $wpdb;

        $table = null;
        $field = null;
        if ($shopType === 'wc') {
            $table = 'wc_enkap_payments';
            $field = 'merchant_reference_id';
        }

        if ($shopType === 'edd') {
            $table = 'edd_enkap_payments';
            $field = 'order_transaction_id';
        }

        if (null === $field) {
            return null;
        }

        $tableAndPrefix = $wpdb->prefix . $table;
        $db_prepare = $wpdb->prepare(
            "SELECT * FROM {$tableAndPrefix} WHERE {$field} = %s",
            sanitize_text_field($identification)
        );
        $payment = $wpdb->get_row($db_prepare);
        if (strtolower($this->options['enkap_notify_new_status']) !== strtolower($payment->status)) {
            return null;
        }

        return $payment;
    }

    private function get_customer_phone(int $userId, $shopType): ?string
    {
        $telCode = '';
        $country = get_user_meta($userId, 'billing_country', true);
        $phone = get_user_meta($userId, 'billing_phone', true);
        if (empty($phone)) {
            return null;
        }

        if (!empty($country) && $shopType === 'wc') {
            $telCode = (new WC_Countries())->get_country_calling_code($country);
        }

        $country_code = $this->options['mobile_county_code'];
        if (empty($telCode)) {
            $telCode = $country_code;
        }

        if (!empty($telCode)) {
            $phoneUtil = PhoneNumberUtil::getInstance();

            // Remove zero from first number
            $number = ltrim($phone, '0');

            try {
                // phone must begin with '+'
                $numberProto = $phoneUtil->parse($number, '');
                $countryCode = $numberProto->getCountryCode();
            } catch (NumberParseException $exception) {
                $countryCode = null;
            }

            // Add country code to prefix number
            if ($countryCode === null) {
                $phone = $telCode . $number;
            }
        }

        return $phone;
    }
}

(new Integrations());
