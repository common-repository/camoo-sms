<?php

namespace CAMOO_SMS;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Features
{
    public $sms;

    public $date;

    public $options;

    protected $db;

    protected $tb_prefix;

    /** CAMOO_SMS_Features constructor. */
    public function __construct()
    {
        global $oCamooSMS, $wpdb;

        $this->sms = $oCamooSMS;
        $this->db = $wpdb;
        $this->tb_prefix = $wpdb->prefix;
        $this->date = CAMOO_SMS_CURRENT_DATE;
        $this->options = Option::getOptions();

        if (isset($this->options['add_mobile_field'])) {
            add_action('user_new_form', [$this, 'add_mobile_field_to_newuser_form']);
            add_filter('user_contactmethods', [$this, 'add_mobile_field_to_profile_form']);
            add_action('register_form', [$this, 'add_mobile_field_to_register_form']);
            add_filter('registration_errors', [$this, 'registration_errors'], 10, 3);
            add_action('user_register', [$this, 'save_register']);

            add_action('user_register', [$this, 'check_admin_duplicate_number']);
            add_action('profile_update', [$this, 'check_admin_duplicate_number']);
        }
        if (isset($this->options['international_mobile'])) {
            add_action('wp_enqueue_scripts', [$this, 'load_international_input']);
            add_action('admin_enqueue_scripts', [$this, 'load_international_input']);
            add_action('login_enqueue_scripts', [$this, 'load_international_input']);
        }
    }

    public function check_admin_duplicate_number($user_id)
    {
        // Get user mobile
        $user_mobile = get_user_meta($user_id, 'mobile', true);

        if (empty($user_mobile)) {
            return;
        }

        // Delete user mobile
        if ($this->check_mobile_number($user_mobile, $user_id)) {
            $this->delete_user_mobile($user_id);
        }
    }

    public function add_mobile_field_to_newuser_form()
    {
        include_once WP_CAMOO_SMS_DIR . 'includes/templates/mobile-field.php';
    }

    public function add_mobile_field_to_profile_form($fields)
    {
        $fields['mobile'] = __('Mobile', 'wp-camoo-sms');

        return $fields;
    }

    public function add_mobile_field_to_register_form()
    {
        $mobile = (isset($_POST['mobile'])) ? sanitize_text_field($_POST['mobile']) : '';
        include_once WP_CAMOO_SMS_DIR . 'includes/templates/mobile-field-register.php';
    }

    public function registration_errors($errors, $sanitized_user_login, $user_email)
    {
        if (empty($_POST['mobile'])) {
            $errors->add('first_name_error', __('<strong>ERROR</strong>: You must include a mobile number.', 'wp-camoo-sms'));
        }

        if ($this->check_mobile_number(sanitize_text_field($_POST['mobile']))) {
            $errors->add('duplicate_mobile_number', __('<strong>ERROR</strong>: This mobile is already registered, please choose another one.', 'wp-camoo-sms'));
        }

        return $errors;
    }

    public function save_register($user_id)
    {
        if (isset($_POST['mobile'])) {
            update_user_meta($user_id, 'mobile', sanitize_text_field($_POST['mobile']));
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.2.0
     */
    public function load_international_input()
    {
        //Register IntelTelInput Assets
        wp_enqueue_style('wpsms-intel-tel-input', WP_CAMOO_SMS_URL . 'assets/css/intlTelInput.min.css', true, WP_CAMOO_SMS_VERSION);
        wp_enqueue_script('wpsms-intel-tel-input', WP_CAMOO_SMS_URL . 'assets/js/intel/intlTelInput.min.js', ['jquery'], WP_CAMOO_SMS_VERSION, true);
        wp_enqueue_script('wpsms-intel-script', WP_CAMOO_SMS_URL . 'assets/js/intel/intel-script.js', true, WP_CAMOO_SMS_VERSION, true);

        // Localize the IntelTelInput
        $tel_intel_vars = [];
        $only_countries_option = Option::getOption('international_mobile_only_countries');
        $preferred_countries_option = Option::getOption('international_mobile_preferred_countries');

        if ($only_countries_option) {
            $tel_intel_vars['only_countries'] = $only_countries_option;
        } else {
            $tel_intel_vars['only_countries'] = '';
        }

        if ($preferred_countries_option) {
            $tel_intel_vars['preferred_countries'] = $preferred_countries_option;
        } else {
            $tel_intel_vars['preferred_countries'] = '';
        }

        if (Option::getOption('international_mobile_auto_hide')) {
            $tel_intel_vars['auto_hide'] = true;
        } else {
            $tel_intel_vars['auto_hide'] = false;
        }

        if (Option::getOption('international_mobile_national_mode')) {
            $tel_intel_vars['national_mode'] = true;
        } else {
            $tel_intel_vars['national_mode'] = false;
        }

        if (Option::getOption('international_mobile_separate_dial_code')) {
            $tel_intel_vars['separate_dial'] = true;
        } else {
            $tel_intel_vars['separate_dial'] = false;
        }

        $tel_intel_vars['util_js'] = WP_CAMOO_SMS_URL . 'assets/js/intel/utils.js';

        wp_localize_script('wpsms-intel-script', 'wp_camoo_sms_intel_tel_input', $tel_intel_vars);
    }

    /**
     * @param null $user_id
     */
    private function check_mobile_number($mobile_number, $user_id = null): bool
    {
        if ($user_id) {
            $result = $this->db->get_results("SELECT * from `{$this->tb_prefix}usermeta` WHERE meta_key = 'mobile' AND meta_value = '{$mobile_number}' AND user_id != '{$user_id}'");
        } else {
            $result = $this->db->get_results("SELECT * from `{$this->tb_prefix}usermeta` WHERE meta_key = 'mobile' AND meta_value = '{$mobile_number}'");
        }

        return (bool)$result;
    }

    private function delete_user_mobile($user_id)
    {
        $this->db->delete(
            $this->tb_prefix . 'usermeta',
            [
                'user_id' => $user_id,
                'meta_key' => 'mobile',
            ]
        );
    }
}

(new Features());
