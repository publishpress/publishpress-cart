<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_currency, $ppcart_debug_logger;
$pwyw_post = filter_input_array(
    INPUT_POST,
    [
        'pwyw_amount' => [
            'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'flags'  => FILTER_REQUIRE_ARRAY,
        ],
    ]
);
$pwyw_amounts = isset($pwyw_post['pwyw_amount']) ? ppcart_parse_pwyw_amounts($pwyw_post['pwyw_amount'], false) : [];

$ppcart_debug_logger->log_event(
    'checkout.stripe_subscription.creating',
    'Creating Stripe subscription for checkout.',
    [
        'order_id'        => (int) $order->id,
        'subscription_id' => (int) $sub->id,
        'product_id'      => (int) $sub->product_id,
    ]
);

if (! $this->is_connect_destination_configured()) {
    $ppcart_debug_logger->log_debug('Stripe Connect destination is missing or invalid during subscription creation.', 4);
    wp_send_json_error([ 'error' => $this->get_connect_configuration_error_message() ]);
}

$apikey = $ppcart_stripe['sk'];
$stripe = ppcart_stripe_client($apikey);

// Create the subscription
$args = ppcart_stripe_subscription_on_session_args([
    'customer' => $order->customer_id,
    'items' => [],
    'trial_from_plan' => false,
    'proration_behavior' => 'none',
    'metadata' => [
        'ppcart_subscription_id' => $sub->id,
        'origin' => get_site_url(),
    ],
]);

$addon = 0;

$tax_rates = [];
if (!empty($sub->tax_rate) && !empty($sub->stripe_tax_id)) {
    if (isset($sub->tax_data->redeem_vat) && $sub->tax_data->redeem_vat) {
        try {
            $coupon = $stripe->coupons->create(['amount_off' => ppcart_price_in_cents($sub->tax_amount, $ppcart_currency),
            'currency' => $ppcart_currency,
            'name' => get_option('_ppcart_vat_reverse_charge', "VAT Reversal"),
            'duration' => 'forever',
            'max_redemptions' => 1]);
            $args['coupon'] = $coupon->id;
        } catch (Exception $e) {
            wp_send_json_error([ 'error' => sanitize_text_field($e->getMessage()) ]);
        }
    } else {
        $tax_rates = ['tax_rates' => [$sub->stripe_tax_id]];
    }
}

// calculate addons and discount
if ($sub->cancel_at) {
    $args['cancel_at'] = $sub->cancel_at;
}

// Add sign up fee
if ($sub->sign_up_fee) {
    $addon += $order->plan->fee;
}

// is main order a subscription?
if ($order->plan->type == 'recurring') {
    if (isset($order->plan->recurring_pwyw) && $order->plan->recurring_pwyw == '1' && isset($order->plan->name_your_own_price_text_recurring) && isset($pwyw_amounts[ $order->plan->option_id ]) && (float) $pwyw_amounts[ $order->plan->option_id ] >= $order->plan->price) {
        // Create a new Price for PWYW
        $price = $stripe->prices->create([
            'unit_amount' => ppcart_price_in_cents($order->plan->initial_payment, $ppcart_currency),
            'currency' => $ppcart_currency,
            'recurring' => [
                'interval' => $order->plan->interval,
                'interval_count' => $order->plan->frequency,
            ],
            'product' => ppcart_get_post_meta($order->product_id, 'stripe_prod_id', true),
            'metadata' => ['ppcart_option_id' => $order->plan->option_id],
        ]);
        $item_args = array_merge(['price' => $price->id], $tax_rates);
    } else {
        $item_args = array_merge(['price' => $order->plan->stripe_id], $tax_rates);
    }
    if ($sub->quantity > 1) {
        $item_args['quantity'] = $sub->quantity;
    }
    $args['items'][] = $item_args;
} else {
    $addon += $order->plan->price;
}

