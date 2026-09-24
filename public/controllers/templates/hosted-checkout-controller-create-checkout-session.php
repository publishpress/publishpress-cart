<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_currency, $ppcart_product, $ppcart_debug_logger;
$ppcart_product_id = ppcart_filter_input(INPUT_POST, 'ppcart_product_id', FILTER_VALIDATE_INT);
$nonce         = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$ppcart_product_id = (false !== $ppcart_product_id && null !== $ppcart_product_id) ? absint($ppcart_product_id) : 0;
$nonce         = is_string($nonce) ? sanitize_text_field($nonce) : '';

$ppcart_debug_logger->log_event(
    'checkout.hosted_session.creating',
    'Creating Stripe Checkout Session for hosted checkout.',
    [
        'product_id' => $ppcart_product_id,
    ]
);

if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    $ppcart_debug_logger->log_event(
        'checkout.security.failed',
        'Checkout security check failed before creating Stripe Checkout Session.',
        [
            'product_id' => $ppcart_product_id,
            'check'      => 'ppcart_purchase_nonce:hosted',
        ],
        4
    );
    wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
}

if (empty($ppcart_stripe['is_hosted_checkout'])) {
    wp_send_json_error([ 'error' => __('Hosted Checkout is not enabled.', 'publishpress-cart') ]);
}

if (! $this->is_connect_destination_configured()) {
    $ppcart_debug_logger->log_debug('Stripe Connect destination is missing or invalid during hosted checkout session creation.', 4);
    wp_send_json_error([ 'error' => $this->get_connect_configuration_error_message() ]);
}

// setup product info
$ppcart_product = ppcart_setup_product($ppcart_product_id);

// Read only the checkout fields declared for this product; the nonce is verified above.
$posted_values = ppcart_parse_checkout_request(null, $ppcart_product);

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
    $customer_args['address'] = [
        'line1'       => sanitize_text_field($posted_values['address1']),
        'postal_code' => sanitize_text_field($posted_values['zip']),
        'city'        => sanitize_text_field($posted_values['city']),
        'state'       => sanitize_text_field($posted_values['state']),
        'country'     => sanitize_text_field($posted_values['country']),
    ];
}

$customer = $this->get_or_create_stripe_customer($stripe, $customer_args, $email);
$_POST['customerId'] = $customer->id;

// Neutralize bump data before load_from_post(); hosted mode doesn't support bumps.
unset($_POST['ppcart-orderbump']);

// setup order info
$ppcart_order = new PPCart_Order();
$ppcart_order->load_from_post();
$ppcart_order = apply_filters('ppcart_after_order_load_from_post', $ppcart_order);

$ppcart_order->order_bumps = false;

// stripe only fields
if ($ppcart_order->pay_method == 'stripe' && $ppcart_order->amount && isset($ppcart_stripe['mode'])) {
    $ppcart_order->gateway_mode = $ppcart_stripe['mode'];
}
$ppcart_order->customer_id = $customer->id;

$descriptor = get_option('_ppcart_stripe_descriptor', false);
if (! $descriptor) {
    $descriptor = get_bloginfo('name') ?? ppcart_get_public_product_name($ppcart_product_id);
}
$descriptor = preg_replace("/[^0-9a-zA-Z ]/", '', substr($descriptor, 0, 22));

// Persist a pending order that the webhook will finalize.
$order_id = $this->store_stripe_owned_record($ppcart_order);

if (! $order_id) {
    $ppcart_debug_logger->log_event(
        'checkout.hosted_session.failed',
        'Pending order could not be saved before creating the Stripe Checkout Session.',
        [
            'product_id' => $ppcart_product_id,
        ],
        4
    );
    wp_send_json_error([ 'error' => __('There was an error, please try again.', 'publishpress-cart') ]);
}

try {
    $temp_order_token = bin2hex(random_bytes(32));
} catch (Exception $e) {
    $temp_order_token = wp_generate_password(64, false, false);
}
ppcart_update_post_meta($order_id, 'temp_order_token', $temp_order_token);

$return_url = isset($posted_values['ppcart_page_url']) ? esc_url_raw($posted_values['ppcart_page_url']) : '';
// This runs over admin-ajax, so REQUEST_URI is no fallback; use the product page.
if ('' === $return_url) {
    $permalink  = get_permalink($ppcart_product_id);
    $return_url = (is_string($permalink) && '' !== $permalink) ? $permalink : home_url('/');
}
// ppcart_page_url is a REQUEST_URI, which is relative to the domain root, not to the
// site. Prefixing home_url() would repeat the subdirectory of a subdirectory
// install, so build the absolute URL from the origin only.
if ('' !== $return_url && ! preg_match('#^https?://#i', $return_url)) {
    $home  = wp_parse_url(home_url('/'));
    $origin = ($home['scheme'] ?? 'https') . '://' . ($home['host'] ?? '');
    if (! empty($home['port'])) {
        $origin .= ':' . $home['port'];
    }
    $return_url = $origin . '/' . ltrim($return_url, '/');
}
$return_url = remove_query_arg([ 'ppcart_session', 'ppcart_hosted', 'ppcart_retry' ], $return_url);

$success_url = add_query_arg(
    [
        'ppcart_session' => '{CHECKOUT_SESSION_ID}',
        'ppcart_hosted'  => 'success',
    ],
    $return_url
);
// add_query_arg url-encodes the Stripe placeholder braces; restore them.
$success_url = str_replace('%7BCHECKOUT_SESSION_ID%7D', '{CHECKOUT_SESSION_ID}', $success_url);
$cancel_url  = add_query_arg('ppcart_hosted', 'cancel', $return_url);

