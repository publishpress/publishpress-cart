<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_product, $ppcart_debug_logger;
$ppcart_product_id = ppcart_filter_input(INPUT_POST, 'ppcart_product_id', FILTER_VALIDATE_INT);
$nonce         = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$ppcart_product_id = (false !== $ppcart_product_id && null !== $ppcart_product_id) ? absint($ppcart_product_id) : 0;
$nonce         = is_string($nonce) ? sanitize_text_field($nonce) : '';

$ppcart_debug_logger->log_event(
    'checkout.setup_intent.creating',
    'Creating Stripe SetupIntent for Payment Element subscription checkout.',
    [
        'product_id' => $ppcart_product_id,
    ]
);

if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    $ppcart_debug_logger->log_event(
        'checkout.security.failed',
        'Checkout security check failed before creating Stripe SetupIntent.',
        [
            'product_id' => $ppcart_product_id,
            'check'      => 'ppcart_purchase_nonce:setup_intent',
        ],
        4
    );
    wp_send_json_error(['error' => __('Invalid Request', 'publishpress-cart')]);
}

if (! $this->is_connect_destination_configured()) {
    $ppcart_debug_logger->log_debug('Stripe Connect destination is missing or invalid during setup intent creation.', 4);
    wp_send_json_error(['error' => $this->get_connect_configuration_error_message()]);
}

$ppcart_product = ppcart_setup_product($ppcart_product_id);

// Read only the checkout fields declared for this product; the nonce is verified above.
$posted_values = ppcart_parse_checkout_request(null, $ppcart_product);

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

$cached_customer_id = $this->get_cached_stripe_customer_id($email, $ppcart_stripe['mode'] ?? '');

if ($cached_customer_id) {
    $customer = (object) ['id' => $cached_customer_id];
} else {
    $customer = $this->get_or_create_stripe_customer($stripe, $customer_args, $email);
}

if (! $customer) {
    wp_send_json_error(['error' => __('There was an error, please try again.', 'publishpress-cart')]);
}

$_POST['customerId'] = $customer->id;

$setup_intent_args = [
    'customer'             => $customer->id,
    'usage'                => 'off_session',
    'payment_method_types' => ['card', 'link'],
    'metadata'             => [
        'ppcart_product_id' => $ppcart_product_id,
        'origin'        => get_site_url(),
    ],
];

try {
    $intent = $stripe->setupIntents->create($setup_intent_args);
} catch (Exception $e) {
    $ppcart_debug_logger->log_event(
        'checkout.setup_intent.failed',
        'Stripe SetupIntent creation failed: ' . $e->getMessage(),
        [
            'product_id' => $ppcart_product_id,
        ],
        4
    );

    wp_send_json_error(['error' => sanitize_text_field($e->getMessage())]);
}

$ppcart_debug_logger->log_event(
    'checkout.setup_intent.created',
    'Stripe SetupIntent created successfully.',
    [
        'product_id'      => $ppcart_product_id,
        'setup_intent_id' => $intent->id,
    ],
    0
);

wp_send_json([
    'clientSecret' => $intent->client_secret,
    'intent_id'    => $intent->id,
    'customer_id'  => $customer->id,
    'prod_id'      => $ppcart_product_id,
    'is_setup_intent' => '1',
]);
