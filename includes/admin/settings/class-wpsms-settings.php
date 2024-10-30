<?php

namespace CAMOO_SMS;

if (!defined('ABSPATH')) {
    exit;
} // No direct access allowed ;)
use CAMOO_SMS\Admin\Helper;
use CAMOO_SMS\Config\Bootstrap;

class Settings
{
    public string $settingName;

    public mixed $options = [];

    public function __construct()
    {
        $this->settingName = Option::MAIN_SETTING_KEY;
        $this->get_settings();
        $this->options = Option::getOptions($this->settingName);

        if (empty($this->options)) {
            update_option($this->settingName, []);
        }

        add_action('admin_menu', [$this, 'add_settings_menu'], 11);

        if (isset($_POST['_wp_http_referer'])) {
            add_filter('wp_camoo_sms_settings_sanitize', [Helper::class, 'sanitizer'], 10, 2);
            add_filter('wp_camoo_sms_settings_beforesave', [Option::class, 'beforeSave']);
        }

        if (isset($_GET['page']) && $_GET['page'] == 'wp-camoo-sms-settings' || isset($_POST['option_page']) &&
            $_POST['option_page'] == 'wp_camoo_sms_settings') {
            add_action('admin_init', [$this, 'register_settings']);
        }
    }

    /** Add WP SMS Professional Package admin page settings */
    public function add_settings_menu()
    {
        add_submenu_page(
            'wp-camoo-sms',
            __('Settings', 'wp-camoo-sms'),
            __('Settings', 'wp-camoo-sms'),
            'wpcamoosms_setting',
            'wp-camoo-sms-settings',
            [
                $this,
                'render_settings',
            ]
        );
    }

    /**
     * Gets saved settings from WP core
     *
     * @since           2.0
     */
    public function get_settings()
    {
        $settings = Option::getOptions($this->settingName);
        if (!$settings) {
            update_option($this->settingName, [
                'rest_api_status' => 1,
            ]);
        }

        return apply_filters('wp_camoo_sms_get_settings', $settings);
    }

    /**
     * Registers settings in WP core
     *
     * @return void
     *
     * @since           2.0
     */
    public function register_settings()
    {
        if (false === Option::getOptions($this->settingName)) {
            add_option($this->settingName);
        }

        foreach ($this->get_registered_settings() as $tab => $settings) {
            add_settings_section(
                'wp_camoo_sms_settings_' . $tab,
                __return_null(),
                '__return_false',
                'wp_camoo_sms_settings_' . $tab
            );

            if (empty($settings)) {
                return;
            }

            foreach ($settings as $option) {
                $name = $option['name'] ?? '';

                add_settings_field(
                    'wp_camoo_sms_settings[' . $option['id'] . ']',
                    $name,
                    [$this, $option['type'] . '_callback'],
                    'wp_camoo_sms_settings_' . $tab,
                    'wp_camoo_sms_settings_' . $tab,
                    [
                        'id' => $option['id'] ?? null,
                        'desc' => !empty($option['desc']) ? $option['desc'] : '',
                        'name' => $option['name'] ?? null,
                        'section' => $tab,
                        'size' => $option['size'] ?? null,
                        'options' => $option['options'] ?? '',
                        'std' => $option['std'] ?? '',
                    ]
                );
                register_setting($this->settingName, $this->settingName, [$this, 'settings_sanitize']);
            }
        }
    }

    /**
     * Gets settings tabs
     *
     * @return array Tabs list
     *
     * @since               2.0
     */
    public function get_tabs(): array
    {
        return [
            'general' => __('General', 'wp-camoo-sms'),
            'gateway' => __('Gateway', 'wp-camoo-sms'),
            'newsletter' => __('SMS Newsletter', 'wp-camoo-sms'),
            'feature' => __('Features', 'wp-camoo-sms'),
            'notifications' => __('Notifications', 'wp-camoo-sms'),
            'integration' => __('Integration', 'wp-camoo-sms'),
        ];
    }

    /**
     * Sanitizes and saves settings after submit
     *
     * @param array $input Settings input
     *
     * @return array New settings
     *
     * @since               2.0
     */
    public function settings_sanitize(array $input = []): array
    {
        if (empty($_POST['_wp_http_referer'])) {
            return $input;
        }

        parse_str($_POST['_wp_http_referer'], $referrer);

        $settings = $this->get_registered_settings();

        $tab = $referrer['tab'] ?? 'wp';

        $input = $input ?: [];
        $input = apply_filters('wp_camoo_sms_settings_' . $tab . '_sanitize', $input);

        // Loop through each setting being saved and pass it through a sanitization filter
        foreach ($input as $key => $value) {
            // Get the setting type (checkbox, select, etc)
            $type = $settings[$tab][$key]['type'] ?? null;

            // General filter
            $input[$key] = apply_filters('wp_camoo_sms_settings_sanitize', $value, $type);

            if (in_array($key, ['bulk_chunk', 'bulk_threshold'])) {
                if (!Gateway::can_bulk_send()) {
                    unset($input[$key]);
                } elseif ((int)$value > 50 || empty($value)) {
                    $input[$key] = apply_filters('wp_camoo_sms_settings_sanitize', 50, 'number');
                }
            }
        }

        // Loop through the whitelist and unset any that are empty for the tab being saved
        if (!empty($settings[$tab])) {
            foreach ($settings[$tab] as $key => $value) {
                // settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
                if (is_numeric($key)) {
                    $key = $value['id'];
                }

                if (empty($input[$key])) {
                    unset($this->options[$key]);
                }
            }
        }
        // Merge our new settings with the existing
        $output = apply_filters('wp_camoo_sms_settings_beforesave', array_merge($this->options, $input));

        add_settings_error('camoo-sms-notices', '', __('Settings updated', 'wp-camoo-sms'), 'updated');

        return $output;
    }

