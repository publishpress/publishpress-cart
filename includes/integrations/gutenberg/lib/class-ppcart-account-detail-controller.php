<?php

if (! defined('ABSPATH')) {
    exit;
}

class PPCart_Account_Detail_Controller
{
    /**
     * Account renderer.
     *
     * @var PPCart_Account_Renderer
     */
    private $renderer;

    public function __construct(PPCart_Account_Renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function register_routes()
    {
        register_rest_route(
            'publishpress-cart/v1',
            '/account-block/detail',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this->renderer, 'get_account_detail' ],
                'permission_callback' => [ $this->renderer, 'can_view_account_detail' ],
                'args'                => [
                    'type'         => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ],
                    'id'           => [
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'presentation' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ],
                    'returnUrl'    => [
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                    ],
                ],
            ]
        );
    }
}
