<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_product, $ppcart_debug_logger;
$nonce = isset($_POST['ppcart-nonce']) && is_string($_POST['ppcart-nonce']) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reading the nonce field for immediate verification.
    ? sanitize_text_field(wp_unslash($_POST['ppcart-nonce'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The value is verified immediately below.
    : '';
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The order ID is required to build an upsell/downsell nonce action.
$ppcart_order_post = isset($_POST['ppcart-order']) ? absint(sanitize_text_field(wp_unslash($_POST['ppcart-order']))) : 0;
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The offer type is required to build an upsell/downsell nonce action.
$is_downsell = isset($_POST['downsell']);
$nonce_action = 'ppcart_purchase_nonce';

if ($ppcart_order_post) {
    $nonce_action = 'ppcart_' . ($is_downsell ? 'downsell' : 'upsell') . '-' . $ppcart_order_post;
}

if (! ppcart_verify_nonce($nonce, $nonce_action)) {
    if (! $ppcart_order_post) {
        $ppcart_debug_logger->log_event(
            'checkout.security.failed',
            'Checkout security check failed before saving order.',
            [
                'check' => 'ppcart_purchase_nonce:2',
            ],
            4
        );
    }

    wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
}

// order id only present for upsells
if ($ppcart_order_post) {
    $order_info = (array) ppcart_setup_order($ppcart_order_post);

    $order_info = apply_filters('ppcart_before_order_save', $order_info, $subscription, $this);

    $order_post_id = apply_filters('ppcart_order_save_override', null, $order_info, $subscription, $this->get_stripe_save_helper());

    if (null !== $order_post_id) {
        return $order_post_id;
    }
} else {
    $post_data = ppcart_filter_input_array(
        INPUT_POST,
        [
            'ppcart_product_id'   => FILTER_VALIDATE_INT,
            'ppcart_product_option' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'ppcart_temp_order_id' => FILTER_VALIDATE_INT,
            'intent_id'       => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        ]
    );
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
        // The Stripe customer comes from the server-side checkout binding for this intent, never from POST.
        $ppcart_checkout_ref = ppcart_filter_input(INPUT_POST, 'ppcart_checkout_ref', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $_POST['customerId'] = PPCart_Stripe_Checkout_Customer::customer_id_for_order(
            is_string($ppcart_checkout_ref) ? sanitize_text_field($ppcart_checkout_ref) : '',
            $intent_id,
            $ppcart_stripe['mode'] ?? ''
        );

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
    $order_post_id = ppcart_store_stripe_owned_record($cart_order);

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