    /**
     * Get settings fields
     *
     * @return array Fields
     *
     * @since           2.0
     */
    public function get_registered_settings()
    {
        $options = [
            'enable' => __('Enable', 'wp-camoo-sms'),
            'disable' => __('Disable', 'wp-camoo-sms'),
        ];

        $settings = apply_filters('wp_camoo_sms_registered_settings', [
            // General tab
            'general' => apply_filters('wp_camoo_sms_general_settings', [
                'admin_title' => [
                    'id' => 'admin_title',
                    'name' => __('Mobile', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'admin_mobile_number' => [
                    'id' => 'admin_mobile_number',
                    'name' => __('Admin mobile number', 'wp-camoo-sms'),
                    'type' => 'text',
                    'desc' => __('Admin mobile number for get any sms notifications. eg: 671234568', 'wp-camoo-sms'),
                ],
                'mobile_county_code' => [
                    'id' => 'mobile_county_code',
                    'name' => __('Mobile country code', 'wp-camoo-sms'),
                    'type' => 'text',
                    'desc' => __('Enter your mobile country code for prefix numbers. For example if you enter +237 The final number will be +237671234568', 'wp-camoo-sms'),
                ],
                'admin_title_privacy' => [
                    'id' => 'admin_title_privacy',
                    'name' => __('Privacy', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'gdpr_compliance' => [
                    'id' => 'gdpr_compliance',
                    'name' => __('GDPR Enhancements', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Enable GDPR related features in this page. Read our GDPR documentation to learn more.', 'wp-camoo-sms'),
                ],
            ]),

            // Gateway tab
            'gateway' => apply_filters('wp_camoo_sms_gateway_settings', [
                // Gateway
                'gayeway_title' => [
                    'id' => 'gayeway_title',
                    'name' => __('Gateway information', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'gateway_name' => [
                    'id' => 'gateway_name',
                    'name' => __('Gateway name', 'wp-camoo-sms'),
                    'type' => 'advancedselect',
                    'options' => Gateway::gateway(),
                    'desc' => __('Please select your gateway.', 'wp-camoo-sms'),
                ],
                'gateway_help' => [
                    'id' => 'gateway_help',
                    'name' => __('Gateway description', 'wp-camoo-sms'),
                    'type' => 'html',
                    'options' => Gateway::help(),
                ],
                'gateway_username' => [
                    'id' => 'gateway_username',
                    'name' => __('API Key', 'wp-camoo-sms'),
                    'type' => 'text',
                    'desc' => __('Enter API KEY for camoo gateway', 'wp-camoo-sms'),
                ],
                'gateway_password' => [
                    'id' => 'gateway_password',
                    'name' => __('API Secret', 'wp-camoo-sms'),
                    'type' => 'password',
                    'desc' => __('Enter API Secret key for camoo gateway', 'wp-camoo-sms'),
                ],
                'gateway_sender_id' => [
                    'id' => 'gateway_sender_id',
                    'name' => __('Sender number', 'wp-camoo-sms'),
                    'type' => 'text',
                    'std' => Gateway::from(),
                    'desc' => __('Sender number or sender ID', 'wp-camoo-sms'),
                ],
                'encrypt_sms' => [
                    'id' => 'encrypt_sms',
                    'name' => __('Encrypt SMS', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => ['class' => 'encrypt-sms', 'disabled' => Helper::getPhpVersion() < CAMOO_SMS_MIN_PHP_VERSION || (!empty($this->options['gateway_name']) && $this->options['gateway_name'] !== 'camoo')],
                    'desc' => __('Encrypt  SMS to ensure an end to end encryption between your server and the Camoo\'s server', 'wp-camoo-sms'),
                ],
                // Gateway status
                'gateway_status_title' => [
                    'id' => 'gateway_status_title',
                    'name' => __('Gateway status', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'account_credit' => [
                    'id' => 'account_credit',
                    'name' => __('Status', 'wp-camoo-sms'),
                    'type' => 'html',
                    'options' => Gateway::status(),
                ],
                'account_response' => [
                    'id' => 'account_response',
                    'name' => __('Result request', 'wp-camoo-sms'),
                    'type' => 'html',
                    'options' => Gateway::response(),
                ],
                'bulk_send' => [
                    'id' => 'bulk_send',
                    'name' => __('Bulk send', 'wp-camoo-sms'),
                    'type' => 'html',
                    'options' => Gateway::bulk_status(),
                ],
                'bulk_chunk' => [
                    'id' => 'bulk_chunk',
                    'name' => __('Bulk Chunk', 'wp-camoo-sms'),
                    'type' => 'number',
                    'std' => 50,
                    'options' => ['disabled' => !Gateway::can_bulk_send()],
                    'desc' => __('When sending bulk SMS in the background, how many SMS chunk should be sent for each loop request? Max: 50', 'wp-camoo-sms'),
                ],
                'bulk_threshold' => [
                    'id' => 'bulk_threshold',
                    'name' => __('Bulk Threshold', 'wp-camoo-sms'),
                    'type' => 'number',
                    'std' => 50,
                    'options' => ['disabled' => !Gateway::can_bulk_send()],
                    'desc' => __('Bulk Threshold triggers sending bulk SMS in the background. Max: 50', 'wp-camoo-sms'),
                ],

                // Account credit
                'account_credit_title' => [
                    'id' => 'account_credit_title',
                    'name' => __('Account balance', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'account_credit_in_menu' => [
                    'id' => 'account_credit_in_menu',
                    'name' => __('Show in admin menu', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Show your account credit in admin menu.', 'wp-camoo-sms'),
                ],
                'account_credit_in_sendsms' => [
                    'id' => 'account_credit_in_sendsms',
                    'name' => __('Show in send SMS page', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Show your account credit in send SMS page.', 'wp-camoo-sms'),
                ],
                // Message header
                'message_title' => [
                    'id' => 'message_title',
                    'name' => __('Message options', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'send_unicode' => [
                    'id' => 'send_unicode',
                    'name' => __('Send as Unicode', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('You can send SMS messages using Unicode for non-English characters (such as Persian, Arabic, Chinese, french or other cyrillic characters).', 'wp-camoo-sms'),
                ],
            ]),

            // SMS Newsletter tab
            'newsletter' => apply_filters('wp_camoo_sms_gateway_settings', [
                // SMS Newsletter
                'newsletter_title' => [
                    'id' => 'newsletter_title',
                    'name' => __('SMS Newsletter', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'newsletter_form_groups' => [
                    'id' => 'newsletter_form_groups',
                    'name' => __('Show Groups', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'desc' => __('Enable showing Groups on Form.', 'wp-camoo-sms'),
                ],
                'newsletter_form_verify' => [
                    'id' => 'newsletter_form_verify',
                    'name' => __('Verify Subscriber', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'desc' => __('Verified subscribe with the activation code', 'wp-camoo-sms'),
                ],
                'welcome' => [
                    'id' => 'welcome',
                    'name' => __('Welcome SMS', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'newsletter_form_welcome' => [
                    'id' => 'newsletter_form_welcome',
                    'name' => __('Status', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'desc' => __('Enable or Disable welcome SMS.', 'wp-camoo-sms'),
                ],
                'newsletter_form_welcome_text' => [
                    'id' => 'newsletter_form_welcome_text',
                    'name' => __('SMS text', 'wp-camoo-sms'),
                    'type' => 'textarea',
                    'desc' => sprintf(__('Subscribe name: %s, Subscribe mobile: %s', 'wp-camoo-sms'), '<code>%subscribe_name%</code>', '<code>%subscribe_mobile%</code>'),
                ],
                'mobile_terms' => [
                    'id' => 'mobile_terms',
                    'name' => __('Mobile Number Terms', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'mobile_terms_field_place_holder' => [
                    'id' => 'mobile_terms_field_place_holder',
                    'name' => __('Field Placeholder', 'wp-camoo-sms'),
                    'type' => 'text',
                ],
                'mobile_terms_minimum' => [
                    'id' => 'mobile_terms_minimum',
                    'name' => __('Minimum number', 'wp-camoo-sms'),
                    'type' => 'number',
                ],
                'mobile_terms_maximum' => [
                    'id' => 'mobile_terms_maximum',
                    'name' => __('Maximum number', 'wp-camoo-sms'),
                    'type' => 'number',
                ],
                //Style Setting
                'style' => [
                    'id' => 'style',
                    'name' => __('Style', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'disable_style_in_front' => [
                    'id' => 'disable_style_in_front',
                    'name' => __('Disable Frontend Style', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'desc' => __('Disable loading Style from Frontend.', 'wp-camoo-sms'),
                ],
            ]),
            // Feature tab
            'feature' => apply_filters('wp_camoo_sms_feature_settings', [
                'mobile_field' => [
                    'id' => 'mobile_field',
                    'name' => __('Mobile field', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'add_mobile_field' => [
                    'id' => 'add_mobile_field',
                    'name' => __('Add Mobile number field', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Add Mobile number to user profile and register form.', 'wp-camoo-sms'),
                ],
                'international_mobile_title' => [
                    'id' => 'international_mobile_title',
                    'name' => __('International Telephone Input', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'international_mobile' => [
                    'id' => 'international_mobile',
                    'name' => __('Enable for mobile fields', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Make mobile input fields in whole plugin to intel tel input.', 'wp-camoo-sms'),
                ],
                'international_mobile_only_countries' => [
                    'id' => 'international_mobile_only_countries',
                    'name' => __('Only Countries', 'wp-camoo-sms'),
                    'type' => 'countryselect',
                    'options' => $this->get_countries_list(),
                    'desc' => __('In the dropdown, display only the countries you specify.', 'wp-camoo-sms'),
                ],
                'international_mobile_preferred_countries' => [
                    'id' => 'international_mobile_preferred_countries',
                    'name' => __('Preferred Countries', 'wp-camoo-sms'),
                    'type' => 'countryselect',
                    'options' => $this->get_countries_list(),
                    'desc' => __('Specify the countries to appear at the top of the list.', 'wp-camoo-sms'),
                ],
                'international_mobile_auto_hide' => [
                    'id' => 'international_mobile_auto_hide',
                    'name' => __('Auto hide dial code', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('If there is just a dial code in the input: remove it on blur or submit, and re-add it on focus.<br>Requires National mode to be deactivate', 'wp-camoo-sms'),
                ],
                'international_mobile_national_mode' => [
                    'id' => 'international_mobile_national_mode',
                    'name' => __('National mode', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Allow users to enter national numbers (and not have to think about international dial codes).', 'wp-camoo-sms'),
                ],
                'international_mobile_separate_dial_code' => [
                    'id' => 'international_mobile_separate_dial_code',
                    'name' => __('Separate dial code', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Display the country dial code next to the selected flag so it\'s not part of the typed number.<br>Note: this will disable National mode because technically we are dealing with international numbers, but with the dial code separated.', 'wp-camoo-sms'),
                ],
                'rest_api' => [
                    'id' => 'rest_api',
                    'name' => __('REST API', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'rest_api_status' => [
                    'id' => 'rest_api_status',
                    'name' => __('REST API status', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Add CAMOO-SMS endpoints to the WP Rest API', 'wp-camoo-sms'),
                ],
            ]),
            // Notifications tab
            'notifications' => apply_filters('wp_camoo_sms_notifications_settings', [
                // Publish new post
                'notif_publish_new_post_title' => [
                    'id' => 'notif_publish_new_post_title',
                    'name' => __('Published new posts', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'notif_publish_new_post' => [
                    'id' => 'notif_publish_new_post',
                    'name' => __('Status', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Send an SMS to subscribers When published new posts.', 'wp-camoo-sms'),
                ],
                'notif_publish_new_post_template' => [
                    'id' => 'notif_publish_new_post_template',
                    'name' => __('Message body', 'wp-camoo-sms'),
                    'type' => 'textarea',
                    'desc' => __('Enter the contents of the sms message.', 'wp-camoo-sms') . '<br>' .
                        sprintf(
                            __('Post title: %s, Post content: %s, Post url: %s, Post date: %s', 'wp-camoo-sms'),
                            '<code>%post_title%</code>',
                            '<code>%post_content%</code>',
                            '<code>%post_url%</code>',
                            '<code>%post_date%</code>'
                        ),
                ],
                // Publish new post
                'notif_publish_new_post_author_title' => [
                    'id' => 'notif_publish_new_post_author_title',
                    'name' => __('Author of the post', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'notif_publish_new_post_author' => [
                    'id' => 'notif_publish_new_post_author',
                    'name' => __('Status', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Send an SMS to the author of the post when that post publish.<br>Make sure "Add Mobile number field" enabled in "Features" settings.', 'wp-camoo-sms'),
                ],
                'notif_publish_new_post_author_post_type' => [
                    'id' => 'notif_publish_new_post_author_post_type',
                    'name' => __('Post Types', 'wp-camoo-sms'),
                    'type' => 'multiselect',
                    'options' => $this->get_list_post_type(['show_ui' => 1]),
                    'desc' => __('Select post types that you want to use this option.', 'wp-camoo-sms'),
                ],
                'notif_publish_new_post_author_template' => [
                    'id' => 'notif_publish_new_post_author_template',
                    'name' => __('Message body', 'wp-camoo-sms'),
                    'type' => 'textarea',
                    'desc' => __('Enter the contents of the sms message.', 'wp-camoo-sms') . '<br>' .
                        sprintf(
                            __('Post title: %s, Post content: %s, Post url: %s, Post date: %s', 'wp-camoo-sms'),
                            '<code>%post_title%</code>',
                            '<code>%post_content%</code>',
                            '<code>%post_url%</code>',
                            '<code>%post_date%</code>'
                        ),
                ],
                // Publish new wp version
                'notif_publish_new_wpversion_title' => [
                    'id' => 'notif_publish_new_wpversion_title',
                    'name' => __('The new release of WordPress', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'notif_publish_new_wpversion' => [
                    'id' => 'notif_publish_new_wpversion',
                    'name' => __('Status', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Send an SMS to you When the new release of WordPress.', 'wp-camoo-sms'),
                ],
                // Register new user
                'notif_register_new_user_title' => [
                    'id' => 'notif_register_new_user_title',
                    'name' => __('Register a new user', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'notif_register_new_user' => [
                    'id' => 'notif_register_new_user',
                    'name' => __('Status', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Send an SMS to you and user when register on wordpress.', 'wp-camoo-sms'),
                ],
                'notif_register_new_user_admin_template' => [
                    'id' => 'notif_register_new_user_admin_template',
                    'name' => __('Message body for admin', 'wp-camoo-sms'),
                    'type' => 'textarea',
                    'desc' => __('Enter the contents of the sms message.', 'wp-camoo-sms') . '<br>' .
                        sprintf(
                            __('User login: %s, User email: %s, Register date: %s', 'wp-camoo-sms'),
                            '<code>%user_login%</code>',
                            '<code>%user_email%</code>',
                            '<code>%date_register%</code>'
                        ),
                ],
                'notif_register_new_user_template' => [
                    'id' => 'notif_register_new_user_template',
                    'name' => __('Message body for user', 'wp-camoo-sms'),
                    'type' => 'textarea',
                    'desc' => __('Enter the contents of the sms message.', 'wp-camoo-sms') . '<br>' .
                        sprintf(
                            __('User login: %s, User email: %s, Register date: %s', 'wp-camoo-sms'),
                            '<code>%user_login%</code>',
                            '<code>%user_email%</code>',
                            '<code>%date_register%</code>'
                        ),
                ],
                // New comment
                'notif_new_comment_title' => [
                    'id' => 'notif_new_comment_title',
                    'name' => __('New comment', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'notif_new_comment' => [
                    'id' => 'notif_new_comment',
                    'name' => __('Status', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Send an SMS to you When get a new comment.', 'wp-camoo-sms'),
                ],
                'notif_new_comment_template' => [
                    'id' => 'notif_new_comment_template',
                    'name' => __('Message body', 'wp-camoo-sms'),
                    'type' => 'textarea',
                    'desc' => __('Enter the contents of the sms message.', 'wp-camoo-sms') . '<br>' .
                        sprintf(
                            __('Comment author: %s, Author email: %s, Author url: %s, Author IP: %s, Comment date: %s, Comment content: %s', 'wp-camoo-sms'),
                            '<code>%comment_author%</code>',
                            '<code>%comment_author_email%</code>',
                            '<code>%comment_author_url%</code>',
                            '<code>%comment_author_IP%</code>',
                            '<code>%comment_date%</code>',
                            '<code>%comment_content%</code>'
                        ),
                ],
                // User login
                'notif_user_login_title' => [
                    'id' => 'notif_user_login_title',
                    'name' => __('User login', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'notif_user_login' => [
                    'id' => 'notif_user_login',
                    'name' => __('Status', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Send an SMS to you When user is login.', 'wp-camoo-sms'),
                ],
                'notif_user_login_template' => [
                    'id' => 'notif_user_login_template',
                    'name' => __('Message body', 'wp-camoo-sms'),
                    'type' => 'textarea',
                    'desc' => __('Enter the contents of the sms message.', 'wp-camoo-sms') . '<br>' .
                        sprintf(
                            __('Username: %s, Nickname: %s', 'wp-camoo-sms'),
                            '<code>%username_login%</code>',
                            '<code>%display_name%</code>'
                        ),
                ],
            ]),
            // Integration  tab
            'integration' => apply_filters('wp_camoo_sms_integration_settings', [
                // Contact form 7
                'cf7_title' => [
                    'id' => 'cf7_title',
                    'name' => __('Contact Form 7', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'cf7_metabox' => [
                    'id' => 'cf7_metabox',
                    'name' => __('SMS meta box', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Added Wordpress SMS meta box to Contact form 7 plugin when enable this option.', 'wp-camoo-sms'),
                ],
                // Woocommerce
                'wc_title' => [
                    'id' => 'wc_title',
                    'name' => __('WooCommerce', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'wc_notif_new_order' => [
                    'id' => 'wc_notif_new_order',
                    'name' => __('New order', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Send an SMS to you When get new order.', 'wp-camoo-sms'),
                ],
                'wc_notif_new_order_template' => [
                    'id' => 'wc_notif_new_order_template',
                    'name' => __('Message body', 'wp-camoo-sms'),
                    'type' => 'textarea',
                    'desc' => __('Enter the contents of the sms message.', 'wp-camoo-sms') . '<br>' .
                        sprintf(
                            __('Order ID: %s, Order status: %s', 'wp-camoo-sms'),
                            '<code>%order_id%</code>',
                            '<code>%status%</code>'
                        ),
                ],
                // EDD
                'edd_title' => [
                    'id' => 'edd_title',
                    'name' => __('Easy Digital Downloads', 'wp-camoo-sms'),
                    'type' => 'header',
                ],
                'edd_notif_new_order' => [
                    'id' => 'edd_notif_new_order',
                    'name' => __('New order', 'wp-camoo-sms'),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Send an SMS to you When get new order.', 'wp-camoo-sms'),
                ],
                'edd_notif_new_order_template' => [
                    'id' => 'edd_notif_new_order_template',
                    'name' => __('Message body', 'wp-camoo-sms'),
                    'type' => 'textarea',
                    'desc' => __('Enter the contents of the message.', 'wp-telegram-notifications') . '<br>' .
                        sprintf(
                            __('Customer email: %s, Customer name: %s, Customer last name: %s', 'wp-telegram-notifications'),
                            '<code>%edd_email%</code>',
                            '<code>%edd_first%</code>',
                            '<code>%edd_last%</code>'
                        ),
                ],

                // Inform customer WooCommerce
                'wc_inform_user_title' => [
                    'id' => 'wc_inform_user_title',
                    'name' => __('Woocommerce Status changed', Bootstrap::DOMAIN_TEXT),
                    'type' => 'header',
                ],
                'wc_notify_status_changed' => [
                    'id' => 'wc_notify_status_changed',
                    'name' => __('On Status Changed', Bootstrap::DOMAIN_TEXT),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Send an SMS to buyer after a specific Order status', Bootstrap::DOMAIN_TEXT),
                ],

                'wc_notify_status_type' => [
                    'id' => 'wc_notify_status_type',
                    'name' => __('Status', Bootstrap::DOMAIN_TEXT),
                    'type' => 'multiselect',
                    'options' => [
                        ['pending' => __('Pending', Bootstrap::DOMAIN_TEXT)],
                        ['failed' => __('Failed', Bootstrap::DOMAIN_TEXT)],
                        ['on-hold' => __('On-Hold', Bootstrap::DOMAIN_TEXT)],
                        ['processing' => __('Processing', Bootstrap::DOMAIN_TEXT)],
                        ['completed' => __('Completed', Bootstrap::DOMAIN_TEXT)],
                        ['refunded' => __('Refunded', Bootstrap::DOMAIN_TEXT)],
                        ['cancelled' => __('Cancelled', Bootstrap::DOMAIN_TEXT)],
                    ],
                    'desc' => __(
                        'Send an SMS to customer when the status changed to one of your selection',
                        Bootstrap::DOMAIN_TEXT
                    ),
                ],
                'wc_notify_status_changed_template' => [
                    'id' => 'wc_notify_status_changed_template',
                    'name' => __('Message body', Bootstrap::DOMAIN_TEXT),
                    'type' => 'textarea',
                    'desc' => __('Enter the contents of the message.', Bootstrap::DOMAIN_TEXT) . '<br>' .
                        sprintf(
                            __(
                                'Woocommerce Order ID: %s, New status: %s, Old Status: %s, Amount: %s, First Name: %s, Last Name: %s',
                                Bootstrap::DOMAIN_TEXT
                            ),
                            '<code>%order_id%</code>',
                            '<code>%new_status%</code>',
                            '<code>%old_status%</code>',
                            '<code>%amount%</code>',
                            '<code>%first_name%</code>',
                            '<code>%last_name%</code>'
                        ),
                ],

                // Smobilpay
                'enkap_title' => [
                    'id' => 'enkap_title',
                    'name' => __('SmobilPay for e-commerce', Bootstrap::DOMAIN_TEXT),
                    'type' => 'header',
                ],
                'enkap_notify_status_changed' => [
                    'id' => 'enkap_notify_status_changed',
                    'name' => __('On Status Changed', Bootstrap::DOMAIN_TEXT),
                    'type' => 'checkbox',
                    'options' => $options,
                    'desc' => __('Send an SMS to customer with a specific payment status', Bootstrap::DOMAIN_TEXT),
                ],
                'enkap_notify_new_status' => [
                    'id' => 'enkap_notify_new_status',
                    'name' => __('Status', Bootstrap::DOMAIN_TEXT),
                    'type' => 'select',
                    'options' => [
                        'CREATED' => __('Created', Bootstrap::DOMAIN_TEXT),
                        'INITIALISED' => __('Initialised', Bootstrap::DOMAIN_TEXT),
                        'IN_PROGRESS' => __('In Progress', Bootstrap::DOMAIN_TEXT),
                        'CONFIRMED' => __('Confirmed', Bootstrap::DOMAIN_TEXT),
                        'FAILED' => __('Failed', Bootstrap::DOMAIN_TEXT),
                        'CANCELED' => __('Cancelled', Bootstrap::DOMAIN_TEXT),
                    ],
                    'desc' => __('Send an SMS to customer when the status changes to.', Bootstrap::DOMAIN_TEXT),
                ],
                'enkap_notify_shop_type' => [
                    'id' => 'enkap_notify_shop_type',
                    'name' => __('Shop Types', Bootstrap::DOMAIN_TEXT),
                    'type' => 'multiselect',
                    'options' => [['wc' => 'WooCommerce'], ['edd' => 'Easy Digital Downloads']],
                    'desc' => __('Select Shop types that you want to use this option.', Bootstrap::DOMAIN_TEXT),
                ],
                'enkap_notify_status_changed_template' => [
                    'id' => 'enkap_notify_status_changed_template',
                    'name' => __('Message body', Bootstrap::DOMAIN_TEXT),
                    'type' => 'textarea',
                    'desc' => __('Enter the contents of the message.', Bootstrap::DOMAIN_TEXT) . '<br>' .
                        sprintf(
                            __(
                                'Smobilpay Order transaction: %s, Merchant Reference: %s, Status: %s, Amount: %s, First Name: %s, Last Name: %s',
                                Bootstrap::DOMAIN_TEXT
                            ),
                            '<code>%order_transaction_id%</code>',
                            '<code>%merchant_reference_id%</code>',
                            '<code>%status%</code>',
                            '<code>%amount%</code>',
                            '<code>%first_name%</code>',
                            '<code>%last_name%</code>'
                        ),
                ],

            ]),
        ]);

        // Check the GDPR is enabled.
        if (Option::getOption('gdpr_compliance')) {
            $settings['newsletter']['newsletter_gdpr'] = [
                'id' => 'newsletter_gdpr',
                'name' => __('GDPR Compliance', 'wp-camoo-sms'),
                'type' => 'header',
            ];

            $settings['newsletter']['newsletter_form_gdpr_text'] = [
                'id' => 'newsletter_form_gdpr_text',
                'name' => __('Confirmation text', 'wp-camoo-sms'),
                'type' => 'textarea',
            ];

            $settings['newsletter']['newsletter_form_gdpr_confirm_checkbox'] = [
                'id' => 'newsletter_form_gdpr_confirm_checkbox',
                'name' => __('Confirmation Checkbox status', 'wp-camoo-sms'),
                'type' => 'select',
                'options' => ['checked' => 'Checked', 'unchecked' => 'Unchecked'],
                'desc' => __('Checked or Unchecked GDPR checkbox as default form load.', 'wp-camoo-sms'),
            ];
        } else {
            $settings['newsletter']['newsletter_gdpr'] = [
                'id' => 'gdpr_notify',
                'name' => __('GDPR Compliance', 'wp-camoo-sms'),
                'type' => 'notice',
                'desc' => __('To get more option for GDPR, you should enable that in the general tab.', 'wp-camoo-sms'),
            ];
        }

        return $settings;
    }

    public function header_callback($args)
    {
        echo '<hr/>';
    }

    public function html_callback($args)
    {
        echo $args['options'];
    }

    public function notice_callback($args)
    {
        echo $args['desc'];
    }

    public function checkbox_callback($args)
    {
        $checked = isset($this->options[$args['id']]) ? checked(1, $this->options[$args['id']], false) : '';
        $html = '<input type="checkbox" id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . ']" value="1" ' . $checked;
        if (is_array($args['options']) && array_key_exists('disabled', $args['options']) && $args['options']['disabled'] === true) {
            $html .= ' disabled';
        }
        $html .= ' />';
        $html .= '<label for="wp_camoo_sms_settings[' . $args['id'] . ']"> ' . __('Active', 'wp-camoo-sms') . '</label>';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function multicheck_callback($args)
    {
        $html = '';
        foreach ($args['options'] as $key => $value) {
            $option_name = $args['id'] . '-' . $key;
            $this->checkbox_callback([
                'id' => $option_name,
                'desc' => $value,
            ]);
            echo '<br>';
        }

        echo $html;
    }

    public function radio_callback($args)
    {
        foreach ($args['options'] as $key => $option) {
            $checked = false;

            if (isset($this->options[$args['id']]) && $this->options[$args['id']] == $key) {
                $checked = true;
            } elseif (isset($args['std']) && $args['std'] == $key && !isset($this->options[$args['id']])) {
                $checked = true;
            }

            echo '<input name="wp_camoo_sms_settings[' . $args['id'] . ']"" id="wp_camoo_sms_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked(true, $checked, false) . '/>';
            echo '<label for="wp_camoo_sms_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label>&nbsp;&nbsp;';
        }

        echo '<p class="description">' . $args['desc'] . '</p>';
    }

    public function text_callback($args)
    {
        if (isset($this->options[$args['id']]) && $this->options[$args['id']]) {
            $value = $this->options[$args['id']];
        } else {
            $value = $args['std'] ?? '';
        }

        $disabled = is_array($args['options']) && array_key_exists('disabled', $args['options']) && $args['options']['disabled'] === true ? ' disabled' : '';

        $size = (isset($args['size']) && !is_null($args['size'])) ? $args['size'] : 'regular';
        $html = '<input type="text" class="' . $size . '-text" id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . ']" value="' . esc_attr(stripslashes($value)) . '"' . $disabled . '/>';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function number_callback($args)
    {
        $value = $this->options[$args['id']] ?? $args['std'] ?? '';
        $disabled = is_array($args['options']) && array_key_exists('disabled', $args['options']) && $args['options']['disabled'] === true ? ' disabled' : '';

        $max = $args['max'] ?? 999999;
        $min = $args['min'] ?? 0;
        $step = $args['step'] ?? 1;

        $size = (isset($args['size']) && !is_null($args['size'])) ? $args['size'] : 'regular';
        $html = '<input type="number" step="' . esc_attr($step) . '" max="' . esc_attr($max) . '" min="' . esc_attr($min) . '" class="' . $size . '-text" id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . ']" value="' . esc_attr(stripslashes($value)) . '"' . $disabled . '/>';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function textarea_callback($args)
    {
        $value = $this->options[$args['id']] ?? $args['std'] ?? '';

        $html = '<textarea class="large-text" cols="50" rows="5" id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . ']">' . esc_textarea(stripslashes($value)) . '</textarea>';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function password_callback($args)
    {
        $value = $this->options[$args['id']] ?? $args['std'] ?? '';

        $size = (isset($args['size']) && !is_null($args['size'])) ? $args['size'] : 'regular';
        $html = '<input type="password" class="' . $size . '-text" id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . ']" value="' . esc_attr($value) . '"/>';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function missing_callback($args)
    {
        echo '&ndash;';

        return false;
    }

    public function select_callback($args)
    {
        $value = $this->options[$args['id']] ?? $args['std'] ?? '';

        $html = '<select id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . ']"/>';

        foreach ($args['options'] as $option => $name) {
            $selected = selected($option, $value, false);
            $html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
        }

        $html .= '</select>';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function multiselect_callback($args)
    {
        $value = $this->options[$args['id']] ?? $args['std'] ?? '';

        $html = '<select id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . '][]" multiple="true" class="chosen-select camoo-sms-et"/>';
        $selected = '';

        foreach ($args['options'] as $name) {
            foreach ($name as $option => $name) {
                if (isset($value) && is_array($value)) {
                    if (in_array($option, $value)) {
                        $selected = " selected='selected'";
                    } else {
                        $selected = '';
                    }
                }
                $html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
            }
        }

        $html .= '</select>';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function countryselect_callback($args)
    {
        $value = $this->options[$args['id']] ?? $args['std'] ?? '';

        $html = '<select id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . '][]" multiple="true" class="chosen-select"/>';
        $selected = '';

        foreach ($args['options'] as $country) {
            if (isset($value) && is_array($value)) {
                if (in_array($country['code'], $value)) {
                    $selected = " selected='selected'";
                } else {
                    $selected = '';
                }
            }
            $html .= '<option value="' . $country['code'] . '" ' . $selected . '>' . $country['name'] . '</option>';
        }

        $html .= '</select>';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function advancedselect_callback($args)
    {
        $value = $this->options[$args['id']] ?? $args['std'] ?? '';

        if (is_rtl()) {
            $class_name = 'chosen-select chosen-rtl';
        } else {
            $class_name = 'chosen-select camoo-sms-et';
        }

        $html = '<select class="' . $class_name . '" id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . ']"/>';

        foreach ($args['options'] as $key => $v) {
            $html .= '<optgroup label="' . ucfirst(str_replace('_', ' ', $key)) . '">';

            foreach ($v as $option => $name) {
                $disabled = ($key == 'pro_pack_gateways') ? $disabled = ' disabled' : '';
                $selected = selected($option, $value, false);
                $html .= '<option value="' . $option . '" ' . $selected . ' ' . $disabled . '>' . ucfirst($name) . '</option>';
            }

            $html .= '</optgroup>';
        }

        $html .= '</select>';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function color_select_callback($args)
    {
        $value = $this->options[$args['id']] ?? $args['std'] ?? '';

        $html = '<select id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . ']"/>';

        foreach ($args['options'] as $option => $color) {
            $selected = selected($option, $value, false);
            $html .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
        }

        $html .= '</select>';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function rich_editor_callback($args)
    {
        global $wp_version;

        $value = $this->options[$args['id']] ?? $args['std'] ?? '';

        if ($wp_version >= 3.3 && function_exists('wp_editor')) {
            $html = wp_editor(stripslashes($value), 'wp_camoo_sms_settings[' . $args['id'] . ']', ['textarea_name' => 'wp_camoo_sms_settings[' . $args['id'] . ']']);
        } else {
            $html = '<textarea class="large-text" rows="10" id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . ']">' . esc_textarea(stripslashes($value)) . '</textarea>';
        }

        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function upload_callback($args)
    {
        $value = $this->options[$args['id']] ?? $args['std'] ?? '';

        $size = (isset($args['size']) && !is_null($args['size'])) ? $args['size'] : 'regular';
        $html = '<input type="text" class="' . $size . '-text wpcamoosms_upload_field" id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . ']" value="' . esc_attr(stripslashes($value)) . '"/>';
        $html .= '<span>&nbsp;<input type="button" class="wp_camoo_sms_settings_upload_button button-secondary" value="' . __('Upload File', 'wpsms') . '"/></span>';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function color_callback($args)
    {
        $value = $this->options[$args['id']] ?? $args['std'] ?? '';

        $default = $args['std'] ?? '';

        $html = '<input type="text" class="wpsms-color-picker" id="wp_camoo_sms_settings[' . $args['id'] . ']" name="wp_camoo_sms_settings[' . $args['id'] . ']" value="' . esc_attr($value) . '" data-default-color="' . esc_attr($default) . '" />';
        $html .= '<p class="description"> ' . $args['desc'] . '</p>';

        echo $html;
    }

    public function render_settings()
    {
        $active_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->get_tabs()) ? $_GET['tab'] : 'general';

        ob_start(); ?>
        <div class="wrap wpsms-settings-wrap">
            <?php do_action('wp_camoo_sms_settings_page'); ?>
            <h2><?php _e('Settings', 'wp-camoo-sms') ?></h2>
            <div class="wpsms-tab-group">
                <ul class="wpsms-tab">
                    <li id="wpsms-logo">
                        <img src="<?php echo WP_CAMOO_SMS_URL; ?>assets/images/logo.svg"/>
                        <p><?php echo sprintf(__('CAMOO-SMS v%s', 'wp-camoo-sms'), WP_CAMOO_SMS_VERSION); ?></p>
                        <?php do_action('wp_camoo_sms_after_setting_logo'); ?>
                    </li>
                    <?php
                    foreach ($this->get_tabs() as $tab_id => $tab_name) {
                        $tab_url = add_query_arg([
                            'settings-updated' => false,
                            'tab' => $tab_id,
                        ]);

                        $active = $active_tab == $tab_id ? 'active' : '';

                        echo '<li><a href="' . esc_url($tab_url) . '" title="' . esc_attr($tab_name) . '" class="' . $active . '">';
                        echo $tab_name;
                        echo '</a></li>';
                    } ?>
                </ul>
                <?php settings_errors('camoo-sms-notices'); ?>
                <div class="wpsms-tab-content">
                    <form method="post" action="options.php">
                        <table class="form-table">
                            <?php
                            settings_fields($this->settingName);
        do_settings_fields('wp_camoo_sms_settings_' . $active_tab, 'wp_camoo_sms_settings_' . $active_tab); ?>
                        </table>
                        <?php submit_button(); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
        echo ob_get_clean();
    }

    /*
     * Get list Post Type
     */
    public function get_list_post_type($args = []): array
    {
        // vars
        $post_types = [];

        // extract special arg
        $exclude = [];
        $exclude[] = 'attachment';
        $exclude[] = 'acf-field'; //Advance custom field
        $exclude[] = 'acf-field-group'; //Advance custom field Group
        $exclude[] = 'vc4_templates'; //Visual composer
        $exclude[] = 'vc_grid_item'; //Visual composer Grid
        $exclude[] = 'acf'; //Advance custom field Basic
        $exclude[] = 'wpcf7_contact_form'; //contact 7 Post Type
        $exclude[] = 'shop_order'; //WooCommerce Shop Order
        $exclude[] = 'shop_coupon'; //WooCommerce Shop coupon

        // get post type objects
        $objects = get_post_types($args, 'objects');
        foreach ($objects as $k => $object) {
            if (in_array($k, $exclude)) {
                continue;
            }
            if ($object->_builtin && !$object->public) {
                continue;
            }
            $post_types[] = [$object->cap->publish_posts . '|' . $object->name => $object->label];
        }

        // return
        return $post_types;
    }

    /**
     * Get countries list
     *
     * @return array|mixed|object
     */
    public function get_countries_list()
    {
        // Load countries list file
        $file = WP_CAMOO_SMS_DIR . 'assets/countries.json';
        $file = file_get_contents($file);

        return json_decode($file, true);
    }
}

(new Settings());
