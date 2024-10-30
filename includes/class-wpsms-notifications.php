<?php

namespace CAMOO_SMS;

use WP_Post;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Notifications
{
    public $sms;

    public $date;

    public $options;

    /**
     * WordPress Database
     *
     * @var string
     */
    protected $db;

    /**
     * WordPress Table prefix
     *
     * @var string
     */
    protected $tb_prefix;

    /** CAMOO_SMS_Notifications constructor. */
    public function __construct()
    {
        global $oCamooSMS, $wp_version, $wpdb;

        $this->sms = $oCamooSMS;
        $this->date = CAMOO_SMS_CURRENT_DATE;
        $this->options = Option::getOptions();
        $this->db = $wpdb;
        $this->tb_prefix = $wpdb->prefix;

        if (isset($this->options['notif_publish_new_post'])) {
            add_action('add_meta_boxes', [$this, 'notification_meta_box']);
            add_action('publish_post', [$this, 'new_post'], 10, 2);
        }

        // WordPress new version
        if (isset($this->options['notif_publish_new_wpversion'])) {
            $update = get_site_transient('update_core');
            $update = $update->updates;

            if (isset($update[1])) {
                if ($update[1]->current > $wp_version && $this->sms->getCredit()) {
                    if (Option::getOptions('wp_last_send_notification') === false) {
                        $this->sms->to = [$this->options['admin_mobile_number']];
                        $this->sms->msg = sprintf(
                            __('WordPress %s is available! Please update now', 'wp-camoo-sms'),
                            $update[1]->current
                        );
                        $this->sms->sendSMS();

                        update_option('wp_last_send_notification', true);
                    }
                } else {
                    update_option('wp_last_send_notification', false);
                }
            }
        }
        $this->initialize();
    }

    /** Add subscribe meta box to the post */
    public function notification_meta_box()
    {
        add_meta_box('subscribe-meta-box', __('SMS', 'wp-camoo-sms'), [
            $this,
            'notification_meta_box_handler',
        ], 'post', 'normal', 'high');
    }

    /**
     * New post manual send SMS
     */
    public function notification_meta_box_handler($post)
    {
        global $wpdb;

        $get_group_result = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}camoo_sms_subscribes_group`");
        $username_active = $wpdb->query("SELECT * FROM {$wpdb->prefix}camoo_sms_subscribes WHERE status = '1'");
        include_once WP_CAMOO_SMS_DIR . 'includes/templates/meta-box.php';
    }

    /**
     * Send SMS when a new post add
     *
     * @return null
     *
     * @internal param $post_id
     */
    public function new_post($ID, $post)
    {
        if ($_REQUEST['wps_send_subscribe'] == 'yes') {
            if ($_REQUEST['wps_subscribe_group'] == 'all') {
                $this->sms->to = $this->db->get_col("SELECT `mobile` FROM {$this->tb_prefix}camoo_sms_subscribes");
            } else {
                $this->sms->to = $this->db->get_col("SELECT `mobile` FROM {$this->tb_prefix}camoo_sms_subscribes WHERE group_ID = '" .
                    sanitize_text_field($_REQUEST['wps_subscribe_group']) . "'");
            }

            $template_vars = [
                '%post_title%' => get_the_title($ID),
                '%post_content%' => wp_trim_words($post->post_content, 10),
                '%post_url%' => wp_get_shortlink($ID),
                '%post_date%' => get_post_time('Y-m-d H:i:s', false, $ID, true),
            ];

            $message = str_replace(
                array_keys($template_vars),
                array_values($template_vars),
                sanitize_text_field($_REQUEST['wpcamoosms_text_template'])
            );

            $this->sms->msg = $message;
            $this->sms->sendSMS();
        }
    }

    /**
     * Send SMS when a new user registered
     */
    public function new_user($user_id)
    {
        $user = get_userdata($user_id);

        $template_vars = [
            '%user_login%' => $user->user_login,
            '%user_email%' => $user->user_email,
            '%date_register%' => $this->date,
        ];

        if (Option::getOption('admin_mobile_number')) {
            // Send SMS to admin
            $this->sms->to = [$this->options['admin_mobile_number']];
            $message = str_replace(
                array_keys($template_vars),
                array_values($template_vars),
                $this->options['notif_register_new_user_admin_template']
            );
            $this->sms->msg = $message;
            $this->sms->sendSMS();
        }

        // Modify request value.
        $request = apply_filters('wp_camoo_sms_from_notify_user_register', $_REQUEST);

        // Send SMS to user register.
        if (isset($user->mobile) || $request && !is_array($request)) {
            if (isset($user->mobile)) {
                $this->sms->to = [$user->mobile];
            } elseif ($request) {
                $this->sms->to = [$request];
            }
            $message = str_replace(
                array_keys($template_vars),
                array_values($template_vars),
                $this->options['notif_register_new_user_template']
            );
            $this->sms->msg = $message;
            $this->sms->sendSMS();
        }
    }

    /**
     * Send SMS when new comment add
     */
    public function new_comment($comment_id, $comment_object)
    {
        if ($comment_object->comment_type == 'order_note') {
            return;
        }

        if ($comment_object->comment_type == 'edd_payment_note') {
            return;
        }

        $this->sms->to = [$this->options['admin_mobile_number']];
        $template_vars = [
            '%comment_author%' => $comment_object->comment_author,
            '%comment_author_email%' => $comment_object->comment_author_email,
            '%comment_author_url%' => $comment_object->comment_author_url,
            '%comment_author_IP%' => $comment_object->comment_author_IP,
            '%comment_date%' => $comment_object->comment_date,
            '%comment_content%' => $comment_object->comment_content,
        ];
        $message = str_replace(
            array_keys($template_vars),
            array_values($template_vars),
            $this->options['notif_new_comment_template']
        );
        $this->sms->msg = $message;
        $this->sms->sendSMS();
    }

    /**
     * Send SMS when user logged in
     */
    public function login_user($username_login, $username)
    {
        if (Option::getOption('admin_mobile_number')) {
            $this->sms->to = [$this->options['admin_mobile_number']];

            $template_vars = [
                '%username_login%' => $username->user_login,
                '%display_name%' => $username->display_name,
            ];
            $message = str_replace(
                array_keys($template_vars),
                array_values($template_vars),
                $this->options['notif_user_login_template']
            );
            $this->sms->msg = $message;
            $this->sms->sendSMS();
        }
    }

    /**
     * Send sms to author of the post if published
     */
    public function new_post_published($ID, WP_Post $post)
    {
        $message = '';
        $template_vars = [
            '%post_title%' => get_the_title($ID),
            '%post_content%' => wp_trim_words($post->post_content, 10),
            '%post_url%' => wp_get_shortlink($ID),
            '%post_date%' => get_post_time('Y-m-d H:i:s', false, $ID, true),
        ];
        $template = $this->options['notif_publish_new_post_author_template'] ?? '';
        if ($template) {
            $message = str_replace(array_keys($template_vars), array_values($template_vars), $template);
        }
        $this->sms->to = [get_user_meta($post->post_author, 'mobile', true)];
        $this->sms->msg = $message;
        $this->sms->sendSMS();
    }

    /**
     * Add only on publish transition actions
     */
    public function transition_publish($new_status, $old_status, $post)
    {
        if ('publish' === $new_status && 'publish' !== $old_status) {
            $post_types_option = Option::getOption('notif_publish_new_post_author_post_type');

            // Check selected post types or not?
            if ($post_types_option and is_array($post_types_option)) {
                // Initialize values
                $post_types = [];
                foreach ($post_types_option as $post_publish_type) {
                    $value = explode('|', $post_publish_type);
                    $post_types[$value[1]] = $value[0];
                }
                if (array_key_exists($post->post_type, $post_types) &&
                    !user_can($post->post_author, $post_types[$post->post_type])) {
                    $this->new_post_published($post->ID, $post);
                }
            }
        }
    }

    protected function initialize(): void
    {
        if (isset($this->options['notif_register_new_user'])) {
            add_action('user_register', [$this, 'new_user'], 10, 1);
        }

        if (isset($this->options['notif_new_comment'])) {
            add_action('wp_insert_comment', [$this, 'new_comment'], 99, 2);
        }

        if (isset($this->options['notif_user_login'])) {
            add_action('wp_login', [$this, 'login_user'], 99, 2);
        }

        // Check to send to author of the post is enabled or not
        if (Option::getOption('notif_publish_new_post_author')) {
            // Add transition publish post
            add_action('transition_post_status', [$this, 'transition_publish'], 10, 3);
        }
    }
}

(new Notifications());
