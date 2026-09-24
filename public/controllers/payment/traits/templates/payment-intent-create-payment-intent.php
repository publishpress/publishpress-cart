<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_currency, $ppcart_product, $ppcart_debug_logger;
$ppcart_product_id = ppcart_filter_input(INPUT_POST, 'ppcart_product_id', FILTER_VALIDATE_INT);
$nonce         = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$preload_intent = ppcart_filter_input(INPUT_POST, 'ppcart_preload_intent', FILTER_VALIDATE_INT);
$ppcart_product_id = (false !== $ppcart_product_id && null !== $ppcart_product_id) ? absint($ppcart_product_id) : 0;
$nonce         = is_string($nonce) ? sanitize_text_field($nonce) : '';
$preload_intent = (false !== $preload_intent && null !== $preload_intent) ? absint($preload_intent) : 0;

$ppcart_debug_logger->log_event(
    'checkout.payment_intent.creating',
    'Creating Stripe PaymentIntent for checkout.',
    [
        'product_id' => $ppcart_product_id,
    ]
);

if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    $ppcart_debug_logger->log_event(
        'checkout.security.failed',
        'Checkout security check failed before creating Stripe PaymentIntent.',
        [
            'product_id' => $ppcart_product_id,
            'check'      => 'ppcart_purchase_nonce:1',
        ],
        4
    );
    wp_send_json_error(['error' => __('Invalid Request', 'publishpress-cart')]);
}

if (! $this->is_connect_destination_configured()) {
    $ppcart_debug_logger->log_debug('Stripe Connect destination is missing or invalid during payment intent creation.', 4);
    wp_send_json_error(['error' => $this->get_connect_configuration_error_message()]);
}

// setup product info
$ppcart_product = ppcart_setup_product($ppcart_product_id);

if ($preload_intent && ! $this->can_preload_payment_intent($ppcart_product)) {
    wp_send_json_error(['error' => __('Payment preparation is unavailable for this checkout.', 'publishpress-cart')]);
}

// Read only the checkout fields declared for this product; the nonce is verified above.
$posted_values = ppcart_parse_checkout_request(null, $ppcart_product);

// Mount request: skip form validation so the element can render; ppcart_validate still runs on submit.
$pe_mount = ppcart_filter_input(INPUT_POST, 'ppcart_pe_mount', FILTER_VALIDATE_INT);
$pe_mount = (false !== $pe_mount && null !== $pe_mount) ? absint($pe_mount) : 0;
if ($pe_mount && ! empty($ppcart_stripe['is_payment_element'])) {
    $this->remove_hook_callbacks_by_class('ppcart_before_create_main_order', PPCart_Public_Checkout_Controller::class, 'validate_order_form_submission');
    $this->remove_hook_callbacks_by_class('ppcart_before_create_main_order', PPCart_Public_Checkout_Controller::class, 'check_product_purchase_limit');
}

do_action('ppcart_before_create_stripe_payment_intent');
do_action('ppcart_before_create_main_order');

$apikey = $ppcart_stripe['sk'];

$stripe = ppcart_stripe_client($apikey);

$email = isset($posted_values['email']) ? strtolower(sanitize_email($posted_values['email'])) : '';
$name = isset($posted_values['first_name']) ? sanitize_text_field($posted_values['first_name']) : '';
$last_name = isset($posted_values['last_name']) ? sanitize_text_field($posted_values['last_name']) : '';

$customer_args = [
    'email' => $email,
    'name'  => $name . ' ' . $last_name,
    'description' => $name . ' ' . $last_name,
];

if (isset($posted_values['phone'])) {
    $customer_args['phone'] = sanitize_text_field($posted_values['phone']);
}

if (isset($posted_values['country'], $posted_values['address1'], $posted_values['city'], $posted_values['state'], $posted_values['zip'])) {
    $country = sanitize_text_field($posted_values['country']);
    $address1 = sanitize_text_field($posted_values['address1']);
    $city = sanitize_text_field($posted_values['city']);
    $state = sanitize_text_field($posted_values['state']);
    $zip = sanitize_text_field($posted_values['zip']);

    $customer_args['address'] = [
        'line1' => $address1,
        'postal_code' => $zip,
        'city' => $city,
        'state' => $state,
        'country' => $country,
    ];
}

