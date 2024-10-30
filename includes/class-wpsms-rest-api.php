<?php

namespace CAMOO_SMS;

use WP_Error;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class RestApi
{
    public $sms;

    public $namespace;

    protected $option;

    protected $db;

    protected $tb_prefix;

    /** @var mixed|void */
    private $options;

    public function __construct()
    {
        global $oCamooSMS, $wpdb;

        $this->sms = $oCamooSMS;
        $this->options = Option::getOptions();
        $this->db = $wpdb;
        $this->tb_prefix = $wpdb->prefix;
        $this->namespace = 'camoosms';
    }

    /**
     * Handle Response
     */
    public static function response($message, int $status = 200): WP_REST_Response
    {
        if ($status == 200) {
            $output = [
                'message' => $message,
                'error' => [],
            ];
        } else {
            $output = [
                'error' => [
                    'code' => $status,
                    'message' => $message,
                ],
            ];
        }

        return new WP_REST_Response($output, $status);
    }

    /**
     * Subscribe User
     *
     * @param null $group
     *
     * @return string|WP_Error
     */
    public static function subscribe($name, $mobile, $group)
    {
        global $oCamooSMS;

        if (empty($name) || empty($mobile)) {
            return new WP_Error('subscribe', __('The name and mobile number must be valued!', 'wp-camoo-sms'));
        }

        $check_group = Newsletter::getGroup($group);

        if (!isset($check_group)) {
            return new WP_Error('subscribe', __('The group number is not valid!', 'wp-camoo-sms'));
        }

        if (preg_match(CAMOO_SMS_MOBILE_REGEX, $mobile) === false) {
            // Return response
            return new WP_Error('subscribe', __('Please enter a valid mobile number', 'wp-camoo-sms'));
        }

        $max_number = Option::getOption('mobile_terms_maximum');

        if ($max_number) {
            if (strlen($mobile) > $max_number) {
                // Return response
                return new WP_Error('subscribe', sprintf(__(
                    'Your mobile number should be less than %s digits',
                    'wp-camoo-sms'
                ), $max_number));
            }
        }
        $min_number = Option::getOption('mobile_terms_minimum');
        if ($min_number && strlen($mobile) < $min_number) {
            // Return response
            return new WP_Error('subscribe', sprintf(__(
                'Your mobile number should be greater than %s digits',
                'wp-camoo-sms'
            ), $min_number));
        }

        $gateway_name = Option::getOption('gateway_name');

        if (Option::getOption('newsletter_form_verify') && $gateway_name) {
            // Check gateway setting

            $key = rand(1000, 9999);

            // Add subscribe to database
            $result = Newsletter::addSubscriber($name, $mobile, $group, '0', $key);

            if ($result['result'] == 'error') {
                // Return response
                return new WP_Error('subscribe', $result['message']);
            }
            $oCamooSMS->to = [$mobile];
            $oCamooSMS->msg = __('Your activation code', 'wp-camoo-sms') . ': ' . $key;
            $oCamooSMS->sendSMS();

            // Return response
            return __('You will join the newsletter, Activation code sent to your mobile.', 'wp-camoo-sms');
        }
        // Add subscribe to database
        $result = Newsletter::addSubscriber($name, $mobile, $group, '1');

        if ($result['result'] == 'error') {
            // Return response
            return new WP_Error('subscribe', $result['message']);
        }

        return __('Your number has been successfully subscribed.', 'wp-camoo-sms');
    }

    /**
     * Unsubscribe user
     *
     * @param null $group
     *
     * @return string|WP_Error
     */
    public static function unSubscribe($name, $mobile, $group)
    {
        if (empty($name) || empty($mobile)) {
            return new WP_Error('unsubscribe', __('The name and mobile number must be valued!', 'wp-camoo-sms'));
        }

        $check_group = Newsletter::getGroup($group);

        if (!isset($check_group) && empty($check_group)) {
            return new WP_Error('unsubscribe', __('The group number is not valid!', 'wp-camoo-sms'));
        }

        if (preg_match(CAMOO_SMS_MOBILE_REGEX, $mobile) === false) {
            // Return response
            return new WP_Error('unsubscribe', __('Please enter a valid mobile number', 'wp-camoo-sms'));
        }

        $max_number = Option::getOption('mobile_terms_maximum');

        if ($max_number && strlen($mobile) > $max_number) {
            // Return response
            return new WP_Error('unsubscribe', sprintf(__(
                'Your mobile number should be less than %s digits',
                'wp-camoo-sms'
            ), $max_number));
        }

        $max_number = Option::getOption('mobile_terms_minimum');

        if ($max_number && strlen($mobile) < $max_number) {
            // Return response
            return new WP_Error('unsubscribe', sprintf(__(
                'Your mobile number should be greater than %s digits',
                'wp-camoo-sms'
            ), $max_number));
        }
        // Delete subscriber
        $result = Newsletter::deleteSubscriberByNumber($mobile, $group);

        // Check result
        if ($result['result'] === 'error') {
            // Return response
            return new WP_Error('unsubscribe', $result['message']);
        }

        return __('Your subscription was canceled.', 'wp-camoo-sms');
    }

    /**
     * Verify Subscriber
     *
     * @return string|WP_Error
     */
    public static function verifySubscriber($name, $mobile, $activation, $group)
    {
        global $oCamooSMS, $wpdb;

        if (empty($name) || empty($mobile) || empty($activation)) {
            return new WP_Error('unsubscribe', __('The required parameters must be valued!', 'wp-camoo-sms'));
        }

        // Check the mobile number is string or integer
        if (strpos($mobile, '+') !== false) {
            $db_prepare = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}camoo_sms_subscribes` WHERE `mobile` = %s AND `status` = %d AND group_ID = %d", $mobile, 0, $group);
        } else {
            $db_prepare = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}camoo_sms_subscribes` WHERE `mobile` = %d AND `status` = %d AND group_ID = %d", $mobile, 0, $group);
        }

        $check_mobile = $wpdb->get_row($db_prepare);

        if (isset($check_mobile)) {
            if ($activation != $check_mobile->activate_key) {
                // Return response
                return new WP_Error('verify_subscriber', __('Activation code is wrong!', 'wp-camoo-sms'));
            }

            // Check the mobile number is string or integer
            if (strpos($mobile, '+') !== false) {
                $result = $wpdb->update(
                    "{$wpdb->prefix}camoo_sms_subscribes",
                    ['status' => '1'],
                    ['mobile' => $mobile, 'group_ID' => $group],
                    ['%d', '%d'],
                    ['%s']
                );
            } else {
                $result = $wpdb->update(
                    "{$wpdb->prefix}camoo_sms_subscribes",
                    ['status' => '1'],
                    ['mobile' => $mobile, 'group_ID' => $group],
                    ['%d', '%d'],
                    ['%d']
                );
            }

            if ($result) {
                // Send welcome message
                if (Option::getOption('newsletter_form_welcome')) {
                    $template_vars = [
                        '%subscribe_name%' => $name,
                        '%subscribe_mobile%' => $mobile,
                    ];
                    $text = Option::getOption('newsletter_form_welcome_text');
                    $message = str_replace(array_keys($template_vars), array_values($template_vars), $text);

                    $oCamooSMS->to = [$mobile];
                    $oCamooSMS->msg = $message;
                    $oCamooSMS->sendSMS();
                }

                // Return response
                return __('Your subscription was successful!', 'wp-camoo-sms');
            }
        }

        return new WP_Error('verify_subscriber', __('Not found the number!', 'wp-camoo-sms'));
    }

    /**
     * Get Subscribers
     *
     * @param string $page
     * @param string $group_id
     * @param string $mobile
     * @param string $search
     *
     * @return array|object|null
     */
    public static function getSubscribers($page = '', $group_id = '', $mobile = '', $search = '')
    {
        global $wpdb;

        $result_limit = 50;
        $where = '';
        $limit = $wpdb->prepare(' LIMIT %d', $result_limit);

        if ($page) {
            $limit = $limit . $wpdb->prepare(' OFFSET %d', $result_limit * $page - $result_limit);
        }
        if ($group_id and $where) {
            $where .= $wpdb->prepare(' AND group_ID = %d', $group_id);
        } elseif ($group_id and !$where) {
            $where = $wpdb->prepare('WHERE group_ID = %d', $group_id);
        }

        if ($mobile and $where) {
            $where .= $wpdb->prepare(' AND mobile = %s', $mobile);
        } elseif ($mobile and !$where) {
            $where = $wpdb->prepare('WHERE mobile = %s', $mobile);
        }

        if ($search and $where) {
            $where .= $wpdb->prepare(' AND name LIKE %s', '%' . $wpdb->esc_like($search) . '%');
        } elseif ($search and !$where) {
            $where = $wpdb->prepare('WHERE name LIKE "%s"', '%' . $wpdb->esc_like($search) . '%');
        }

        $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}camoo_sms_subscribes {$where}{$limit}");

        return $result;
    }

    /**
     * Send SMS
     *
     * @param bool $isflash
     */
    public static function sendSMS($to, $msg, $isflash = false)
    {
        // Check if valued required parameters or not
        if (empty($to) or empty($msg)) {
            return new WP_Error('send_sms', __('The required parameters must be valued!', 'wp-camoo-sms'));
        }

        // Get the result
        global $oCamooSMS;
        $oCamooSMS->to = [$to];
        $oCamooSMS->msg = $msg;

        return $oCamooSMS->sendSMS();
    }
}

(new RestApi());
