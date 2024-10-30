<?php

namespace CAMOO_SMS\Api\V1;

use CAMOO_SMS\RestApi;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * @category   class
 *
 * @version    1.0
 */
class Send extends RestApi
{
    public function __construct()
    {
        // Register routes
        add_action('rest_api_init', [$this, 'register_routes']);

        parent::__construct();
    }

    /** Register routes */
    public function register_routes()
    {
        // SMS Newsletter
        register_rest_route($this->namespace . '/v1', '/send', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'send_callback'],
                'args' => [
                    'to' => [
                        'required' => true,
                    ],
                    'msg' => [
                        'required' => true,
                    ],
                    'isflash' => [
                        'required' => false,
                    ],
                ],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);
    }

    public function send_callback(WP_REST_Request $request): WP_REST_Response
    {
        // Get parameters from request
        $params = $request->get_params();

        $to = $params['to'] ?? '';
        $msg = $params['msg'] ?? '';
        $isflash = $params['isflash'] ?? '';
        $result = self::sendSMS($to, $msg, $isflash);

        if (is_wp_error($result)) {
            return self::response($result->get_error_message(), 400);
        }

        return self::response($result);
    }

    /**
     * Check user permission
     */
    public function get_item_permissions_check($request): bool
    {
        return current_user_can('wpcamoosms_sendsms');
    }
}

(new Send());