$customer_from_cache = false;
$cached_customer_id  = $this->get_cached_stripe_customer_id($email, $ppcart_stripe['mode'] ?? '');

if ($cached_customer_id) {
    $customer            = (object) ['id' => $cached_customer_id];
    $customer_from_cache = true;
} else {
    $customer = $this->get_or_create_stripe_customer($stripe, $customer_args, $email);
}

$_POST['customerId'] = $customer->id;

$ppcart_option_id = isset($posted_values['ppcart_product_option']) ? sanitize_text_field($posted_values['ppcart_product_option']) : '';

// setup order info
$ppcart_order = new PPCart_Order();
$ppcart_order->load_from_post();

$ppcart_order = apply_filters('ppcart_after_order_load_from_post', $ppcart_order);

// stripe only fields
if ($ppcart_order->pay_method == 'stripe' && $ppcart_order->amount && isset($ppcart_stripe['mode'])) {
    $ppcart_order->gateway_mode = $ppcart_stripe['mode'];
}

$amount = $ppcart_order->amount;
$sub_total = $ppcart_order->invoice_subtotal;
$tax_applied = false;
if ($ppcart_order->tax_amount > 0) {
    $tax_applied = true;
}

// Setup payment intent and return result
$amount_for_stripe = ppcart_price_in_cents($amount, $ppcart_currency);

$client_secret = '';
$intent_id = '';
$descriptor = get_option('_ppcart_stripe_descriptor', false);
if (!$descriptor) {
    $descriptor = get_bloginfo('name') ?? ppcart_get_public_product_name($ppcart_product_id);
}

$bump_plan = false;
if (is_array($ppcart_order->order_bumps) && isset($ppcart_order->order_bumps['main'])) {
    // does main order bump have a subscription?
    $bump = $ppcart_order->order_bumps['main'];
    if (isset($bump['plan']) && $bump['plan']->type == 'recurring') {
        $bump_plan = true;
    }
}

