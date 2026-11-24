<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Cart checkout: the form, the order built from the claimed cart, the payment, and the confirmation.
 */
final class PPCart_Public_Cart_Checkout_Controller
{
    public function __construct(
        private PPCart_Shopping_Cart_Store $store = new PPCart_Shopping_Cart_Store(),
        private PPCart_Shopping_Cart_Settings $settings = new PPCart_Shopping_Cart_Settings(),
        private PPCart_Shopping_Cart_View $view = new PPCart_Shopping_Cart_View(),
        private PPCart_Shopping_Cart_Stripe $stripe = new PPCart_Shopping_Cart_Stripe(),
        private PPCart_Shopping_Cart_Rest $rest = new PPCart_Shopping_Cart_Rest(),
    ) {
    }

    public function register_routes(): void
    {
        register_rest_route(PPCart_Shopping_Cart_Rest::REST_NAMESPACE, '/cart/checkout', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'checkout'],
            'permission_callback' => [$this->rest, 'can_change_cart'],
        ]);
        register_rest_route(PPCart_Shopping_Cart_Rest::REST_NAMESPACE, '/cart/checkout/confirm', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'confirm'],
            'permission_callback' => [$this->rest, 'can_change_cart'],
            'args'                => [
                'order_id' => ['type' => 'integer', 'required' => true, 'minimum' => 1],
                'token'    => ['type' => 'string', 'required' => true],
            ],
        ]);
    }

    public function checkout(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if (! $this->settings->enabled()) {
            return $this->refuse(new WP_Error('ppcart_cart_disabled', __('The shopping cart is not available.', 'publishpress-cart')), $request);
        }
        $posted = (array) ($request->get_json_params() ?: $request->get_body_params());
        $input = PPCart_Shopping_Cart_Checkout_Input::from_post($this->settings->checkout_fields(), $posted);
        if (is_wp_error($input)) {
            return $this->refuse($input, $request);
        }
        $method = $input->payment_method();
        if ('' !== $method && ! isset($this->payment_methods()[$method])) {
            return $this->refuse($this->no_payment_method(), $request);
        }
        if ('stripe' === $method) {
            ppcart_check_stripe_checkout_rate_limit(false);
        }

        $cart = $this->store->claim_checkout($input->seen_revision(), $input->customer());
        if (is_wp_error($cart)) {
            return $this->refuse($cart, $request);
        }

        $order = new PPCart_Order();
        $order->load_from_shopping_cart($cart, $input->customer());
        $page_id = absint($posted['ppcart_page_id'] ?? 0);
        if ($page_id && 'publish' === get_post_status($page_id)) {
            $order->page_id = $page_id;
            $order->page_url = get_permalink($page_id);
        }

        $payment = $this->pay($order, $method);
        if (is_wp_error($payment)) {
            $payment->add_data(['cart' => $this->store->read()] + (array) $payment->get_error_data());
            return $this->refuse($payment, $request);
        }
        return rest_ensure_response($payment + $this->result($order, $request));
    }

    /** The browser calls this after Stripe confirmed the payment. Stripe, not the browser, says whether it succeeded. */
    public function confirm(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $order_id = absint($request['order_id']);
        $order = PPCart_Order::token_matches($order_id, (string) $request['token']) ? new PPCart_Order($order_id) : null;
        if (! $order || ! $order->id || ! ppcart_order_is_shopping_cart($order)) {
            return $this->refuse(new WP_Error('ppcart_cart_order_missing', __('This order was not found.', 'publishpress-cart')), $request);
        }
        $settled = $this->stripe->settle($order);
        if (is_wp_error($settled)) {
            return $this->refuse($settled, $request);
        }
        return rest_ensure_response(['status' => 'paid'] + $this->result($order, $request));
    }

    /** The checkout block with source Cart and [ppcart_cart_checkout] print this. */
    public function render(array $attributes = [], bool $preview = false): string
    {
        if (! $this->settings->enabled()) {
            return '';
        }
        ppcart_shopping_cart_nocache();
        ppcart_shopping_cart_assets();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only. The order access token in the request is the proof.
        $order_id = isset($_GET['ppcart-order']) ? absint(wp_unslash($_GET['ppcart-order'])) : 0;
        if (! $preview && $order_id && PPCart_Order::visitor_can_view($order_id)) {
            $order = new PPCart_Order($order_id);
            if (ppcart_order_is_shopping_cart($order)) {
                return ppcart_shopping_cart_confirmation($order, $attributes);
            }
        }

        $stripe = PPCart_Shopping_Cart_Stripe::available();
        if ($stripe && ! $preview) {
            // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Stripe asks sites to load this script from js.stripe.com without a version.
            wp_enqueue_script('ppcart-stripe-api-v3', 'https://js.stripe.com/v3/', [], null, true);
        }
        $user = wp_get_current_user();
        return $this->view->render('checkout', $this->store->read(), $attributes, [
            'preview' => $preview,
            'fields'  => $this->settings->checkout_fields(),
            'values'  => $user->exists() ? ['first_name' => $user->first_name, 'last_name' => $user->last_name, 'email' => $user->user_email] : [],
            'methods' => $this->payment_methods(),
            'button'  => $this->settings->button_text(),
            'page_id' => get_queried_object_id(),
            'stripe'  => $stripe ? [
                'key'      => $GLOBALS['ppcart_stripe']['pk'],
                'locale'   => ppcart_stripe_locale(),
                'currency' => strtolower((string) $GLOBALS['ppcart_currency']),
            ] : null,
        ]);
    }

    /** @return array<string,string> Pay methods a buyer can choose, with their labels. A free order needs none. */
    public function payment_methods(): array
    {
        $methods = [];
        if (PPCart_Shopping_Cart_Stripe::available()) {
            $methods['stripe'] = __('Credit Card', 'publishpress-cart');
        }
        if ('1' == get_option('_ppcart_cashondelivery_enable')) {
            $methods['cod'] = __('Cash on Delivery', 'publishpress-cart');
        }
        return $methods;
    }

    /**
     * Store the order and start its payment. The amount is the total of the stored order.
     *
     * @return array{status:string,payment?:array{client_secret:string,amount:int,currency:string}}|WP_Error
     */
    private function pay(PPCart_Order $order, string $method): array|WP_Error
    {
        if ((float) $order->amount <= 0) {
            $order->pay_method = PPCart_Order::$free_gateway_str;
            $order->status = PPCart_Order::$paid_str;
            $order->store();
            return ['status' => 'paid'];
        }
        if ('cod' === $method) {
            $order->store();
            return ['status' => 'pending'];
        }
        if ('stripe' !== $method) {
            return $this->no_payment_method();
        }

        $order->gateway_mode = $GLOBALS['ppcart_stripe']['mode'];
        ppcart_store_stripe_owned_record($order);
        $intent = $this->stripe->create_intent($order);
        if (is_wp_error($intent)) {
            ppcart_log_entry($order->id, $intent->get_error_message());
            return $intent;
        }
        return [
            'status'  => 'requires_payment',
            'payment' => [
                'client_secret' => $intent['client_secret'],
                'amount'        => PPCart_Shopping_Cart_Stripe::amount((float) $order->amount),
                'currency'      => strtolower((string) $GLOBALS['ppcart_currency']),
            ],
        ];
    }

    /** What the browser needs after the order exists: where to go, and the cart as it is now. */
    private function result(PPCart_Order $order, WP_REST_Request $request): array
    {
        $confirmation = $this->settings->confirmation($order);
        if ('message' === $confirmation['type'] && $order->page_url) {
            $confirmation['url'] = PPCart_Order::confirmation_url($order->page_url, $order->id);
        }
        return [
            'order_id'     => (int) $order->id,
            'token'        => PPCart_Order::ensure_invoice_access_token($order->id),
            'confirmation' => $confirmation,
            'cart'         => $this->view->response($this->store->read(), $request),
        ];
    }

    private function no_payment_method(): WP_Error
    {
        return new WP_Error('ppcart_cart_payment_method', __('Choose a payment method.', 'publishpress-cart'));
    }

    private function refuse(WP_Error $error, WP_REST_Request $request): WP_Error
    {
        return $this->rest->error_response($error, $request);
    }
}