// process order bumps
if (is_array($order->order_bumps)) {
    foreach ($order->order_bumps as $bump) {
        if (isset($bump['plan']) && $bump['plan']->type == 'recurring') {
            if (isset($bump['plan']->recurring_pwyw) && $bump['plan']->recurring_pwyw == '1' && isset($bump['plan']->name_your_own_price_text_recurring) && isset($pwyw_amounts[ $bump['plan']->option_id ]) && (float) $pwyw_amounts[ $bump['plan']->option_id ] >= $bump['plan']->price) {
                $price = $stripe->prices->create([
                    'unit_amount' => ppcart_price_in_cents($bump['plan']->initial_payment, $ppcart_currency),
                    'currency' => $ppcart_currency,
                    'recurring' => [
                        'interval' => $bump['plan']->interval,
                        'interval_count' => $bump['plan']->frequency,
                    ],
                    'product' => ppcart_get_post_meta($bump['id'], 'stripe_prod_id', true),
                    'metadata' => ['ppcart_option_id' => $bump['plan']->option_id],
                ]);
                $item_args = array_merge(['price' => $price->id], $tax_rates);
            } else {
                $item_args = array_merge(['price' => $bump['plan']->stripe_id], $tax_rates);
            }
            $args['items'][] = $item_args;
        } else {
            $addon += $bump['amount'];
        }
    }
}

$args = apply_filters('ppcart_stripe_subscription_invoice_args', $args, [
    'order'     => $order,
    'sub'       => $sub,
    'addon'     => $addon,
    'tax_rates' => $tax_rates,
    'currency'  => $ppcart_currency,
]);

$args = $this->add_connect_args_to_subscription($args);

$args = apply_filters('ppcart_checkout_stripe_subscription_args', $args, $order, $sub);
if (! is_array($args)) {
    wp_send_json_error([ 'error' => __('This checkout could not be started. Please contact the site administrator.', 'publishpress-cart') ]);
}
$args = ppcart_stripe_subscription_on_session_args($args);

if (! ppcart_stripe_connect_fee_is_valid($args)) {
    $ppcart_debug_logger->log_event(
        'checkout.stripe_subscription.invalid_connect_fee',
        'Stripe subscription Connect fee was missing or invalid after ppcart_checkout_stripe_subscription_args.',
        [
            'order_id'        => (int) $order->id,
            'subscription_id' => (int) $sub->id,
            'product_id'      => (int) $sub->product_id,
        ],
        4
    );
    wp_send_json_error([ 'error' => __('This checkout could not be started. Please contact the site administrator.', 'publishpress-cart') ]);
}

$ppcart_debug_logger->log_event(
    'checkout.stripe_subscription.request_prepared',
    'Stripe subscription request prepared.',
    [
        'order_id'        => (int) $order->id,
        'subscription_id' => (int) $sub->id,
        'product_id'      => (int) $sub->product_id,
        'currency'        => $ppcart_currency,
        'item_count'      => count($args['items']),
        'has_trial'       => ! empty($args['trial_period_days']),
        'has_coupon'      => ! empty($args['coupon']),
    ]
);


try {
    $subscription = $stripe->subscriptions->create($args);
} catch (Exception $e) {
    $ppcart_debug_logger->log_event(
        'checkout.stripe_subscription.failed',
        'Stripe subscription creation failed: ' . $e->getMessage(),
        [
            'order_id'        => (int) $order->id,
            'subscription_id' => (int) $sub->id,
            'product_id'      => (int) $sub->product_id,
        ],
        4
    );
    wp_send_json_error([ 'error' => sanitize_text_field($e->getMessage()) ]);
}

if (isset($subscription->id)) {
    $ppcart_debug_logger->log_event(
        'checkout.stripe_subscription.created',
        'Stripe subscription created successfully.',
        [
            'order_id'               => (int) $order->id,
            'subscription_id'        => (int) $sub->id,
            'stripe_subscription_id' => $subscription->id,
            'stripe_status'          => $subscription->status,
        ],
        0
    );
    return $subscription;
}

return false;
