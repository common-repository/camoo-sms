<?php

namespace CAMOO_SMS\Status;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

use DateTime;
use DateTimeInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use wpdb;

/**
 * @category   class
 *
 * @version    1.0
 */
class Status
{
    private const SMS_STATUS = [
        'delivered',
        'scheduled',
        'buffered',
        'sent',
        'expired',
        'delivery_failed',
    ];

    private wpdb $db;

    public function __construct()
    {
        global $wpdb;

        $this->db = $wpdb;
    }

    public static function allowedStatus(): array
    {
        return self::SMS_STATUS;
    }

    public static function validateDate(string $statusTime): bool
    {
        [$statusTime] = explode(' ', $statusTime);
        return DateTime::createFromFormat(DateTimeInterface::ATOM, $statusTime) !== false;
    }

    public function manage(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $data = $request->get_params();
        $id = sanitize_key($data['id']);
        $status = sanitize_key($data['status']);
        $recipient = sanitize_text_field($data['recipient']);
        $sDatetime = sanitize_text_field($data['statusDatetime']);

        if (!empty($id) && !empty($status) && !empty($recipient) && !empty($sDatetime) &&
            ($ohSMS = $this->getByMessageId($id))) {
            $options = ['status' => $status, 'status_time' => $sDatetime];
            if (in_array($status, static::allowedStatus()) && static::validateDate($sDatetime) &&
                $this->updateById($ohSMS->ID, $options)) {
                return new WP_REST_Response(['message' => 'OK', 'error' => []], 200);
            }
        }

        return new WP_Error('404', 'Page Not Found!', ['status' => 404]);
    }

    private function updateById(int|string $id, array $options): mixed
    {
        return $this->db->update($this->db->prefix . 'camoo_sms_send', $options, ['ID' => $id]);
    }

    private function getByMessageId(string $id): mixed
    {
        return $this->db->get_row(
            "SELECT * FROM `{$this->db->prefix}camoo_sms_send` WHERE message_id = '{$id}' LIMIT 1"
        );
    }
}
