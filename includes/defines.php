<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Check get_plugin_data function exist
 */
if (!function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Set Plugin path and url defines.
define('WP_CAMOO_SMS_URL', plugin_dir_url(dirname(__FILE__)));
define('WP_CAMOO_SMS_DIR', plugin_dir_path(dirname(__FILE__)));

// Get plugin Data.
$plugin_data = get_plugin_data(WP_CAMOO_SMS_DIR . 'camoo-sms.php');

// Set another useful Plugin defines.
define('WP_CAMOO_SMS_VERSION', $plugin_data['Version']);
define('WP_CAMOO_SMS_ADMIN_URL', get_admin_url());
const WP_CAMOO_SMS_SITE = 'https://www.camoo.cm';
if (!defined('CAMOO_SMS_MOBILE_REGEX')) {
    define('CAMOO_SMS_MOBILE_REGEX', '/^[\+|\(|\)|\d|\- ]*$/');
}
if (!defined('CAMOO_SMS_CURRENT_DATE')) {
    define('CAMOO_SMS_CURRENT_DATE', date('Y-m-d H:i:s', current_time('timestamp')));
}
const CAMOO_SMS_MIN_PHP_VERSION = 80100;
