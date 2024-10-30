<?php

namespace CAMOO_SMS;

class Welcome
{
    public function __construct()
    {
        // Welcome Hooks
        add_action('admin_menu', [$this, 'menu']);
        add_action('upgrader_process_complete', [$this, 'do_welcome'], 10, 2);
        add_action('admin_init', [$this, 'init']);
    }

    /** Initial */
    public function init()
    {
        if (Option::getOptions('wp_camoo_sms_show_welcome_page') and (strpos($_SERVER['REQUEST_URI'], '/wp-admin/index.php') !== false or strpos($_SERVER['REQUEST_URI'], 'wp-camoo-sms') !== false)) {
            // Disable show welcome page

            update_option('wp_camoo_sms_first_show_welcome_page', true);
            update_option('wp_camoo_sms_show_welcome_page', false);

            // Redirect to welcome page
            wp_redirect('admin.php?page=wp-camoo-sms-welcome');
        }

        if (!Option::getOptions('wp_camoo_sms_first_show_welcome_page')) {
            update_option('wp_camoo_sms_show_welcome_page', true);
        }
    }

    /** Register menu */
    public function menu()
    {
        add_submenu_page(__('CAMOO-SMS Welcome', 'wp-camoo-sms'), __('CAMOO-SMS Welcome', 'wp-camoo-sms'), __('CAMOO-SMS Welcome', 'wp-camoo-sms'), 'administrator', 'wp-camoo-sms-welcome', [$this, 'page_callback']);
    }

    /** Welcome page */
    public static function page_callback()
    {
        include WP_CAMOO_SMS_DIR . 'includes/admin/welcome/welcome.php';
    }

    public function do_welcome($upgrader_object, $options)
    {
        if (isset($options['action']) && $options['action'] == 'update' && isset($options['type']) && $options['type'] == 'plugin' && isset($options['plugins'])) {
            foreach ($options['plugins'] as $each_plugin) {
                if ($each_plugin === \CAMOO_SMS\Config\Bootstrap::PLUGIN_MAIN_FILE) {
                    // Enable welcome page in database
                    update_option('wp_camoo_sms_show_welcome_page', true);
                }
            }
        }
    }

    /** Show change log */
    public static function show_change_log()
    {
        $response = wp_remote_get('https://api.github.com/repos/camoo/wp-camoo-sms/releases/latest');

        // Check response
        if (is_wp_error($response)) {
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code == '200') {
            $data = json_decode($response['body']);

            if (!class_exists('\Parsedown')) {
                include_once WP_CAMOO_SMS_DIR . 'includes/libraries/parsedown.class.php';
            }

            $Parsedown = new \Parsedown();

            echo $Parsedown->text(nl2br($data->body));
        }
    }
}

new Welcome();
