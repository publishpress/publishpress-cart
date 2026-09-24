<?php

if (! defined('ABSPATH')) {
    exit;
}

class PPCart_Checkout_Preview_Controller
{
    /**
     * Checkout renderer.
     *
     * @var PPCart_Checkout_Renderer
     */
    private $renderer;

    public function __construct(PPCart_Checkout_Renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function register_routes()
    {
        register_rest_route(
            'publishpress-cart/v1',
            '/checkout-block/products',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this->renderer, 'get_products' ],
                'permission_callback' => [ $this->renderer, 'can_edit_checkout_products' ],
                'args'                => [
                    'search' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );

        register_rest_route(
            'publishpress-cart/v1',
            '/checkout-block/preview',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this->renderer, 'get_preview' ],
                'permission_callback' => [ $this->renderer, 'can_preview_checkout_product' ],
                'args'                => [
                    'pid'            => [
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'template'       => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ],
                    'plan'           => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'coupon'         => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'hide_labels'    => [
                        'type'              => 'boolean',
                        'sanitize_callback' => 'rest_sanitize_boolean',
                    ],
                    'style_settings' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                    'content_order'  => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                    'text_settings'  => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                ],
            ]
        );
    }
}
