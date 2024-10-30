<?php

namespace CAMOO_SMS;

use CAMOO_SMS\Config\Bootstrap;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Install
{
    public function __construct()
    {
        add_action('wpmu_new_blog', [$this, 'add_table_on_create_blog'], 10, 1);
        add_filter('wpmu_drop_tables', [$this, 'remove_table_on_delete_blog']);
    }

    /**
     * Adding new MYSQL Table in Activation Plugin
     */
    public static function create_table($network_wide)
    {
        global $wpdb;

        if (is_multisite() && $network_wide) {
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);

                self::table_sql();

                restore_current_blog();
            }
        } else {
            self::table_sql();
        }
    }

    /** Table SQL */
    public static function table_sql()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name = $wpdb->prefix . 'camoo_sms_subscribes';
        if ($wpdb->get_var("show tables like '{$table_name}'") != $table_name) {
            $create_sms_subscribes = ("CREATE TABLE IF NOT EXISTS {$table_name}(
            ID int(10) NOT NULL auto_increment,
            date DATETIME,
            name VARCHAR(250),
            mobile VARCHAR(20) NOT NULL,
            status tinyint(1),
            activate_key INT(11),
            group_ID int(5),
            PRIMARY KEY(ID)) CHARSET=utf8");

            dbDelta($create_sms_subscribes);
        }

        $table_name = $wpdb->prefix . 'camoo_sms_subscribes_group';
        if ($wpdb->get_var("show tables like '{$table_name}'") != $table_name) {
            $create_sms_subscribes_group = ("CREATE TABLE IF NOT EXISTS {$table_name}(
            ID int(10) NOT NULL auto_increment,
            name VARCHAR(250),
            created_at timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(ID)) CHARSET=utf8");

            dbDelta($create_sms_subscribes_group);
        }

        $table_name = $wpdb->prefix . 'camoo_sms_send';
        if ($wpdb->get_var("show tables like '{$table_name}'") != $table_name) {
            $create_sms_send = ("CREATE TABLE IF NOT EXISTS {$table_name}(
            ID int(10) NOT NULL auto_increment,
			message_id varchar(100) NOT NULL DEFAULT '',
            date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            sender VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            recipient TEXT NOT NULL,
  			response TEXT NOT NULL,
			status varchar(10) NOT NULL DEFAULT 'sent',
			reference varchar(75) NOT NULL DEFAULT '',
			updated_at timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			status_time datetime NOT NULL DEFAULT '2023-01-01 00:00:00',
            PRIMARY KEY(ID)) CHARSET=utf8");

            dbDelta($create_sms_send);
        }
    }

    /**
     * Creating plugin tables
     */
    public static function install($network_wide)
    {
        self::create_table($network_wide);

        add_option('wp_camoo_sms_db_version', WP_CAMOO_SMS_VERSION);
        // Delete notification new wp_version option
        delete_option('wp_notification_new_wp_version');

        if (is_admin()) {
            self::upgrade();
        }
    }

    /** Upgrade plugin requirements if needed */
    public static function upgrade(): void
    {
        $installedVersion = Option::getOptions('wp_camoo_sms_db_version');

        if ($installedVersion > WP_CAMOO_SMS_VERSION) {
            return;
        }
        global $wpdb;

        // Add response and status for outbox
        $table_name = $wpdb->prefix . 'camoo_sms_send';
        $column = $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ',
            DB_NAME,
            $table_name,
            'response'
        ));

        if (empty($column)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD status varchar(10) NOT NULL AFTER recipient, ADD response TEXT NOT NULL AFTER recipient");
        }

        // Fix columns length issue
        $table_name = $wpdb->prefix . 'camoo_sms_subscribes';
        $wpdb->query("ALTER TABLE {$table_name} MODIFY name VARCHAR(250)");

        update_option('wp_camoo_sms_db_version', WP_CAMOO_SMS_VERSION);

        // Delete old last credit option
        delete_option('wp_last_credit');
    }

    /**
     * Creating Table for New Blog in WordPress
     */
    public function add_table_on_create_blog($blog_id)
    {
        if (is_plugin_active_for_network(Bootstrap::PLUGIN_MAIN_FILE)) {
            switch_to_blog($blog_id);

            self::table_sql();

            restore_current_blog();
        }
    }

    /**
     * Remove Table On Delete Blog Wordpress
     */
    public function remove_table_on_delete_blog($tables): array
    {
        global $wpdb;
        foreach (['camoo_sms_subscribes', 'camoo_sms_subscribes_group', 'camoo_sms_send'] as $tbl) {
            $tables[] = $wpdb->tb_prefix . $tbl;
        }

        return $tables;
    }
}

(new Install());
