<?php

if (! defined('ABSPATH')) {
    exit;
}


$payment_id = trim(sanitize_text_field((string) $payment_id));
if (0 !== strpos($payment_id, 'ch_') && 0 !== strpos($payment_id, 'pi_')) {
    return new WP_Error('ppcart_import_charge_invalid_id', __('Enter a Stripe payment ID that starts with ch_ or pi_.', 'publishpress-cart'));
}

$subscription_id = absint($subscription_id);
$product_id = absint($product_id);
if ($subscription_id && $product_id) {
    return new WP_Error('ppcart_import_charge_target', __('Enter a subscription ID or choose a product, not both.', 'publishpress-cart'));
}
if (! $subscription_id && ! $product_id) {
    return new WP_Error('ppcart_import_charge_target', __('Enter a subscription ID or choose a product.', 'publishpress-cart'));
}
if ($product_id) {
    return self::import_one_time_charge($payment_id, $product_id);
}

$sub = new PPCart_Subscription($subscription_id);
if (! $sub->id) {
    return new WP_Error('ppcart_import_charge_subscription', __('Enter the ID of a Cart subscription.', 'publishpress-cart'));
}
if ('stripe' !== $sub->pay_method) {
    return new WP_Error('ppcart_import_charge_subscription', __('This subscription isn\'t paid with Stripe.', 'publishpress-cart'));
}

$exists = self::find_imported_charge_order([ $payment_id ]);
if ($exists) {
    return $exists;
}

$charge = self::retrieve_import_charge($payment_id, ppcart_normalize_stripe_mode($sub->gateway_mode));
if (is_wp_error($charge)) {
    return $charge;
}

$charge_id = (string) self::get($charge, 'id', '');

$charge_customer = self::get($charge, 'customer', '');
$charge_customer = is_string($charge_customer) ? $charge_customer : (string) self::get($charge_customer, 'id', '');
if ('' === $charge_customer || (string) $sub->customer_id !== $charge_customer) {
    return new WP_Error(
        'ppcart_import_charge_customer',
        /* translators: %d: local subscription ID */
        sprintf(__('This payment belongs to a different Stripe customer than subscription #%d.', 'publishpress-cart'), (int) $sub->id)
    );
}

// Saving an order onto a subscription whose only order isn't paid yet
// replaces that order (PPCart_Order::check_first_order()). Refuse instead of
// overwriting it.
if (1 === (int) $sub->order_count()) {
    $only_order = $sub->first_order();
    if ($only_order && ! empty($only_order->id) && ! in_array($only_order->status, [ 'paid', 'refunded' ], true)) {
        return new WP_Error(
            'ppcart_import_charge_first_order',
            /* translators: %d: order ID */
            sprintf(__('This subscription\'s only order, #%d, isn\'t paid. Fix that order first, then import the payment.', 'publishpress-cart'), (int) $only_order->id),
            [ 'order_id' => (int) $only_order->id ]
        );
    }
}

$currency = strtoupper((string) self::get($charge, 'currency', $sub->currency ? $sub->currency : 'usd'));
$amount = ppcart_format_stripe_number(absint(self::get($charge, 'amount', 0)), $currency);
$amount_refunded = absint(self::get($charge, 'amount_refunded', 0));
$fully_refunded = $amount_refunded > 0 && $amount_refunded >= absint(self::get($charge, 'amount', 0));

$no_integrations = '__return_false';
add_filter('ppcart_trigger_order_integrations', $no_integrations, 99);

try {
    $imported_order = PPCart_Stripe_Sync_Context::run(
        function () use ($sub, $charge, $charge_id, $currency, $amount, $amount_refunded, $fully_refunded) {
            $order = $sub->new_order();
            if (! $order) {
                return false;
            }

            // The copied first order can miss customer details, for example
            // one migrated from StudioCart. Fill them from the subscription.
            foreach (
                [
                    'firstname', 'lastname', 'first_name', 'last_name', 'customer_name', 'customer_id',
                    'email', 'phone', 'user_account', 'product_id', 'product_name',
                ] as $field
            ) {
                if (empty($order->$field) && isset($sub->$field) && '' !== $sub->$field && null !== $sub->$field) {
                    $order->$field = $sub->$field;
                }
            }

            $order->transaction_id = $charge_id;
            $order->currency = $currency;
            $order->amount = $amount;
            $order->invoice_total = $amount;
            $order->invoice_subtotal = $amount;
            $order->main_offer_amt = $amount;
            $order->tax_amount = 0;
            $order->payment_status = $fully_refunded ? 'refunded' : 'paid';
            $order->status = $fully_refunded ? 'refunded' : 'paid';
            if (! empty($order->items[0])) {
                $order->items[0]['unit_price'] = $amount;
                $order->items[0]['subtotal'] = $amount;
                $order->items[0]['total_amount'] = $amount;
                $order->items[0]['tax_amount'] = 0;
                $order->items[0]['quantity'] = 1;
            }
            $order->quantity = 1;
            $order->store(false);

            if (! $order->id) {
                return false;
            }

            if ($amount_refunded) {
                ppcart_update_post_meta($order->id, 'refund_log', self::build_refund_log_from_charge($charge, $currency));
                ppcart_update_post_meta($order->id, 'refund_amount', ppcart_format_stripe_number($amount_refunded, $currency));
            }

            $order->set_date_from_timestamp(absint(self::get($charge, 'created', time())));

            return $order;
        }
    );
} finally {
    remove_filter('ppcart_trigger_order_integrations', $no_integrations, 99);
}

if (! $imported_order || ! $imported_order->id) {
    return new WP_Error('ppcart_import_charge_store', __('The order could not be saved.', 'publishpress-cart'));
}

/* translators: %s: Stripe charge ID */
ppcart_log_entry($imported_order->id, sprintf(__('Imported from Stripe payment %s.', 'publishpress-cart'), $charge_id));
/* translators: 1: order ID, 2: Stripe charge ID */
ppcart_log_entry($sub->id, sprintf(__('Order #%1$d imported from Stripe payment %2$s.', 'publishpress-cart'), (int) $imported_order->id, $charge_id));

return new PPCart_Order($imported_order->id);
