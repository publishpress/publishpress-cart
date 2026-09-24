<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product, $ppcart_debug_logger;
$nonce         = ppcart_filter_input(INPUT_POST, 'ppcart-nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$product_id_in = ppcart_filter_input(INPUT_POST, 'ppcart_product_id', FILTER_VALIDATE_INT);
$nonce         = is_string($nonce) ? sanitize_text_field($nonce) : '';
$product_id_in = (false !== $product_id_in && null !== $product_id_in) ? absint($product_id_in) : 0;

$ppcart_debug_logger->log_event(
    'checkout.validation.started',
    'Checkout form validation started.',
    [
        'product_id' => $product_id_in,
    ]
);

if (! ppcart_verify_nonce($nonce, 'ppcart_purchase_nonce')) {
    wp_send_json_error([ 'error' => __('Invalid Request', 'publishpress-cart') ]);
}

if (! $product_id_in) {
    wp_send_json_error(
        [
            'error' => __('There was a problem with your submission, please refresh the page and try again.', 'publishpress-cart'),
        ]
    );
}

if (!$ppcart_product) {
    $ppcart_product_id = $product_id_in;
    $ppcart_product = ppcart_setup_product($ppcart_product_id);
}

// Read only the checkout fields declared for this product; the nonce is verified above.
$posted_values = ppcart_parse_checkout_request(null, $ppcart_product);

$required = [];
$validation_errors = [];
$messages = [];

if ($ppcart_product->terms_url) {
    $required[] = 'ppcart_accept_terms';
}
if ($ppcart_product->privacy_url) {
    $required[] = 'ppcart_accept_privacy';
}

$ppcart_product->optin_required ??= false;

if ($ppcart_product->show_optin_cb) {
    $req_consent = apply_filters('ppcart_consent_required', $ppcart_product->optin_required, $ppcart_product);
    if ($req_consent) {
        $required[] = 'ppcart_consent';
    }
}

$defaultfields = [
    'firstname' => ['name' => 'first_name','required' => true],
    'lastname' => ['name' => 'last_name','required' => true],
    'email' => ['name' => 'email','required' => true],
    'phone' => ['name' => 'phone','required' => false],
    'company' => ['name' => 'company','required' => false],
];

$address_fields = [
    'country' => ['name' => 'country','required' => true],
    'address1' => ['name' => 'address1','required' => true],
    'city' => ['name' => 'city','required' => true],
    'state' => ['name' => 'state','required' => true],
    'zip' => ['name' => 'zip','required' => true],
];

// default fields
$defaultfields = apply_filters('ppcart_order_form_fields', $defaultfields, $ppcart_product);
foreach ($defaultfields as $k => $field) {
    if (isset($field['required']) && $field['required']) {
        $required[] = $field['name'];
    }
}

// address fields
if (isset($ppcart_product->show_address_fields)) {
    $address_fields = apply_filters('ppcart_order_form_address_fields', $address_fields, $ppcart_product);
    foreach ($address_fields as $k => $field) {
        if (isset($field['required']) && $field['required']) {
            $required[] = $field['name'];
        }
    }
}

// custom fields
$required = apply_filters('ppcart_validate_custom_fields', $required, $ppcart_product);

// do validation
if (!empty($required)) {
    foreach ($required as $key) {
        if (!is_array($key)) {
            $value = $posted_values[ $key ] ?? '';
            if ('' === trim((string) $value)) {
                $validation_errors[] = ['field' => $key, 'message' => __("This field is required", "publishpress-cart")];
            }
        } else {
            foreach ($key as $k) {
                $custom_value = $posted_values['ppcart_custom_fields'][ $k ] ?? '';
                if (empty($custom_value)) {
                    $validation_errors[] = ['field' => 'ppcart_custom_fields[' . $k . ']', 'message' => __("This field is required", "publishpress-cart")];
                }
            }
        }
    }
}

// add error messages
if (!empty($validation_errors)) {
    $messages[] = '• ' . __("Required fields missing", "publishpress-cart");
}

// check PWYW
if (isset($posted_values['ppcart_product_option'])) {
    $ppcart_option_id = sanitize_text_field($posted_values['ppcart_product_option']);
    $sale = (isset($posted_values['on-sale']) && ppcart_is_prod_on_sale()) ? 1 : 0;
    $plan = ppcart_plan($ppcart_option_id, $sale);

    if (!$plan) {
        $messages[] = '• ' . __("Something went wrong, please refresh the page and try again. ", "publishpress-cart");
    }

    $plan->type ??= '';

    if ($plan->type == 'pwyw') {
        $pwyw_amount = isset($posted_values['pwyw_amount'][ $ppcart_option_id ]) ? (float) $posted_values['pwyw_amount'][ $ppcart_option_id ] : 0;
        if ($pwyw_amount < (float) $plan->price) {
            $price = ppcart_format_price($plan->price, $html = false);
            /* translators: %s: minimum amount. */
            $messages[] = '• ' . sprintf(__("Please enter an amount greater than or equal to %s", "publishpress-cart"), html_entity_decode($price));
            /* translators: %s: minimum amount. */
            $validation_errors[] = ['field' => 'pwyw_amount[' . $ppcart_option_id . ']', 'message' => sprintf(__("Please enter an amount greater than or equal to %s", "publishpress-cart"), ppcart_format_price($plan->price))];
        }
    }
}

// check email
if (isset($posted_values['email'])) {
    if (! is_email(sanitize_email($posted_values['email']))) {
        $messages[] = '• ' . __("Invalid email address", "publishpress-cart");
        $validation_errors[] = ['field' => 'email', 'message' => __('Enter a valid email', "publishpress-cart")];
    }
}

$messages = apply_filters('ppcart_checkout_form_validation_messafes', $messages);

if (!empty($messages)) {
    $ppcart_debug_logger->log_event(
        'checkout.validation.failed',
        'Checkout form validation failed.',
        [
            'product_id' => $product_id_in,
            'messages'   => $messages,
            'fields'     => $validation_errors,
        ],
        4
    );

    wp_send_json_error([
        'error' => __("There was a problem with your submission, please check your info and try again:", "publishpress-cart") . "\n" . implode("\n", $messages),
        'fields' => $validation_errors,
    ]);
} else {
    $ppcart_debug_logger->log_event(
        'checkout.validation.passed',
        'Checkout form validation passed.',
        [
            'product_id' => $product_id_in,
        ],
        0
    );
}
