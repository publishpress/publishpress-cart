<?php

if (! defined('ABSPATH')) {
    exit;
}


$payment_id = trim(sanitize_text_field((string) $payment_id));
if (0 !== strpos($payment_id, 'ch_') && 0 !== strpos($payment_id, 'pi_')) {
    return new WP_Error('ppcart_import_charge_invalid_id', __('Enter a Stripe payment ID that starts with ch_ or pi_.', 'publishpress-cart'));
}

$sub = new PPCart_Subscription(absint($subscription_id));
if (! $sub->id) {
    return new WP_Error('ppcart_import_charge_subscription', __('Enter the ID of a Cart subscription.', 'publishpress-cart'));
}
if ('stripe' !== $sub->pay_method) {
    return new WP_Error('ppcart_import_charge_subscription', __('This subscription isn\'t paid with Stripe.', 'publishpress-cart'));
}

$already_imported = function ($ids) {
    foreach (array_filter($ids) as $id) {
        $order = PPCart_Order::get_by_trans_id($id);
        if ($order && $order->id) {
            return new WP_Error(
                'ppcart_import_charge_exists',
                /* translators: %d: order ID */
                sprintf(__('This payment is already order #%d.', 'publishpress-cart'), (int) $order->id),
                [ 'order_id' => (int) $order->id ]
            );
        }
    }

    return false;
};

$exists = $already_imported([ $payment_id ]);
if ($exists) {
    return $exists;
}

try {
    $stripe = self::get_client_for_mode(ppcart_normalize_stripe_mode($sub->gateway_mode));
    if (0 === strpos($payment_id, 'pi_')) {
        $intent = $stripe->paymentIntents->retrieve($payment_id, [ 'expand' => [ 'latest_charge' ] ]);
        $charge = self::get($intent, 'latest_charge', null);
        if (is_string($charge) && '' !== $charge) {
            $charge = $stripe->charges->retrieve($charge, [ 'expand' => [ 'refunds' ] ]);
        } elseif ($charge) {
            $charge = $stripe->charges->retrieve((string) self::get($charge, 'id', ''), [ 'expand' => [ 'refunds' ] ]);
        }
    } else {
        $charge = $stripe->charges->retrieve($payment_id, [ 'expand' => [ 'refunds' ] ]);
    }
} catch (Throwable $e) {
    return new WP_Error('ppcart_import_charge_stripe', $e->getMessage());
}

if (! $charge || 'succeeded' !== (string) self::get($charge, 'status', '')) {
    return new WP_Error('ppcart_import_charge_status', __('Only a successful payment can be imported.', 'publishpress-cart'));
}

// A payment that paid an invoice belongs to that invoice's subscription.
// Sync with Stripe imports it, so it can't be added here by hand.
$charge_invoice = self::get($charge, 'invoice', '');
$charge_invoice = is_string($charge_invoice) ? $charge_invoice : (string) self::get($charge_invoice, 'id', '');
if ('' !== $charge_invoice) {
    return new WP_Error('ppcart_import_charge_invoice', __('This payment paid a Stripe invoice. Use Sync with Stripe on its subscription instead.', 'publishpress-cart'));
}

$charge_id = (string) self::get($charge, 'id', '');
$intent_id = self::get($charge, 'payment_intent', '');
$intent_id = is_string($intent_id) ? $intent_id : (string) self::get($intent_id, 'id', '');

$exists = $already_imported([ $charge_id, $intent_id ]);
if ($exists) {
    return $exists;
}

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