$metadata = [
    'ppcart_product_id' => $ppcart_product_id,
    'ppcart_order_id'   => $order_id,
    'origin'        => get_site_url(),
];

$args = [
    'customer'    => $customer->id,
    'success_url' => $success_url,
    'cancel_url'  => $cancel_url,
    'metadata'    => $metadata,
];

if ($ppcart_order->plan->type == 'recurring') {
    // Subscription mode: reference the pre-synced Stripe price for this plan.
    $stripe_price_id = isset($ppcart_order->plan->stripe_id) ? sanitize_text_field($ppcart_order->plan->stripe_id) : '';

    if ('' === $stripe_price_id) {
        $ppcart_debug_logger->log_event(
            'checkout.hosted_session.failed',
            'Recurring plan is missing a synced Stripe price for hosted checkout.',
            [
                'product_id' => $ppcart_product_id,
                'order_id'   => (int) $order_id,
            ],
            4
        );
        wp_send_json_error([ 'error' => __('This subscription is not ready for checkout, please contact the site administrator.', 'publishpress-cart') ]);
    }

    $item_args = [
        'price'    => $stripe_price_id,
        'quantity' => ($ppcart_order->quantity > 1) ? (int) $ppcart_order->quantity : 1,
    ];

    $args['mode']       = 'subscription';
    $args['line_items'] = [ $item_args ];

    $subscription_data = [
        'metadata' => $metadata,
    ];
    $subscription_data = $this->add_connect_args_to_subscription($subscription_data);
    $args['subscription_data'] = $subscription_data;
} else {
    // One-time / PWYW: resolve the amount server-side.
    $amount_for_stripe = ppcart_price_in_cents($ppcart_order->amount, $ppcart_currency);

    $args['mode']       = 'payment';
    $args['line_items'] = [
        [
            'quantity'   => 1,
            'price_data' => [
                'currency'     => $ppcart_currency,
                'unit_amount'  => $amount_for_stripe,
                'product_data' => [
                    'name' => ppcart_get_public_product_name($ppcart_product_id),
                ],
            ],
        ],
    ];

    $payment_intent_data = [
        'statement_descriptor_suffix' => $descriptor,
        'description'                  => ppcart_get_public_product_name($ppcart_product_id),
        'metadata'                     => $metadata,
    ];

    // Nest Connect application_fee_amount / transfer_data under payment_intent_data.
    $connect_args = $this->add_connect_args_to_payment_intent([], $amount_for_stripe);
    if (isset($connect_args['application_fee_amount'])) {
        $payment_intent_data['application_fee_amount'] = $connect_args['application_fee_amount'];
    }
    if (isset($connect_args['transfer_data'])) {
        $payment_intent_data['transfer_data'] = $connect_args['transfer_data'];
    }

    $args['payment_intent_data'] = $payment_intent_data;
}

// Pro maps applied coupons to their synced Stripe coupon ids via a `discounts` array.
$args = apply_filters('ppcart_stripe_checkout_session_args', $args, $ppcart_order);

if (! ppcart_stripe_connect_fee_is_valid($args, isset($amount_for_stripe) ? $amount_for_stripe : 0)) {
    $ppcart_debug_logger->log_event(
        'checkout.hosted_session.invalid_connect_fee',
        'Stripe Checkout Session Connect fee was missing or invalid after ppcart_stripe_checkout_session_args.',
        [
            'product_id' => $ppcart_product_id,
            'order_id'   => (int) $order_id,
            'mode'       => isset($args['mode']) ? $args['mode'] : '',
        ],
        4
    );
    wp_send_json_error([ 'error' => __('This checkout could not be started. Please contact the site administrator.', 'publishpress-cart') ]);
}

$ppcart_debug_logger->log_event(
    'checkout.hosted_session.request_prepared',
    'Stripe Checkout Session request prepared.',
    [
        'product_id' => $ppcart_product_id,
        'order_id'   => (int) $order_id,
        'mode'       => $args['mode'],
    ]
);



try {
    $session = $stripe->checkout->sessions->create($args);
} catch (Exception $e) {
    $ppcart_debug_logger->log_event(
        'checkout.hosted_session.failed',
        'Stripe Checkout Session creation failed: ' . $e->getMessage(),
        [
            'product_id' => $ppcart_product_id,
            'order_id'   => (int) $order_id,
        ],
        4
    );
    wp_send_json_error([ 'error' => sanitize_text_field($e->getMessage()) ]);
}

// Link the session to the pending order and store the reconciliation payload.
// Order meta is the durable fallback when the transient is missing/evicted.
ppcart_update_post_meta($order_id, 'checkout_session_id', sanitize_text_field($session->id));
if (isset($ppcart_stripe['mode']) && '' !== $ppcart_stripe['mode']) {
    ppcart_update_post_meta($order_id, 'gateway_mode', sanitize_text_field($ppcart_stripe['mode']));
}
set_transient(
    'ppcart_hosted_session_' . sanitize_key($session->id),
    [
        'order_id'         => (int) $order_id,
        'product_id'       => (int) $ppcart_product_id,
        'temp_order_token' => $temp_order_token,
        'gateway_mode'     => isset($ppcart_stripe['mode']) ? sanitize_text_field($ppcart_stripe['mode']) : '',
    ],
    DAY_IN_SECONDS
);

$ppcart_debug_logger->log_event(
    'checkout.hosted_session.created',
    'Stripe Checkout Session created successfully.',
    [
        'product_id' => $ppcart_product_id,
        'order_id'   => (int) $order_id,
        'session_id' => $session->id,
    ],
    0
);

wp_send_json_success([ 'url' => $session->url ]);
