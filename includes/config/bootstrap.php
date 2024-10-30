<?php

namespace CAMOO_SMS\Config;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

use CAMOO_SMS\Gateway;

class Bootstrap
{
    public const PLUGIN_MAIN_FILE = 'camoo-sms/camoo-sms.php';

    public const DOMAIN_TEXT = 'wp-camoo-sms';

    public function initialize(): Gateway
    {
        require_once dirname(plugin_dir_path(__FILE__), 2) . '/vendor/autoload.php';

        require_once dirname(plugin_dir_path(__FILE__)) . '/defines.php';
        require_once WP_CAMOO_SMS_DIR . 'includes/class-wpsms-gateway.php';
        require_once WP_CAMOO_SMS_DIR . 'includes/admin/class-wpsms-admin-helper.php';
        require_once WP_CAMOO_SMS_DIR . 'includes/class-wpsms-option.php';
        require WP_CAMOO_SMS_DIR . 'includes/class-wpsms.php';
        add_filter('all_plugins', [$this, 'modify_plugin_description']);
        add_filter(
            'plugin_action_links_' . plugin_basename(self::PLUGIN_MAIN_FILE),
            [$this, 'onPluginActionLinks'],
            1,
            1
        );

        return Gateway::initial();
    }

    public function modify_plugin_description($all_plugins): array
    {
        if (isset($all_plugins[static::PLUGIN_MAIN_FILE])) {
            $all_plugins[static::PLUGIN_MAIN_FILE]['Description'] = sprintf(
                __('With CAMOO SMS, you have the ability to send (Bulk) SMS to a group, to a user, to a number, to members of SMS newsletter or to every events in your site. The usage of this plugin is completely free. You have to just have a CAMOO account. <a target="_blank" href="%s">Sign up</a> for a free account. Ask CAMOO Team for new access_key', 'wp-camoo-sms'),
                WP_CAMOO_SMS_SITE . '/join'
            );
        }

        return $all_plugins;
    }

    public function onPluginActionLinks($links): array
    {
        $settings_link = [
            'settings' => '<a href="' .
                admin_url('admin.php?page=wp-camoo-sms-settings&tab=gateway') .
                '" title="' . __('Settings', self::DOMAIN_TEXT) . '">' . __('Settings', self::DOMAIN_TEXT) . '</a>',
        ];

        return array_merge($settings_link, $links);
    }
}
