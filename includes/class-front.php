<?php

namespace CAMOO_SMS;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Front
{
    /** @var mixed|void */
    private $options;

    public function __construct()
    {
        $this->options = Option::getOptions();

        // Load assets
        add_action('wp_enqueue_scripts', [$this, 'front_assets']);
        add_action('admin_bar_menu', [$this, 'admin_bar']);
    }

    /** Include front table */
    public function front_assets()
    {
        //Register admin-bar.css for whole admin area
        wp_register_style(
            'wpsms-admin-bar',
            WP_CAMOO_SMS_URL . 'assets/css/admin-bar.css',
            true,
            WP_CAMOO_SMS_VERSION
        );
        wp_enqueue_style('wpsms-admin-bar');

        // Check if "Disable Style" in frontend is active or not
        if (empty($this->options['disable_style_in_front']) || (isset($this->options['disable_style_in_front']) &&
                !$this->options['disable_style_in_front'])) {
            wp_register_style(
                'wpsms-subscribe',
                WP_CAMOO_SMS_URL . 'assets/css/subscribe.css',
                true,
                WP_CAMOO_SMS_VERSION
            );
            wp_enqueue_style('wpsms-subscribe');
        }
    }

    /** Admin bar plugin */
    public function admin_bar()
    {
        global $wp_admin_bar;
        if (is_super_admin() && is_admin_bar_showing()) {
            $credit = Option::getOptions('wp_camoo_sms_gateway_credit');
            if ($credit && isset($this->options['account_credit_in_menu']) && !is_object($credit)) {
                $wp_admin_bar->add_menu([
                    'id' => 'wp-credit-sms',
                    'title' => '<span class="ab-icon"></span>' . $credit,
                    'href' => WP_CAMOO_SMS_ADMIN_URL . 'admin.php?page=wp-camoo-sms-settings',
                ]);
            }
        }

        $wp_admin_bar->add_menu([
            'id' => 'wp-send-sms',
            'parent' => 'new-content',
            'title' => __('SMS', 'wp-camoo-sms'),
            'href' => WP_CAMOO_SMS_ADMIN_URL . 'admin.php?page=wp-camoo-sms',
        ]);
    }
}

(new Front());
