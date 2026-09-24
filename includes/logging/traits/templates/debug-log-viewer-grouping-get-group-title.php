<?php

if (! defined('ABSPATH')) {
    exit;
}


$record_ids = $group['record_ids'] ?? [];
$workflow   = $group['workflow'] ?? 'General';

if (! empty($record_ids['order_id']) && self::group_has_payment_setup_event($group)) {
    return sprintf(
        /* translators: %s: order ID. */
        __('Payment setup for Order #%s', 'publishpress-cart'),
        absint($record_ids['order_id'])
    );
}

if (! empty($record_ids['order_id']) && self::group_has_order_transition($group, 'pending-payment', 'paid')) {
    return sprintf(
        /* translators: %s: order ID. */
        __('Payment confirmed for Order #%s', 'publishpress-cart'),
        absint($record_ids['order_id'])
    );
}

if (! empty($record_ids['order_id']) && self::group_has_transaction_update_without_status_change($group)) {
    return sprintf(
        /* translators: %s: order ID. */
        __('Stripe payment details updated for Order #%s', 'publishpress-cart'),
        absint($record_ids['order_id'])
    );
}

if (! empty($record_ids['order_id']) && self::group_has_event($group, [ 'checkout.order.saved' ])) {
    return sprintf(
        /* translators: %s: order ID. */
        __('Checkout saved Order #%s', 'publishpress-cart'),
        absint($record_ids['order_id'])
    );
}

if (! empty($record_ids['order_id']) && self::group_has_event($group, [ 'order.save.created' ])) {
    return sprintf(
        /* translators: %s: order ID. */
        __('Order #%s created', 'publishpress-cart'),
        absint($record_ids['order_id'])
    );
}

if (! empty($record_ids['order_id']) && self::group_has_workflow($group, [ 'Checkout', 'Stripe' ])) {
    return sprintf(
        /* translators: %s: order ID. */
        __('Checkout for Order #%s', 'publishpress-cart'),
        absint($record_ids['order_id'])
    );
}

if (! empty($record_ids['order_id'])) {
    return sprintf(
        /* translators: %s: order ID. */
        __('Order #%s activity', 'publishpress-cart'),
        absint($record_ids['order_id'])
    );
}

if (! empty($record_ids['subscription_id'])) {
    return sprintf(
        /* translators: %s: subscription ID. */
        __('Subscription #%s activity', 'publishpress-cart'),
        absint($record_ids['subscription_id'])
    );
}

if (! empty($record_ids['product_id'])) {
    return sprintf(
        /* translators: %s: product ID. */
        __('Checkout for Product #%s', 'publishpress-cart'),
        absint($record_ids['product_id'])
    );
}

return sprintf(
    /* translators: %s: workflow name. */
    __('%s activity', 'publishpress-cart'),
    $workflow
);