$order_id = "";
$temp_order_token = '';
$create_intent = apply_filters('ppcart_create_stripe_intent', (($ppcart_order->plan->type == 'free' || $ppcart_order->plan->type == 'one-time' || $ppcart_order->plan->type == 'pwyw') && !$bump_plan), $ppcart_order);
if ($create_intent) {
    $payment_intent_args = [
        'amount'        => $amount_for_stripe,
        'currency'      => $ppcart_currency,
        'statement_descriptor_suffix' => preg_replace("/[^0-9a-zA-Z ]/", '', substr($descriptor, 0, 22)),
        'description' => ppcart_get_public_product_name($ppcart_product_id),
        'customer' => $customer->id,
        'confirmation_method' => 'automatic',
        'setup_future_usage' => 'off_session',
        'metadata' => [
            'ppcart_product_id' => $ppcart_product_id,
            'origin' => get_site_url(),
        ],
    ];

    if (! empty($posted_values['ppcart_payment_method_id'])) {
        $payment_intent_args['payment_method'] = sanitize_text_field($posted_values['ppcart_payment_method_id']);
    }

    // Reuse-capable methods only — off-session upsells depend on setup_future_usage.
    if (! empty($ppcart_stripe['is_payment_element'])) {
        $payment_intent_args['payment_method_types'] = ['card', 'link'];
    }

    $payment_intent_args = $this->add_connect_args_to_payment_intent($payment_intent_args, $amount_for_stripe);

    if (! ppcart_stripe_connect_fee_is_valid($payment_intent_args, $amount_for_stripe)) {
        $ppcart_debug_logger->log_event(
            'checkout.payment_intent.invalid_connect_fee',
            'Stripe PaymentIntent Connect fee was missing or invalid.',
            [
                'product_id' => $ppcart_product_id,
            ],
            4
        );
        wp_send_json_error([ 'error' => __('This checkout could not be started. Please contact the site administrator.', 'publishpress-cart') ]);
    }

    $ppcart_debug_logger->log_event(
        'checkout.payment_intent.request_prepared',
        'Stripe PaymentIntent request prepared.',
        [
            'product_id'         => $ppcart_product_id,
            'amount'             => $amount_for_stripe,
            'currency'           => $ppcart_currency,
            'has_payment_method' => ! empty($payment_intent_args['payment_method']),
        ]
    );

    try {
        $intent = $stripe->paymentIntents->create($payment_intent_args);
    } catch (Exception $e) {
        if ($customer_from_cache && $this->is_missing_stripe_customer_exception($e)) {
            try {
                $customer                           = $this->get_or_create_stripe_customer($stripe, $customer_args, $email);
                $_POST['customerId']                = $customer->id;
                $ppcart_order->customer_id                 = $customer->id;
                $payment_intent_args['customer']    = $customer->id;
                $customer_from_cache                = false;
                $intent                             = $stripe->paymentIntents->create($payment_intent_args);
            } catch (Exception $e) {
                $ppcart_debug_logger->log_event(
                    'checkout.payment_intent.failed',
                    'Stripe PaymentIntent creation failed: ' . $e->getMessage(),
                    [
                        'product_id' => $ppcart_product_id,
                    ],
                    4
                );

                wp_send_json_error(['error' => sanitize_text_field($e->getMessage())]);
            }
        } else {
            $ppcart_debug_logger->log_event(
                'checkout.payment_intent.failed',
                'Stripe PaymentIntent creation failed: ' . $e->getMessage(),
                [
                    'product_id' => $ppcart_product_id,
                ],
                4
            );

            wp_send_json_error(['error' => sanitize_text_field($e->getMessage())]);
        }
    }

    $client_secret = $intent->client_secret;
    $intent_id = $intent->id;
    $ppcart_order->transaction_id = sanitize_text_field($intent_id);

    // No order row on mount; save_order_to_db creates it at submit.
    if ($pe_mount && ! empty($ppcart_stripe['is_payment_element'])) {
        $order_id = '';
    } else {
        $order_id = $this->store_stripe_owned_record($ppcart_order);
    }

    if ($order_id) {
        try {
            $temp_order_token = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $temp_order_token = wp_generate_password(64, false, false);
        }

        ppcart_update_post_meta($order_id, 'temp_order_token', $temp_order_token);
        if ($preload_intent) {
            ppcart_update_post_meta($order_id, 'preloaded_intent', '1');
            ppcart_update_post_meta($order_id, 'preloaded_intent_created', time());
        }
    }
}

if ($customer) {
    $ppcart_debug_logger->log_event(
        'checkout.payment_intent.created',
        'Stripe PaymentIntent created successfully.',
        [
            'product_id'          => $ppcart_product_id,
            'order_id'            => (int) $order_id,
            'payment_intent_id'   => $intent_id,
            'amount'              => $amount,
            'amount_stripe_units' => $amount_for_stripe,
            'currency'            => $ppcart_currency,
        ],
        0
    );
    $response = [
        'clientSecret' => $client_secret,
        'intent_id' => $intent_id,
        'customer_id' => $customer->id,
        'amount' => $amount,
        'sub_total' => $sub_total,
        'tax_applied' => $tax_applied,
        'ppcart_temp_order_id' => $order_id,
        'ppcart_temp_order_token' => $temp_order_token,
        'prod_id'   => $ppcart_product_id,
    ];

    if ($preload_intent) {
        $response['preloadedIntent'] = '1';
    }

    if ($order_id) {
        $response['ppcart_access'] = PPCart_Order::ensure_invoice_access_token($order_id);
    }

    if ($order_id && ! $ppcart_product->upsell_path && $ppcart_product->confirmation != 'redirect') {
        $response['formAction'] = PPCart_Order::confirmation_url($ppcart_product->thanks_url, $order_id);
        $response['formAction'] = apply_filters('ppcart_host_purchase_url', $response['formAction'], $order_id, $ppcart_product_id);

        if ($this->can_use_direct_confirmation($ppcart_product)) {
            $response['directConfirmation'] = '1';
        }
    }

    wp_send_json($response);
} else {
    $ppcart_debug_logger->log_event(
        'checkout.payment_intent.failed',
        'Stripe customer could not be created before checkout response.',
        [
            'product_id' => $ppcart_product_id,
        ],
        4
    );
    wp_send_json_error(['error' => 'There was an error, please try again.']);
}
