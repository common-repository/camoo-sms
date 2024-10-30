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
class Newsletter extends RestApi
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
        register_rest_route($this->namespace . '/v1', '/newsletter', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'subscribe_callback'],
                'permission_callback' => '__return_true',
                'args' => [
                    'name' => [
                        'required' => true,
                    ],
                    'mobile' => [
                        'required' => true,
                    ],
                    'group_id' => [
                        'required' => false,
                    ],
                ],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'unsubscribe_callback'],
                'permission_callback' => '__return_true',
                'args' => [
                    'name' => [
                        'required' => true,
                    ],
                    'mobile' => [
                        'required' => true,
                    ],
                ],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'verify_subscriber_callback'],
                'permission_callback' => '__return_true',
                'args' => [
                    'name' => [
                        'required' => true,
                    ],
                    'mobile' => [
                        'required' => true,
                    ],
                    'activation' => [
                        'required' => true,
                    ],
                ],
            ],
        ]);
    }

    public function subscribe_callback(WP_REST_Request $request): WP_REST_Response
    {
        // Get parameters from request
        $params = $request->get_params();

        $group_id = $params['group_id'] ?? 1;
        $result = self::subscribe($params['name'], $params['mobile'], $group_id);

        if (is_wp_error($result)) {
            return self::response($result->get_error_message(), 400);
        }

        return self::response($result);
    }

    public function unsubscribe_callback(WP_REST_Request $request): WP_REST_Response
    {
        // Get parameters from request
        $params = $request->get_params();

        $group_id = $params['group_id'] ?? 1;
        $result = self::unSubscribe($params['name'], $params['mobile'], $group_id);

        if (is_wp_error($result)) {
            return self::response($result->get_error_message(), 400);
        }

        return self::response(__('Your number has been successfully unsubscribed.', 'wp-camoo-sms'));
    }

    public function verify_subscriber_callback(WP_REST_Request $request): WP_REST_Response
    {
        // Get parameters from request
        $params = $request->get_params();

        $group_id = $params['group_id'] ?? 1;
        $result = self::verifySubscriber($params['name'], $params['mobile'], $params['activation'], $group_id);

        if (is_wp_error($result)) {
            return self::response($result->get_error_message(), 400);
        }

        return self::response(__('Your number has been successfully subscribed.', 'wp-camoo-sms'));
    }
}

(new Newsletter());
