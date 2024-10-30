<?php

namespace CAMOO_SMS;

use CAMOO_SMS\Admin\Helper;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Subscribers
{
    /** Subscribe admin page */
    public function render_page()
    {
        // Add subscriber
        if (isset($_POST['wp_add_subscribe']) && isset($_POST['camoo_sms_n']) && wp_verify_nonce($_POST['camoo_sms_n'], 'camoo_sms_n')) {
            $group = isset($_POST['wpcamoosms_group_name']) ? sanitize_text_field($_POST['wpcamoosms_group_name']) : '';
            if ($group) {
                $result = Newsletter::addSubscriber(sanitize_text_field($_POST['wp_subscribe_name']), sanitize_text_field($_POST['wp_subscribe_mobile']), $group);
            } else {
                $result = Newsletter::addSubscriber(sanitize_text_field($_POST['wp_subscribe_name']), sanitize_text_field($_POST['wp_subscribe_mobile']));
            }

            echo Helper::notice($result['message'], $result['result']);
        }

        // Edit subscriber page
        if (isset($_POST['wp_update_subscribe']) && isset($_POST['camoo_sms_n']) && wp_verify_nonce($_POST['camoo_sms_n'], 'camoo_sms_n')) {
            $group = isset($_POST['wpcamoosms_group_name']) ? sanitize_text_field($_POST['wpcamoosms_group_name']) : '';
            $result = Newsletter::updateSubscriber(sanitize_key($_POST['ID']), sanitize_text_field($_POST['wp_subscribe_name']), sanitize_text_field($_POST['wp_subscribe_mobile']), $group, sanitize_text_field($_POST['wpcamoosms_subscribe_status']));
            echo Helper::notice($result['message'], $result['result']);
        }

        // Import subscriber page
        if (isset($_POST['wps_import']) && isset($_POST['camoo_sms_n']) && wp_verify_nonce($_POST['camoo_sms_n'], 'camoo_sms_n')) {
            include_once WP_CAMOO_SMS_DIR . 'includes/admin/import.php';
        }

        include_once WP_CAMOO_SMS_DIR . 'includes/admin/subscribers/class-wpsms-subscribers-table.php';

        //Create an instance of our package class...
        $list_table = new Subscribers_List_Table();

        //Fetch, prepare, sort, and filter our data...
        $list_table->prepare_items();

        include_once WP_CAMOO_SMS_DIR . 'includes/admin/subscribers/subscribers.php';
    }
}
