<?php

namespace CAMOO_SMS;

use CAMOO_SMS\Config\Bootstrap;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Newsletter
{
    public $date;

    protected $db;

    protected $tb_prefix;

    public function __construct()
    {
        global $wpdb;

        $this->date = CAMOO_SMS_CURRENT_DATE;
        $this->db = $wpdb;
        $this->tb_prefix = $wpdb->prefix;
        add_action('wp_enqueue_scripts', [$this, 'load_script']);
    }

    /** Include front table */
    public function load_script()
    {
        // jQuery will be included automatically
        wp_enqueue_script(
            'ajax-script',
            WP_CAMOO_SMS_URL . 'assets/js/script.js',
            ['jquery'],
            WP_CAMOO_SMS_VERSION
        );

        // Ajax params
        wp_localize_script('ajax-script', 'ajax_object', [
            'ajaxurl' => get_rest_url(null, 'camoosms/v1/newsletter'),
        ]);
    }

    /**
     * Add Subscriber
     *
     * @param string $group_id
     * @param string $status
     * @param null   $key
     */
    public static function addSubscriber($name, $mobile, $group_id = '', $status = '1', $key = null): array
    {
        global $wpdb;

        if (self::isDuplicate($mobile, $group_id)) {
            return [
                'result' => 'error',
                'message' => __(
                    'The mobile numbers has been already duplicate.',
                    Bootstrap::DOMAIN_TEXT
                ),
            ];
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'camoo_sms_subscribes',
            [
                'date' => CAMOO_SMS_CURRENT_DATE,
                'name' => $name,
                'mobile' => $mobile,
                'status' => $status,
                'activate_key' => $key,
                'group_ID' => $group_id,
            ]
        );

        if ($result) {
            /**
             * Run hook after adding subscribe.
             *
             * @param string $name   name.
             * @param string $mobile mobile.
             *
             * @since 3.0
             */
            do_action('wp_camoo_sms_add_subscriber', $name, $mobile);

            return ['result' => 'success', 'message' => __('Subscriber successfully added.', 'wp-camoo-sms')];
        }

        return [
            'result' => 'error',
            'message' => __('Having problem with add subscriber, please try again later.', Bootstrap::DOMAIN_TEXT),
        ];
    }

    /**
     * Get Subscriber
     *
     * @return array|object|void|null
     */
    public static function getSubscriber($id)
    {
        global $wpdb;
        $result = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}camoo_sms_subscribes` WHERE ID = '" . $id . "'");

        if ($result) {
            return $result;
        }
    }

    /**
     * Delete subscriber by number
     *
     * @param null $group_id
     */
    public static function deleteSubscriberByNumber($mobile, $group_id = null): array
    {
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'camoo_sms_subscribes',
            [
                'mobile' => $mobile,
                'group_id' => $group_id,
            ]
        );

        if (!$result) {
            return ['result' => 'error', 'message' => __('The subscribe does not exist.', 'wp-camoo-sms')];
        }

        /**
         * Run hook after deleting subscribe.
         *
         * @param string $result result query.
         *
         * @since 3.0
         */
        do_action('wp_camoo_sms_delete_subscriber', $result);

        return ['result' => 'success', 'message' => __('Subscribe successfully removed.', 'wp-camoo-sms')];
    }

    /**
     * Update Subscriber
     *
     * @param string $group_id
     * @param string $status
     *
     * @return array
     */
    public static function updateSubscriber($id, $name, $mobile, $group_id = '', $status = '1')
    {
        global $wpdb;

        if (empty($id) || empty($name) || empty($mobile)) {
            return ['result' => 'error', 'message' => __('The fields must be valued.', 'wp-camoo-sms')];
        }

        if (self::isDuplicate($mobile, $group_id, $id)) {
            return ['result' => 'error', 'message' => __('The mobile numbers has been already duplicate.', 'wp-camoo-sms')];
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'camoo_sms_subscribes',
            [
                'name' => $name,
                'mobile' => $mobile,
                'group_ID' => $group_id,
                'status' => $status,
            ],
            [
                'ID' => $id,
            ]
        );

        if ($result) {
            /**
             * Run hook after updating subscribe.
             *
             * @param string $result result query.
             *
             * @since 3.0
             */
            do_action('wp_camoo_sms_update_subscriber', $result);

            return ['result' => 'success', 'message' => __('Subscriber successfully updated.', 'wp-camoo-sms')];
        }

        return ['result' => 'error', 'message' => __('Having problem with update subscriber, Duplicate entries or subscriber not found! please try again.', 'wp-camoo-sms')];
    }

    /**
     * Get Group by group ID
     *
     * @return object|null
     */
    public static function getGroup($group_id)
    {
        global $wpdb;

        $db_prepare = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}camoo_sms_subscribes_group` WHERE `ID` = %d", $group_id);
        $result = $wpdb->get_row($db_prepare);

        if ($result) {
            return $result;
        }

        return null;
    }

    /**
     * Get Groups
     *
     * @return array|object|null
     */
    public static function getGroups()
    {
        global $wpdb;
        $result = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}camoo_sms_subscribes_group`");

        if ($result) {
            return $result;
        }
    }

    /**
     * Delete Group
     **
     * @return false|int|void
     */
    public static function deleteGroup(int $id)
    {
        global $wpdb;

        if (empty($id)) {
            return;
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'camoo_sms_subscribes_group',
            [
                'ID' => $id,
            ]
        );

        if ($result) {
            /**
             * Run hook after deleting group.
             *
             * @param string $result result query.
             *
             * @since 3.0
             */
            do_action('wp_camoo_sms_delete_group', $result);

            return $result;
        }
    }

    /**
     * Add Group
     *
     * @return array
     */
    public static function addGroup(string $name): ?array
    {
        global $wpdb;
        if (empty($name)) {
            return ['result' => 'error', 'message' => __('Name is empty!', 'wp-camoo-sms')];
        }

        $table = $wpdb->prefix . 'camoo_sms_subscribes_group';
        $prepare = $wpdb->prepare("SELECT COUNT(ID) FROM {$table} WHERE `name` = %s", $name);
        $count = $wpdb->get_var($prepare);
        if ($count) {
            return [
                'result' => 'error',
                'message' => sprintf(__('Group Name "%s" exists!', 'wp-camoo-sms'), $name),
            ];
        }
        $result = $wpdb->insert(
            $wpdb->prefix . 'camoo_sms_subscribes_group',
            [
                'name' => $name,
            ]
        );

        if (empty($result)) {
            return null;
        }

        do_action('wp_camoo_sms_add_group', $result);

        return ['result' => 'success', 'message' => __('Group successfully added.', 'wp-camoo-sms')];
    }

    /**
     * Update Group
     *
     * @return array|void
     *
     * @internal param param $Not
     */
    public static function updateGroup($id, $name)
    {
        global $wpdb;

        if (empty($id) or empty($name)) {
            return;
        }

        $table = $wpdb->prefix . 'camoo_sms_subscribes_group';
        $prepare = $wpdb->prepare("SELECT COUNT(ID) FROM {$table} WHERE `name` = %s", $name);
        $count = $wpdb->get_var($prepare);

        if ($count) {
            return [
                'result' => 'error',
                'message' => sprintf(__('Group Name "%s" exists!', 'wp-camoo-sms'), $name),
            ];
        }
        $result = $wpdb->update(
            $wpdb->prefix . 'camoo_sms_subscribes_group',
            [
                'name' => $name,
            ],
            [
                'ID' => $id,
            ]
        );

        if ($result) {
            /**
             * Run hook after updating group.
             *
             * @param string $result result query.
             *
             * @since 3.0
             */
            do_action('wp_camoo_sms_update_group', $result);

            return ['result' => 'success', 'message' => __('Group successfully updated.', 'wp-camoo-sms')];
        }

        return [
            'result' => 'error',
            'message' => sprintf(__('Group Name "%s" exists!', 'wp-camoo-sms'), $name),
        ];
    }

    /**
     * Check the mobile number is duplicate
     *
     * @param null $group_id
     * @param null $id
     */
    public static function isDuplicate($mobile_number, $group_id = null, $id = null)
    {
        global $wpdb;
        $sql = "SELECT * FROM `{$wpdb->prefix}camoo_sms_subscribes` WHERE mobile = '" . $mobile_number . "'";

        if ($group_id) {
            $sql .= " AND group_id = '" . $group_id . "'";
        }

        if ($id) {
            $sql .= " AND id != '" . $id . "'";
        }

        $result = $wpdb->get_row($sql);

        return $result;
    }

    /**
     * @param string $group_id
     *
     * @return array
     */
    public static function getSubscribers($group_id = '')
    {
        global $wpdb;

        $where = '';

        if ($group_id) {
            $where = $wpdb->prepare(' WHERE group_ID = %d', $group_id);
        }

        $result = $wpdb->get_col("SELECT `mobile` FROM {$wpdb->prefix}camoo_sms_subscribes" . $where);

        return $result;
    }

    public static function insertSubscriber($date, $name, $mobile, $status, $group_id)
    {
        global $wpdb;

        $result = $wpdb->insert(
            "{$wpdb->prefix}camoo_sms_subscribes",
            [
                'date' => $date,
                'name' => $name,
                'mobile' => $mobile,
                'status' => $status,
                'group_ID' => $group_id,
            ]
        );

        return $result;
    }

    /**
     * Get Total Subscribers with Group ID
     *
     * @param null $group_id
     *
     * @return object|null
     */
    public static function getTotal($group_id = null)
    {
        global $wpdb;

        if ($group_id) {
            $result = $wpdb->query($wpdb->prepare("SELECT name FROM {$wpdb->prefix}camoo_sms_subscribes WHERE group_ID = %d", $group_id));
        } else {
            $result = $wpdb->query("SELECT name FROM {$wpdb->prefix}camoo_sms_subscribes");
        }

        if ($result) {
            return $result;
        }

        return null;
    }

    /**
     * Load NewsLetter form for Shortcode or Widget usage
     *
     * @param null $widget_id
     * @param null $instance
     */
    public static function loadNewsLetter($widget_id = null, $instance = null)
    {
        global $wpdb;
        $get_group_result = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}camoo_sms_subscribes_group`");

        include_once WP_CAMOO_SMS_DIR . 'includes/templates/subscribe-form.php';
    }
}

new Newsletter();
