<?php

if (! defined('ABSPATH')) {
    exit;
}


$stripe_subscription_id = trim(sanitize_text_field((string) $stripe_subscription_id));
if (0 !== strpos($stripe_subscription_id, 'sub_')) {
    return new WP_Error('ppcart_import_invalid_id', __('Enter a Stripe subscription ID that starts with sub_.', 'publishpress-cart'));
}

$existing = PPCart_Subscription::get_by_sub_id($stripe_subscription_id);
if ($existing && $existing->id) {
    return new WP_Error(
        'ppcart_import_exists',
        /* translators: %d: local subscription ID */
        sprintf(__('This Stripe subscription is already subscription #%d.', 'publishpress-cart'), (int) $existing->id),
        [ 'subscription_id' => (int) $existing->id ]
    );
}

$import_mode = ppcart_normalize_stripe_mode($args['mode'] ?? '');

try {
    $stripe = self::get_client_for_mode($import_mode);
    $stripe_sub = $stripe->subscriptions->retrieve($stripe_subscription_id, [ 'expand' => [ 'customer' ] ]);
} catch (Throwable $e) {
    return new WP_Error('ppcart_import_stripe', $e->getMessage());
}

$item = self::path($stripe_sub, [ 'items', 'data', 0 ], null);
$price = self::get($item, 'price', null);
if (! $price) {
    $price = self::get($item, 'plan', null);
}
$price_id = (string) self::get($price, 'id', '');

$product_id = absint($args['product_id'] ?? 0);
if (! $product_id) {
    $product_id = self::find_product_for_stripe_price($price_id);
}
if (! $product_id) {
    foreach ([ 'ppcart_product_id', 'sc_product_id' ] as $key) {
        $candidate = absint(self::metadata($stripe_sub, $key, 0));
        if ($candidate && ppcart_is_product_post_type(get_post_type($candidate))) {
            $product_id = $candidate;
            break;
        }
    }
}
if (! $product_id || ! ppcart_is_product_post_type(get_post_type($product_id))) {
    return new WP_Error(
        'ppcart_import_product',
        /* translators: %s: Stripe price ID */
        sprintf(__('No product sells the Stripe price %s. Choose a product and import again.', 'publishpress-cart'), $price_id ? $price_id : '-')
    );
}

$customer = self::get($stripe_sub, 'customer', null);
$customer_id = is_string($customer) ? $customer : (string) self::get($customer, 'id', '');
$email = is_string($customer) ? '' : sanitize_email((string) self::get($customer, 'email', ''));
$name = is_string($customer) ? '' : sanitize_text_field((string) self::get($customer, 'name', ''));
$name_parts = $name ? explode(' ', $name, 2) : [ '', '' ];
$currency = strtoupper((string) self::get($stripe_sub, 'currency', self::get($price, 'currency', 'usd')));
$amount = ppcart_format_stripe_number(absint(self::get($price, 'unit_amount', self::get($price, 'amount', 0))) * max(1, absint(self::get($item, 'quantity', 1))), $currency);
$recurring = self::get($price, 'recurring', null);
$interval = (string) ($recurring ? self::get($recurring, 'interval', 'month') : self::get($price, 'interval', 'month'));
$frequency = absint($recurring ? self::get($recurring, 'interval_count', 1) : self::get($price, 'interval_count', 1));
$mapped = self::map_subscription($stripe_sub);
$user = $email ? get_user_by('email', $email) : false;

$sub = new PPCart_Subscription();
$sub->subscription_id = $stripe_subscription_id;
$sub->pay_method = 'stripe';
$sub->gateway_mode = self::get($stripe_sub, 'livemode', 'live' === $import_mode) ? 'live' : 'test';
$sub->status = $mapped['status'];
$sub->sub_status = $mapped['sub_status'];
$sub->sub_next_bill_date = $mapped['sub_next_bill_date'];
$sub->cancel_at = $mapped['cancel_at'];
$sub->product_id = $product_id;
$sub->product_name = get_the_title($product_id);
$sub->item_name = $sub->product_name;
$sub->sub_item_name = $sub->product_name;
$sub->plan_id = $price_id;
$sub->amount = $amount;
$sub->sub_amount = $amount;
$sub->main_offer_amt = $amount;
$sub->sub_interval = $interval;
$sub->sub_frequency = $frequency ? $frequency : 1;
$sub->sub_installments = -1;
$sub->quantity = max(1, absint(self::get($item, 'quantity', 1)));
$sub->currency = $currency;
$sub->customer_id = $customer_id;
$sub->email = $email;
$sub->customer_name = $name;
$sub->first_name = $name_parts[0];
$sub->firstname = $name_parts[0];
$sub->last_name = $name_parts[1] ?? '';
$sub->lastname = $name_parts[1] ?? '';
$sub->user_account = $user ? (int) $user->ID : 0;

$no_integrations = '__return_false';
add_filter('ppcart_trigger_subscription_integrations', $no_integrations, 99);
add_filter('ppcart_trigger_order_integrations', $no_integrations, 99);

try {
    $sub->store(false);
    if (! $sub->id) {
        return new WP_Error('ppcart_import_store', __('The subscription could not be saved.', 'publishpress-cart'));
    }

    $canceled_at = absint(self::get($stripe_sub, 'canceled_at', 0));
    if ($canceled_at) {
        ppcart_update_post_meta($sub->id, 'cancel_date', wp_date('Y-m-d', $canceled_at));
    }

    // Date the subscription by when it started in Stripe.
    $created = absint(self::get($stripe_sub, 'start_date', self::get($stripe_sub, 'created', 0)));
    if ($created) {
        wp_update_post(
            [
                'ID'            => $sub->id,
                'post_date'     => get_date_from_gmt(gmdate('Y-m-d H:i:s', $created)),
                'post_date_gmt' => gmdate('Y-m-d H:i:s', $created),
            ]
        );
    }

    /* translators: %s: Stripe subscription ID */
    ppcart_log_entry($sub->id, sprintf(__('Imported from Stripe subscription %s.', 'publishpress-cart'), $stripe_subscription_id));

    try {
        $sub = self::sync_subscription_by_local($sub->id);
    } catch (Throwable $e) {
        /* translators: %s: error message */
        ppcart_log_entry($sub->id, sprintf(__('Importing payments failed: %s', 'publishpress-cart'), $e->getMessage()));
    }
} finally {
    remove_filter('ppcart_trigger_subscription_integrations', $no_integrations, 99);
    remove_filter('ppcart_trigger_order_integrations', $no_integrations, 99);
}

$sub = new PPCart_Subscription($sub->id);
$first = $sub->first_order();
if ($first && ! empty($first->id)) {
    ppcart_update_post_meta($sub->id, 'first_order', $first->id);
}

return $sub;
