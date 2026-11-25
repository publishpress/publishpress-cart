<?php

if (! defined('ABSPATH')) {
    exit;
}


if (! $sub || ! $sub->id || ! $paid_at) {
    return 0;
}

$amount = (float) ppcart_format_stripe_number(absint($amount), $currency);
$paid_at = absint($paid_at);

$order_ids = get_posts(
    [
        'post_type'      => ppcart_query_post_types('order'),
        'post_status'    => 'any',
        'posts_per_page' => 500,
        'fields'         => 'ids',
        'orderby'        => 'date',
        'order'          => 'ASC',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Orders of one subscription, during a manual or webhook sync.
        'meta_query'     => [
            [
                'key'   => ppcart_meta_key('subscription_id'),
                'value' => $sub->id,
            ],
        ],
    ]
);

foreach ($order_ids as $order_id) {
    if ('' !== (string) ppcart_get_post_meta($order_id, 'transaction_id', true)) {
        continue;
    }

    // An order already linked to another Stripe invoice is a different payment.
    $linked_invoice_id = (string) ppcart_get_post_meta($order_id, 'stripe_invoice_id', true);
    if ('' !== $linked_invoice_id && $linked_invoice_id !== (string) $invoice_id) {
        continue;
    }

    $order_status = (string) ppcart_get_post_meta($order_id, 'status', true);
    if (! in_array($order_status, [ 'paid', 'completed', 'refunded' ], true)) {
        continue;
    }

    if (abs((float) ppcart_get_post_meta($order_id, 'amount', true) - $amount) > 0.01) {
        continue;
    }

    $order_time = absint(get_post_time('U', true, $order_id));
    if ($order_time && abs($order_time - $paid_at) <= DAY_IN_SECONDS) {
        return (int) $order_id;
    }
}

return 0;
