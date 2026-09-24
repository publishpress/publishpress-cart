<?php

/**
 * Stripe hosted-checkout return helpers.
 *
 * @package PublishPress_Cart
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
     * Locates a local order id for a Stripe Checkout Session id.
     *
     * @param string $session_id Stripe Checkout Session id (cs_...).
     * @return int
     */
function ppcart_hosted_checkout_find_order_id_by_session($session_id)
{
    $session_id = sanitize_text_field((string) $session_id);
    if ('' === $session_id) {
        return 0;
    }

    $orders = get_posts(
        [
            'post_type'              => ppcart_query_post_types('order'),
            'post_status'            => 'any',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Lookup by unique session id.
            'meta_query'             => [
                [
                    'key' => ppcart_meta_key('checkout_session_id'),
                    'value' => $session_id,
                ],
            ],
        ]
    );

    return ! empty($orders[0]) ? absint($orders[0]) : 0;
}


/**
     * Resolves order id + Stripe gateway mode for a hosted-checkout return.
     *
     * Prefers the short-lived transient written at session creation, then durable
     * order meta keyed by checkout session id (survives transient miss/eviction).
     *
     * @param string $session_id Stripe Checkout Session id.
     * @return array{order_id:int,gateway_mode:string,source:string}
     */
function ppcart_hosted_checkout_resolve_return_context($session_id)
{
    $session_id = sanitize_text_field((string) $session_id);
    $order_id   = 0;
    $mode       = '';
    $source     = 'empty';

    if ('' === $session_id) {
        return [
            'order_id'     => 0,
            'gateway_mode' => '',
            'source'       => 'empty',
        ];
    }

    $payload = get_transient('ppcart_hosted_session_' . sanitize_key($session_id));
    if (is_array($payload)) {
        $order_id = ! empty($payload['order_id']) ? absint($payload['order_id']) : 0;
        $mode     = ! empty($payload['gateway_mode']) ? sanitize_text_field($payload['gateway_mode']) : '';
        $source   = 'transient';
    }

    if (! $order_id) {
        $order_id = ppcart_hosted_checkout_find_order_id_by_session($session_id);
        if ($order_id) {
            $source = ('transient' === $source) ? 'transient+order_meta' : 'order_meta';
        }
    }

    if ('' === $mode && $order_id) {
        $stored = ppcart_get_post_meta($order_id, 'gateway_mode', true);
        if (is_string($stored) && '' !== $stored) {
            $mode = sanitize_text_field($stored);
            if ('transient' === $source) {
                $source = 'transient+order_mode';
            }
        }
    }

    return [
        'order_id'     => $order_id,
        'gateway_mode' => $mode,
        'source'       => $source,
    ];
}


/**
     * Gateway modes to try when retrieving a Checkout Session.
     *
     * @param string $preferred Preferred mode from transient/order meta.
     * @return string[]
     */
function ppcart_hosted_checkout_modes_to_try($preferred = '')
{
    $preferred = sanitize_text_field((string) $preferred);
    $modes     = [];

    if ('' !== $preferred) {
        $modes[] = $preferred;
    }

    foreach ([ 'test', 'live' ] as $candidate) {
        if (! in_array($candidate, $modes, true)) {
            $modes[] = $candidate;
        }
    }

    return $modes;
}


/**
     * Builds a confirmation redirect URL with a non-empty thanks_url fallback.
     *
     * Mirrors the classic-checkout branches in
     * public/controllers/order/traits/templates/order-save-save-order-to-db.php so
     * every Confirmation Type (message, page, redirect) and the upsell path behave
     * the same after a Stripe-hosted purchase.
     *
     * @param int $order_id   Local order id.
     * @param int $product_id Product id.
     * @return string
     */
function ppcart_hosted_checkout_confirmation_url($order_id, $product_id)
{
    $order_id   = absint($order_id);
    $product_id = absint($product_id);
    $ppcart_product        = null;
    $thanks     = '';

    if ($product_id && function_exists('ppcart_setup_product')) {
        $ppcart_product = ppcart_setup_product($product_id);
        if (is_object($ppcart_product) && ! empty($ppcart_product->thanks_url)) {
            $thanks = (string) $ppcart_product->thanks_url;
        }
    }

    // Upsell funnel owns the post-purchase destination; it appends ppcart-order itself.
    if (is_object($ppcart_product) && ! empty($ppcart_product->upsell_path) && ! empty($ppcart_product->form_action)) {
        $upsell_url = (string) apply_filters('ppcart_host_purchase_url', (string) $ppcart_product->form_action, $order_id, $product_id);
        /** This filter is documented at the end of this function. */
        return add_query_arg(PPCart_Order::access_arg($order_id), $upsell_url);
    }

    // Confirmation Type "redirect": send the buyer to the configured URL, with
    // merge tags resolved, instead of a thank-you page carrying ppcart-order.
    if (is_object($ppcart_product) && isset($ppcart_product->redirect_url) && '' !== (string) $ppcart_product->redirect_url) {
        $order_info = [];
        if ($order_id && class_exists('PPCart_Order')) {
            $cart_order = new PPCart_Order($order_id);
            if ($cart_order->id) {
                $order_info = $cart_order->get_data();
            }
        }

        $redirect = function_exists('ppcart_personalize')
            ? ppcart_personalize((string) $ppcart_product->redirect_url, $order_info, 'urlencode')
            : (string) $ppcart_product->redirect_url;

        if ($redirect) {
            /** This filter is documented at the end of this function. */
            return (string) apply_filters('ppcart_host_purchase_url', esc_url_raw($redirect), $order_id, $product_id);
        }
    }

    // `_ppcart_confirmation_page` is product post meta, not a global option, so there is
    // no site-wide confirmation page to fall back to. Mirror ppcart_setup_product(): when
    // no thank-you page resolves, send the buyer back to the product, which renders
    // the confirmation from the `ppcart-order` query arg.
    if ('' === $thanks && $product_id) {
        $permalink = get_permalink($product_id);
        if (is_string($permalink) && '' !== $permalink) {
            $thanks = $permalink;
        }
    }

    if ('' === $thanks) {
        $thanks = home_url('/');
    }

    $confirmation = PPCart_Order::confirmation_url($thanks, $order_id);
    /**
     * Filter post-purchase redirect URL after hosted checkout success.
     *
     * @param string $confirmation Confirmation URL.
     * @param int    $order_id     Order id.
     * @param int    $product_id   Product id.
     */
    return (string) apply_filters('ppcart_host_purchase_url', $confirmation, $order_id, $product_id);
}
