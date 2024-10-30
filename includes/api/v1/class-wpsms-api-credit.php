<?php

namespace CAMOO_SMS\Api\V1;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
use CAMOO_SMS\Option;
use CAMOO_SMS\RestApi;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * @category   class
 *
 * @version    1.0
 */
class Credit extends RestApi
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
        register_rest_route($this->namespace . '/v1', '/credit', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'credit_callback'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
        ]);
    }

    public function credit_callback(WP_REST_Request $request): WP_REST_Response
    {
        $output = [
            'credit' => Option::getOptions('wp_camoo_sms_gateway_credit'),
        ];

        return new WP_REST_Response($output);
    }

    /**
     * Check user permission
     */
    public function get_item_permissions_check($request): bool
    {
        return current_user_can('wpcamoosms_setting');
    }
}

(new Credit());
