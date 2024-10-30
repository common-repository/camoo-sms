<?php
/**
 * Uninstalling CAMOO SMS deletes tables, and options.
 *
 * @version 2.0.0
 *
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */
defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

delete_option('wp_camoo_sms_db_version');
delete_option('widget_wpcamoosms_widget');

$wpdb->query("DELETE FROM {$wpdb->options} WHERE `option_name` LIKE 'wp_camoo_sms%';");

foreach (['camoo_sms_subscribes', 'camoo_sms_subscribes_group', 'camoo_sms_send'] as $tbl) {
    $table = $wpdb->tb_prefix . $tbl;
    $wpdb->query('DROP TABLE IF EXISTS ' . sanitize_text_field($table) . ';');
}
