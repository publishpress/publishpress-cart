<?php

if (! defined('ABSPATH')) {
    exit;
}


$item  = self::path($stripe_sub, [ 'items', 'data', 0 ]);
$price = self::get($item, 'price');

// Fall back to the item-level current_period_end (newer Stripe API versions).
if (empty($sub->sub_next_bill_date)) {
    $item_period_end = absint(self::get($item, 'current_period_end', 0));
    if (
        $item_period_end && empty($mapped['cancel_at'])
        && ! in_array($mapped['status'], [ 'canceled', 'paused' ], true)
    ) {
        $sub->sub_next_bill_date = $item_period_end;
    }
}

if ($price) {
    $unit_amount = self::get($price, 'unit_amount', null);
    if (null !== $unit_amount && (empty($sub->sub_amount) || empty($sub->amount))) {
        $quantity = (int) ($sub->quantity ? $sub->quantity : 1);
        $amount   = ((float) $unit_amount / 100) * $quantity;
        if (empty($sub->amount)) {
            $sub->amount = $amount;
        }
        if (empty($sub->sub_amount)) {
            $sub->sub_amount = $amount;
        }
    }

    $interval = self::path($price, [ 'recurring', 'interval' ], '');
    if ($interval && empty($sub->sub_interval)) {
        $sub->sub_interval = sanitize_text_field($interval);
    }

    $interval_count = self::path($price, [ 'recurring', 'interval_count' ], 0);
    if ($interval_count && empty($sub->sub_frequency)) {
        $sub->sub_frequency = absint($interval_count);
    }
}

// Card Element convention: -1 installments for an ongoing subscription.
if (empty($sub->sub_installments)) {
    $sub->sub_installments = -1;
}

if (empty($sub->sub_item_name) && ! empty($sub->item_name)) {
    $sub->sub_item_name = $sub->item_name;
}
