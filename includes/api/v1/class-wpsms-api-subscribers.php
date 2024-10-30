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
class Subscribers extends RestApi
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
        register_rest_route($this->namespace . '/v1', '/subscribers', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'subscribers_callback'],
                'args' => [
                    'page' => [
                        'required' => false,
                    ],
                    'group_id' => [
                        'required' => false,
                    ],
                    'number' => [
                        'required' => false,
                    ],
                    'search' => [
                        'required' => false,
                    ],
                ],
                'permission_callback' => '__return_true',
            ],
        ]);
    }

    public function subscribers_callback(WP_REST_Request $request): WP_REST_Response
    {
        // Get parameters from request
        $params = $request->get_params();

        $page = $params['page'] ?? '';
        $group_id = $params['group_id'] ?? '';
        $mobile = $params['mobile'] ?? '';
        $search = $params['search'] ?? '';
        $result = self::getSubscribers($page, $group_id, $mobile, $search);

        return self::response($result);
    }

    /**
     * Check user permission
     */
    public function get_item_permissions_check($request): bool
    {
        return current_user_can('wpcamoosms_subscribers');
    }
}

(new Subscribers());
