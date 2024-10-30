<?php

namespace CAMOO_SMS;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

use CAMOO_SMS\Admin\Helper;
use CAMOO_SMS\Gateway\Camoo;
use wpdb;

/**
 * Class Send SMS Page
 */
class SMS_Send
{
    public Gateway|Camoo $sms;

    protected wpdb $db;

    protected string $tbPrefix;

    protected mixed $options;

    public function __construct()
    {
        global $wpdb, $oCamooSMS;

        $this->db = $wpdb;
        $this->tbPrefix = $wpdb->prefix;
        $this->sms = $oCamooSMS;
        $this->options = Option::getOptions();
    }

    /** Sending sms admin page */
    public function render_page()
    {
        $get_group_result = $this->db->get_results("SELECT * FROM `{$this->db->prefix}camoo_sms_subscribes_group`");
        $get_users_mobile = $this->db->get_col("SELECT `meta_value` FROM `{$this->db->prefix}usermeta` WHERE `meta_key` = 'mobile'");

        $mobile_field = Option::getOption('add_mobile_field');

        //Get User Mobile List by Role
        if (!empty($mobile_field) && $mobile_field == 1) {
            $wpcamoosms_list_of_role = [];
            foreach (wp_roles()->role_names as $key_item => $val_item) {
                $wpcamoosms_list_of_role[$key_item] = [
                    'name' => $val_item,
                    'count' => count(get_users([
                        'meta_key' => 'mobile',
                        'meta_value' => '',
                        'meta_compare' => '!=',
                        'role' => $key_item,
                        'fields' => 'ID',
                    ])),
                ];
            }
        }

        $gateway_name = Option::getOption('gateway_name');
        $credit = Option::getOptions('wp_camoo_sms_gateway_credit');

        if ($gateway_name && $credit === null) {
            echo '<br><div class="update-nag">' . __('You should have sufficient funds for sending sms in the account', 'wp-camoo-sms') . '</div>';

            return;
        } elseif (!$gateway_name) {
            $params = ['page' => 'wp-camoo-sms-settings', 'tab' => 'gateway'];
            echo '<br><div class="update-nag">' . sprintf(__('You should choose and configuration your gateway in the Setting page.<br><a href="%s">Click here</a> to configure the camoo\'s gateway', 'wp-camoo-sms'), Helper::adminUrl($params)) . '</div>';

            return;
        }

        if (isset($_POST['sendSMS']) && isset($_POST['camoo_sms_send']) && wp_verify_nonce($_POST['camoo_sms_send'], 'camoo_sms_send')) {
            if ($_POST['wp_get_message']) {
                if ($_POST['wp_send_to'] == 'wp_subscribe_username') {
                    if ($_POST['wpcamoosms_group_name'] == 'all') {
                        $this->sms->to = $this->db->get_col("SELECT mobile FROM {$this->db->prefix}camoo_sms_subscribes WHERE `status` = '1'");
                    } else {
                        $this->sms->to = $this->db->get_col("SELECT mobile FROM {$this->db->prefix}camoo_sms_subscribes WHERE `status` = '1' AND `group_ID` = '" . sanitize_text_field($_POST['wpcamoosms_group_name']) . "'");
                    }
                } elseif ($_POST['wp_send_to'] == 'wp_users') {
                    $this->sms->to = $get_users_mobile;
                } elseif ($_POST['wp_send_to'] == 'wp_tellephone') {
                    $this->sms->to = explode(',', sanitize_text_field($_POST['wp_get_number']));
                } elseif ($_POST['wp_send_to'] == 'wp_role') {
                    $to = [];
                    add_action('pre_user_query', [SMS_Send::class, 'get_query_user_mobile']);
                    $list = get_users([
                        'meta_key' => 'mobile',
                        'meta_value' => '',
                        'meta_compare' => '!=',
                        'role' => sanitize_text_field($_POST['wpcamoosms_group_role']),
                        'fields' => 'all',
                    ]);
                    remove_action('pre_user_query', [SMS_Send::class, 'get_query_user_mobile']);
                    foreach ($list as $user) {
                        $to[] = $user->mobile;
                    }
                    $this->sms->to = $to;
                }

                $this->sms->from = sanitize_text_field($_POST['wp_get_sender']);
                $this->sms->msg = sanitize_textarea_field($_POST['wp_get_message']);

                $this->sms->isFlash = isset($_POST['wp_flash']) && $_POST['wp_flash'] === 'true';
                if (isset($_POST['wp_route']) && sanitize_key($_POST['wp_route']) === 'classic') {
                    $this->sms->smsRoute = 'classic';
                }

                // Send sms
                $response = $this->sms->sendSMS();

                if (is_wp_error($response)) {
                    if (is_array($response->get_error_message())) {
                        $response = print_r($response->get_error_message(), 1);
                    } else {
                        $response = $response->get_error_message();
                    }

                    echo "<div class='error'><p>" . sprintf(__('<strong>SMS was not delivered! results received:</strong> %s', 'wp-camoo-sms'), $response) . '</p></div>';
                } else {
                    echo "<div class='updated'><p>" . __('The SMS sent successfully', 'wp-camoo-sms') . '</p></div>';
                    $credit = Gateway::credit();
                }
            } else {
                echo "<div class='error'><p>" . __('Please enter your SMS message.', 'wp-camoo-sms') . '</p></div>';
            }
        }

        include_once WP_CAMOO_SMS_DIR . 'includes/admin/send/send-sms.php';
    }

    /**
     * Custom Query for Get All User Mobile in special Role
     */
    public static function get_query_user_mobile($user_query)
    {
        global $wpdb;

        $user_query->query_fields .= ', m1.meta_value AS mobile ';
        $user_query->query_from .= " JOIN {$wpdb->usermeta} m1 ON (m1.user_id = {$wpdb->users}.ID AND m1.meta_key = 'mobile') ";

        return $user_query;
    }
}

(new SMS_Send());
