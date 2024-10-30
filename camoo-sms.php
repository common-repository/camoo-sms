<?php
/**
 * Plugin Name: Camoo SMS
 * Plugin URI: https://www.camoo.cm/bulk-sms
 * Description: With CAMOO SMS, you have the ability to send (Bulk) SMS to a group, to a user, to a number, to members of SMS newsletter or to every single event in your site. The usage of this plugin is completely free. You have to just have a CAMOO account. <a target="_blank" href="https://www.camoo.cm/join">Sign up</a> for a free account. Ask CAMOO Team for new access_key
 * Version: 3.0.1
 * Author: Camoo Sarl
 * Author URI: https://www.camoo.cm/
 * Text Domain: wp-camoo-sms
 * Domain Path: /languages
 * Tested up to: 6.2.2
 * Requires at least: 3.0
 * Requires PHP: 8.1
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use CAMOO_SMS\Config\Bootstrap;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Load plugin Special Options init
 */
require_once plugin_dir_path(__FILE__) . 'includes/config/bootstrap.php';

$oCamooSMS = (new Bootstrap())->initialize();

(new CAMOO_SMS());
