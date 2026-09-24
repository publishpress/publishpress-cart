<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_product, $ppcart_debug_logger;
$ppcart_order_post = filter_input(INPUT_POST, 'ppcart-order', FILTER_VALIDATE_INT);
$nonce = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$is_downsell = null !== filter_input(INPUT_POST, 'downsell', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$ppcart_order_post = (false !== $ppcart_order_post && null !== $ppcart_order_post) ? absint($ppcart_order_post) : 0;
$nonce = is_string($nonce) ? sanitize_text_field($nonce) : '';

if (! $ppcart_order_post) {
    if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
        wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
    }
}

// order id only present for upsells
if ($ppcart_order_post) {
    $oto_type = $is_downsell ? 'downsell' : 'upsell';

    $order_info = (array) ppcart_setup_order($ppcart_order_post);

    if (! ppcart_verify_nonce($nonce, 'ppcart_' . $oto_type . '-' . $order_info['ID'])) {
        wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
    }

    $order_info = apply_filters('ppcart_before_order_save', $order_info, $subscription, $this);

    $order_post_id = apply_filters('ppcart_order_save_override', null, $order_info, $subscription, $this->get_stripe_save_helper());

    if (null !== $order_post_id) {
        return $order_post_id;
    }
} else {
    $post_data = ppcart_filter_input_array(
        INPUT_POST,
        [
            'ppcart-nonce'        => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'ppcart_product_id'   => FILTER_VALIDATE_INT,
            'ppcart_product_option' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'ppcart_temp_order_id' => FILTER_VALIDATE_INT,
            'intent_id'       => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        ]
    );
    $nonce        = isset($post_data['ppcart-nonce']) && is_string($post_data['ppcart-nonce']) ? sanitize_text_field($post_data['ppcart-nonce']) : '';
    $ppcart_product_id = isset($post_data['ppcart_product_id']) && false !== $post_data['ppcart_product_id'] && null !== $post_data['ppcart_product_id'] ? absint($post_data['ppcart_product_id']) : 0;
    $ppcart_option_id = isset($post_data['ppcart_product_option']) && is_string($post_data['ppcart_product_option']) ? sanitize_text_field($post_data['ppcart_product_option']) : '';
    $ppcart_temp_order_id = isset($post_data['ppcart_temp_order_id']) && false !== $post_data['ppcart_temp_order_id'] && null !== $post_data['ppcart_temp_order_id'] ? absint($post_data['ppcart_temp_order_id']) : 0;
    $intent_id    = isset($post_data['intent_id']) && is_string($post_data['intent_id']) ? sanitize_text_field($post_data['intent_id']) : '';
    $ppcart_temp_order_token = ppcart_filter_input(INPUT_POST, 'ppcart_temp_order_token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $ppcart_temp_order_token = is_string($ppcart_temp_order_token) ? sanitize_text_field($ppcart_temp_order_token) : '';

    $ppcart_debug_logger->log_event(
        'checkout.order.submitted',
        'Checkout order form submitted.',
        [
            'product_id'      => $ppcart_product_id,
            'option_id'       => $ppcart_option_id,
            'temp_order_id'   => $ppcart_temp_order_id,
            'has_stripe_intent' => '' !== $intent_id,
        ]
    );

    // base order
    if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
        $ppcart_debug_logger->log_event(
            'checkout.security.failed',
            'Checkout security check failed before saving order.',
            [
                'product_id' => $ppcart_product_id,
                'check'      => 'ppcart_purchase_nonce:2',
            ],
            4
        );
        wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
    }

    $ppcart_debug_logger->log_event(
        'checkout.order.validation.started',
        'Checkout order validation hooks started.',
        [
            'product_id' => $ppcart_product_id,
        ]
    );

    do_action('ppcart_before_create_main_order');

    $ppcart_debug_logger->log_event(
        'checkout.order.validation.passed',
        'Checkout order validation hooks passed.',
        [
            'product_id' => $ppcart_product_id,
        ],
        0
    );

    // setup product info
    $ppcart_product = ppcart_setup_product($ppcart_product_id);
    if ($ppcart_temp_order_id) {
        $stored_temp_order_token = ppcart_get_post_meta($ppcart_temp_order_id, 'temp_order_token', true);
        if (! is_string($stored_temp_order_token) || '' === $stored_temp_order_token || ! hash_equals($stored_temp_order_token, $ppcart_temp_order_token)) {
            $ppcart_debug_logger->log_debug('Temp order token validation failed for order #' . $ppcart_temp_order_id, 4);
            wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
        }

        $cart_order = new PPCart_Order($ppcart_temp_order_id);
    } else {
        // setup order info
        $cart_order = new PPCart_Order();
        $cart_order->load_from_post();
        $cart_order = apply_filters('ppcart_after_order_load_from_post', $cart_order);

        if (!isset($cart_order->order_summary_items) || empty($cart_order->order_summary_items)) {
            echo wp_json_encode([
                'error' => __("No items added", "publishpress-cart"),
            ]);
            exit();
        }
    }

    // stripe only fields
    if ($cart_order->pay_method == 'stripe' && $cart_order->amount && isset($ppcart_stripe['mode'])) {
        $cart_order->gateway_mode = $ppcart_stripe['mode'];
        $cart_order->transaction_id = $intent_id;
    }

    // Free order
    if (! $ppcart_temp_order_id && 0 == $cart_order->non_formatted_amount) {
        $cart_order->pay_method = 'free';
        $cart_order->status = 'paid';
    }

    $ppcart_debug_logger->log_event(
        'checkout.order.saving',
        'Saving checkout order to PublishPress Cart.',
        [
            'order_id'   => (int) $cart_order->id,
            'product_id' => (int) $cart_order->product_id,
            'status'     => $cart_order->status,
            'gateway'    => $cart_order->pay_method,
        ]
    );

    // save order to db
    $order_post_id = $this->store_stripe_owned_record($cart_order);

    if ($ppcart_temp_order_id) {
        $this->clear_temp_order_meta($ppcart_temp_order_id);
    }

    if ($order_post_id) {
        $ppcart_debug_logger->log_event(
            'checkout.order.saved',
            "Order #{$order_post_id} saved successfully with status {$cart_order->status}.",
            [
                'order_id'   => (int) $order_post_id,
                'product_id' => (int) $cart_order->product_id,
                'status'     => $cart_order->status,
                'gateway'    => $cart_order->pay_method,
            ],
            0
        );
    } else {
        $ppcart_debug_logger->log_event(
            'checkout.order.failed',
            'Order could not be saved after checkout validation.',
            [
                'product_id' => (int) $cart_order->product_id,
                'status'     => $cart_order->status,
                'gateway'    => $cart_order->pay_method,
            ],
            4
        );
    }
}

$data['order_id'] = $order_post_id;
$data['amount'] = $cart_order->amount;
if ($order_post_id) {
    $data['ppcart_access'] = PPCart_Order::ensure_invoice_access_token($order_post_id);
}


$order_info = $cart_order->get_data();

if ($ppcart_product->upsell_path && ! empty($ppcart_product->form_action)) {
    $data['formAction'] = apply_filters('ppcart_host_purchase_url', $ppcart_product->form_action, $order_post_id, $ppcart_product_id);
    $data['formAction'] = add_query_arg(PPCart_Order::access_arg($order_post_id), $data['formAction']);
} elseif (!$ppcart_product->upsell_path && $ppcart_product->confirmation != 'redirect') {
    $data['formAction'] = PPCart_Order::confirmation_url($ppcart_product->thanks_url, $order_post_id);
    $data['formAction'] = apply_filters('ppcart_host_purchase_url', $data['formAction'], $order_post_id, $ppcart_product_id);
}

if (isset($ppcart_product->redirect_url) && !$ppcart_product->upsell_path) {
    $redirect = esc_url_raw(ppcart_personalize($ppcart_product->redirect_url, $order_info, 'urlencode'));
    $data['redirect'] = $redirect;
}

wp_send_json($data);
