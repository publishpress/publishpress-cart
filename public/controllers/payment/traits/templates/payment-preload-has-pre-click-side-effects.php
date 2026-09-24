<?php

if (! defined('ABSPATH')) {
    exit;
}


$checked_hooks = [
    'ppcart_before_create_stripe_payment_intent' => [],
    'ppcart_before_create_main_order'            => [
        [
            'class'  => PPCart_Public_Checkout_Controller::class,
            'method' => 'check_product_purchase_limit',
        ],
        [
            'class'  => PPCart_Public_Checkout_Controller::class,
            'method' => 'validate_order_form_submission',
        ],
    ],
    'ppcart_customer_defaults'                   => [],
    'ppcart_plan_at_checkout'                    => [],
    'ppcart_after_setup_atts_from_post'          => [],
    'ppcart_order_pre_calculate_tax'             => [],
    'ppcart_charge_amount'                       => [],
    'ppcart_after_load_from_post'                => [],
    'ppcart_after_order_load_from_post'          => [],
    'ppcart_create_stripe_intent'                => [],
];

foreach ($checked_hooks as $hook_name => $allowed_callbacks) {
    if ($this->has_unexpected_hook_callbacks($hook_name, $allowed_callbacks)) {
        return true;
    }
}

return false;
