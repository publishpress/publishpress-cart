<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PPCart_Shopping_Cart_Rest
{
    public const REST_NAMESPACE = 'ppcart/v1';

    private const STATUS = [
        'ppcart_cart_disabled'          => 403,
        'ppcart_cart_item_missing'      => 404,
        'ppcart_cart_already_in_cart'   => 409,
        'ppcart_cart_full'              => 409,
        'ppcart_cart_conflict'          => 409,
        'ppcart_cart_changed'           => 409,
        'ppcart_cart_not_eligible'      => 422,
        'ppcart_cart_plan_not_eligible' => 422,
        'ppcart_cart_quantity_invalid'  => 422,
        'ppcart_cart_empty'             => 422,
    ];

    public function __construct(
        private PPCart_Shopping_Cart_Store $store = new PPCart_Shopping_Cart_Store(),
        private PPCart_Shopping_Cart_View $view = new PPCart_Shopping_Cart_View()
    ) {
    }

    public function register_routes(): void
    {
        $key = [
            'type'              => 'string',
            'required'          => true,
            'validate_callback' => static fn ($value): bool => is_string($value) && 1 === preg_match('/^[1-9][0-9]*:.+$/s', rawurldecode($value)),
            'sanitize_callback' => static fn (string $value): string => rawurldecode($value),
        ];
        register_rest_route(self::REST_NAMESPACE, '/cart', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_cart'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route(self::REST_NAMESPACE, '/cart/items', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'add_item'],
            'permission_callback' => [$this, 'can_change_cart'],
            'args'                => [
                'product_id' => ['type' => 'integer', 'required' => true, 'minimum' => 1],
                'plan_id'    => ['type' => 'string', 'required' => true, 'minLength' => 1],
            ],
        ]);
        register_rest_route(self::REST_NAMESPACE, '/cart/items/(?P<key>[^/]+)', [
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'remove_item'],
                'permission_callback' => [$this, 'can_change_cart'],
                'args'                => ['key' => $key],
            ],
            [
                'methods'             => 'PATCH',
                'callback'            => [$this, 'set_quantity'],
                'permission_callback' => [$this, 'can_change_cart'],
                'args'                => ['key' => $key, 'quantity' => ['type' => 'integer', 'required' => true]],
            ],
        ]);
    }

    public function get_cart(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response($this->view->response($this->store->read(), $request));
    }

    public function add_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $product_id = absint($request['product_id']);
        $plan_id = sanitize_text_field($request['plan_id']);
        return $this->respond(
            $this->store->update(static fn (PPCart_Shopping_Cart $cart): true|WP_Error => $cart->add_item($product_id, $plan_id), true),
            $request
        );
    }

    public function remove_item(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $key = (string) $request['key'];
        return $this->respond($this->store->update(static fn (PPCart_Shopping_Cart $cart): bool => $cart->remove_item($key)), $request);
    }

    public function set_quantity(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $key = (string) $request['key'];
        $quantity = (int) $request['quantity'];
        return $this->respond($this->store->update(static fn (PPCart_Shopping_Cart $cart): true|WP_Error => $cart->set_quantity($key, $quantity)), $request);
    }

    public function can_change_cart(WP_REST_Request $request): true|WP_Error
    {
        $nonce = $request->get_header('x_wp_nonce') ?: $request->get_param('_wpnonce');
        if (is_string($nonce) && wp_verify_nonce($nonce, 'wp_rest')) {
            return true;
        }
        return new WP_Error('rest_cookie_invalid_nonce', __('Your session expired. Please try again.', 'publishpress-cart'), ['status' => 403]);
    }

    /** Give a cart error its HTTP status and the current cart, so the client redraws without a second request. */
    public function error_response(WP_Error $error, WP_REST_Request $request): WP_Error
    {
        $data = (array) $error->get_error_data();
        $data['status'] = self::STATUS[$error->get_error_code()] ?? 500;
        if (($data['cart'] ?? null) instanceof PPCart_Shopping_Cart_Priced) {
            $data['cart'] = $this->view->response($data['cart'], $request);
        }
        $error->add_data($data);
        return $error;
    }

    /**
     * Let GET /cart refresh the REST nonce of a signed-in visitor.
     *
     * Core cookie authentication reads a request without a nonce as a guest and rejects an
     * expired nonce before the route runs. A cached page holds such a nonce, so the cart
     * read is the one route that restores the cookie user. A request from another origin
     * keeps core's result, because core answers any origin with credentials allowed.
     */
    public function authenticate_cart_read(mixed $result): mixed
    {
        if (! $this->is_cart_read() || ! $this->is_same_origin()) {
            return $result;
        }
        $invalid_nonce = is_wp_error($result) && 'rest_cookie_invalid_nonce' === $result->get_error_code();
        if (true !== $result && ! $invalid_nonce) {
            return $result;
        }
        if (! is_user_logged_in() && true === ($GLOBALS['wp_rest_auth_cookie'] ?? null)) {
            wp_set_current_user((int) wp_validate_auth_cookie('', 'logged_in'));
        }
        return true;
    }

    public function send_no_store(WP_HTTP_Response $response, WP_REST_Server $server, WP_REST_Request $request): WP_HTTP_Response
    {
        if (1 === preg_match('#^/' . self::REST_NAMESPACE . '/cart(/|$)#', $request->get_route())) {
            $response->header('Cache-Control', 'private, no-store');
        }
        return $response;
    }

    private function respond(PPCart_Shopping_Cart_Priced|WP_Error $cart, WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        return is_wp_error($cart) ? $this->error_response($cart, $request) : rest_ensure_response($this->view->response($cart, $request));
    }

    private function is_cart_read(): bool
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_key(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence check only; a method override disables the adapter.
        if ('get' !== $method || isset($_GET['_method']) || isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return false;
        }
        $route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';
        return is_string($route) && '/' . self::REST_NAMESPACE . '/cart' === untrailingslashit($route);
    }

    private function is_same_origin(): bool
    {
        if (isset($_SERVER['HTTP_SEC_FETCH_SITE'])) {
            return in_array(sanitize_key(wp_unslash($_SERVER['HTTP_SEC_FETCH_SITE'])), ['same-origin', 'none'], true);
        }
        $origin = get_http_origin();
        if (! $origin) {
            return true;
        }
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        return '' !== $host && strtolower($origin) === (is_ssl() ? 'https://' : 'http://') . strtolower($host);
    }
}
