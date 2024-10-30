<?php

namespace CAMOO_SMS;

use CAMOO_SMS\Config\Bootstrap;
use CamooSms\Gateway\Controller\TopupController;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Admin
{
    public $oCamooSMS;

    protected $db;

    protected $tb_prefix;

    protected $options;

    public function __construct()
    {
        global $wpdb;

        $this->db = $wpdb;
        $this->tb_prefix = $wpdb->prefix;
        $this->options = Option::getOptions();
        $this->init();

        // Add Actions
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('admin_bar_menu', [$this, 'admin_bar']);
        add_action('dashboard_glance_items', [$this, 'dashboard_glance']);
        add_action('admin_menu', [$this, 'admin_menu']);

        // Add Filters
        add_filter('plugin_row_meta', [$this, 'meta_links'], 0, 2);
    }

    /** Include admin assets */
    public function admin_assets()
    {
        // Register admin-bar.css for whole admin area
        wp_register_style('wpsms-admin-bar', WP_CAMOO_SMS_URL . 'assets/css/admin-bar.css', true, WP_CAMOO_SMS_VERSION);
        wp_enqueue_style('wpsms-admin-bar');

        if (stristr(get_current_screen()->id, 'wp-camoo-sms')) {
            wp_register_style('wpsms-admin', WP_CAMOO_SMS_URL . 'assets/css/admin.css', true, WP_CAMOO_SMS_VERSION);
            wp_enqueue_style('wpsms-admin');
            if (is_rtl()) {
                wp_enqueue_style('wpsms-rtl', WP_CAMOO_SMS_URL . 'assets/css/rtl.css', true, WP_CAMOO_SMS_VERSION);
            }

            wp_enqueue_style('wpsms-chosen', WP_CAMOO_SMS_URL . 'assets/css/chosen.min.css', true, WP_CAMOO_SMS_VERSION);
            wp_enqueue_script('wpsms-chosen', WP_CAMOO_SMS_URL . 'assets/js/chosen.jquery.min.js', true, WP_CAMOO_SMS_VERSION);
            wp_enqueue_script('wpsms-word-and-character-counter', WP_CAMOO_SMS_URL . 'assets/js/jquery.word-and-character-counter.min.js', true, WP_CAMOO_SMS_VERSION);
            wp_enqueue_script('wpsms-admin', WP_CAMOO_SMS_URL . 'assets/js/admin.js', true, WP_CAMOO_SMS_VERSION);
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
                    'href' => WP_CAMOO_SMS_ADMIN_URL . '/admin.php?page=wp-camoo-sms-settings',
                ]);
            }
        }

        $wp_admin_bar->add_menu([
            'id' => 'wp-send-sms',
            'parent' => 'new-content',
            'title' => __('Camoo SMS', Bootstrap::DOMAIN_TEXT),
            'href' => WP_CAMOO_SMS_ADMIN_URL . '/admin.php?page=wp-camoo-sms',
        ]);
    }

    /** Dashboard glance plugin */
    public function dashboard_glance()
    {
        $subscribe = $this->db->get_var("SELECT COUNT(*) FROM {$this->tb_prefix}camoo_sms_subscribes");
        $credit = Option::getOptions('wp_camoo_sms_gateway_credit');

        echo "<li class='wpsms-subscribe-count'><a href='" . WP_CAMOO_SMS_ADMIN_URL . "admin.php?page=wp-camoo-sms-subscribers'>" . sprintf(__('%s Subscriber', 'wp-camoo-sms'), $subscribe) . '</a></li>';
        if (!is_object($credit)) {
            echo "<li class='wpsms-credit-count'><a href='" . WP_CAMOO_SMS_ADMIN_URL . "admin.php?page=wp-camoo-sms-settings&tab=web-service'>" . sprintf(__('%s SMS Credit', 'wp-camoo-sms'), $credit) . '</a></li>';
        }
    }

    /** Administrator admin_menu */
    public function admin_menu()
    {
        $hook_suffix = [];
        add_menu_page(__('Camoo SMS', 'wp-camoo-sms'), __('Camoo SMS', 'wp-camoo-sms'), 'wpcamoosms_sendsms', 'wp-camoo-sms', [$this, 'send_sms_callback'], 'dashicons-email-alt');
        add_submenu_page('wp-camoo-sms', __('Send SMS', 'wp-camoo-sms'), __('Send SMS', 'wp-camoo-sms'), 'wpcamoosms_sendsms', 'wp-camoo-sms', [$this, 'send_sms_callback']);
        add_submenu_page('wp-camoo-sms', __('Outbox', 'wp-camoo-sms'), __('Outbox', 'wp-camoo-sms'), 'wpcamoosms_outbox', 'wp-camoo-sms-outbox', [$this, 'outbox_callback']);

        $hook_suffix['subscribers'] = add_submenu_page('wp-camoo-sms', __('Subscribers', 'wp-camoo-sms'), __('Subscribers', 'wp-camoo-sms'), 'wpcamoosms_subscribers', 'wp-camoo-sms-subscribers', [$this, 'subscribers_callback']);
        $hook_suffix['groups'] = add_submenu_page('wp-camoo-sms', __('Groups', 'wp-camoo-sms'), __('Groups', 'wp-camoo-sms'), 'wpcamoosms_subscribers', 'wp-camoo-sms-subscribers-group', [$this, 'groups_callback']);

        // Check GDPR compliance for Privacy menu
        if (isset($this->options['gdpr_compliance']) && $this->options['gdpr_compliance'] == 1) {
            $hook_suffix['privacy'] = add_submenu_page('wp-camoo-sms', __('Privacy', 'wp-camoo-sms'), __('Privacy', 'wp-camoo-sms'), 'manage_options', 'wp-camoo-sms-subscribers-privacy', [$this, 'privacy_callback']);
        }

        $hook_suffix['system_info'] = add_submenu_page('wp-camoo-sms', __('System Info', 'wp-camoo-sms'), __('System Info', 'wp-camoo-sms'), 'manage_options', 'wp-camoo-sms-system-info', [$this, 'system_info_callback']);

         add_submenu_page(
            'wp-camoo-sms',
            __('Top up your account', 'wp-camoo-sms'),
            __('Top up your account', 'wp-camoo-sms'),
            'manage_options',
            'wp-camoo-sms-topup',
            [$this, 'topupAccount']
        );
        // Add styles to menu pages
        foreach ($hook_suffix as $menu => $hook) {
            add_action("load-{$hook}", [$this, $menu . '_assets']);
        }
    }


    public function topupAccount(): void
    {
        $controller = new TopupController();
        $controller->render();
    }

    /** Callback send sms page. */
    public function send_sms_callback()
    {
        $page = new SMS_Send();
        $page->render_page();
    }

    /** Callback outbox page. */
    public function outbox_callback()
    {
        $page = new Outbox();
        $page->render_page();
    }

    /** Callback subscribers page. */
    public function subscribers_callback()
    {
        // Subscribers class.
        require_once WP_CAMOO_SMS_DIR . 'includes/admin/subscribers/class-wpsms-subscribers.php';

        $page = new Subscribers();
        $page->render_page();
    }

    /** Callback subscribers page. */
    public function groups_callback()
    {
        // Groups class.
        require_once WP_CAMOO_SMS_DIR . 'includes/admin/groups/class-wpsms-groups.php';

        $page = new Groups();
        $page->render_page();
    }

    /** Callback subscribers page. */
    public function privacy_callback()
    {
        // Privacy class.
        require_once WP_CAMOO_SMS_DIR . 'includes/admin/privacy/class-wpsms-privacy.php';

        $page = new Privacy();
        $page->pagehook = get_current_screen()->id;
        $page->render_page();
    }

    /** System info page. */
    public function system_info_callback()
    {
        $page = new SystemInfo();
        $page->render_page();
    }

    /** Load subscribers page assets */
    public function subscribers_assets()
    {
        wp_register_script('wp-camoo-sms-edit-subscriber', WP_CAMOO_SMS_URL . 'assets/js/edit-subscriber.js', ['jquery'], null, true);
        wp_enqueue_script('wp-camoo-sms-edit-subscriber');

        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

        $tb_show_url = add_query_arg(
            [
                'action' => 'wp_camoo_sms_edit_subscriber',
            ],
            admin_url('admin-ajax.php', $protocol)
        );

        $ajax_vars = [
            'tb_show_url' => $tb_show_url,
            'tb_show_tag' => __('Edit Subscriber', 'wp-camoo-sms'),
        ];
        wp_localize_script('wp-camoo-sms-edit-subscriber', 'wp_camoo_sms_edit_subscribe_ajax_vars', $ajax_vars);
    }

    /** Load groups page assets */
    public function groups_assets()
    {
        wp_register_script('wp-camoo-sms-edit-group', WP_CAMOO_SMS_URL . 'assets/js/edit-group.js', ['jquery'], null, true);
        wp_enqueue_script('wp-camoo-sms-edit-group');

        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

        $tb_show_url = add_query_arg(
            [
                'action' => 'wp_camoo_sms_edit_group',
            ],
            admin_url('admin-ajax.php', $protocol)
        );

        $ajax_vars = [
            'tb_show_url' => $tb_show_url,
            'tb_show_tag' => __('Edit Group', 'wp-camoo-sms'),
        ];
        wp_localize_script('wp-camoo-sms-edit-group', 'wp_camoo_sms_edit_group_ajax_vars', $ajax_vars);
    }

    /** Load privacy page assets */
    public function privacy_assets()
    {
        $pagehook = get_current_screen()->id;

        wp_enqueue_script('common');
        wp_enqueue_script('wp-lists');
        wp_enqueue_script('postbox');

        add_meta_box('privacy-meta-1', esc_html(get_admin_page_title()), [Privacy::class, 'privacy_meta_html_gdpr'], $pagehook, 'side', 'core');
        add_meta_box('privacy-meta-2', __('Export User’s Data related to CAMOO-SMS', 'wp-camoo-sms'), [Privacy::class, 'privacy_meta_html_export'], $pagehook, 'normal', 'core');
        add_meta_box('privacy-meta-3', __('Erase User’s Data related to CAMOO-SMS', 'wp-camoo-sms'), [Privacy::class, 'privacy_meta_html_delete'], $pagehook, 'normal', 'core');
    }

    /** Load system info page assets */
    public function system_info_assets()
    {
        wp_enqueue_style('wpsms-system-info', WP_CAMOO_SMS_URL . 'assets/css/system-info.css', true, WP_CAMOO_SMS_VERSION);
    }

    /**
     * Administrator add Meta Links
     *
     * @return array
     */
    public function meta_links($links, $file)
    {
        //       if ($file === Bootstrap::PLUGIN_MAIN_FILE) {
        //      }

        return $links;
    }

    /** Adding new capability in the plugin */
    public function add_cap()
    {
        // Get administrator role
        $role = get_role('administrator');

        $role->add_cap('wpcamoosms_sendsms');
        $role->add_cap('wpcamoosms_outbox');
        $role->add_cap('wpcamoosms_subscribers');
        $role->add_cap('wpcamoosms_setting');
    }

    /** Admin newsletter */
    public function admin_newsletter()
    {
        #include_once WP_CAMOO_SMS_DIR . 'includes/templates/admin-newsletter.php';
    }

    /** Initial plugin */
    private function init()
    {
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'wpsms-hide-newsletter') {
                update_option('wpcamoosms_hide_newsletter', true);
            }
        }

        if (!Option::getOptions('wpcamoosms_hide_newsletter')) {
            add_action('wp_camoo_sms_settings_page', [$this, 'admin_newsletter']);
        }

        // Check exists require function
        if (!function_exists('wp_get_current_user')) {
            include ABSPATH . 'wp-includes/pluggable.php';
        }

        // Add plugin caps to admin role
        if (is_admin() && is_super_admin()) {
            $this->add_cap();
        }
    }
}

(new Admin());
