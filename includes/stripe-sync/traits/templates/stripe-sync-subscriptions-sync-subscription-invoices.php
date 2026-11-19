<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_currency;

if (! $sub || ! $sub->id || empty($sub->subscription_id)) {
    return false;
}

if (! $stripe) {
    $stripe = self::get_client_for_mode($sub->gateway_mode);
}

// Page through every invoice of the subscription. One request returns at most
// 100, and a long-running subscription can have more. The page cap only stops
// a runaway loop (20 pages is 2,000 invoices).
$invoice_params = [
    'subscription' => $sub->subscription_id,
    'limit'        => 100,
    'expand'       => [ 'data.payments' ],
];
$invoice_data = [];
for ($page_number = 0; $page_number < 20; $page_number++) {
    $invoices = $stripe->invoices->all($invoice_params);
    $page_data = isset($invoices->data) && is_array($invoices->data) ? $invoices->data : [];
    if (! $page_data) {
        break;
    }

    $invoice_data = array_merge($invoice_data, $page_data);

    $last_invoice_id = self::get(end($page_data), 'id', '');
    if (empty($invoices->has_more) || ! $last_invoice_id) {
        break;
    }

    $invoice_params['starting_after'] = $last_invoice_id;
}

// Stripe lists the newest invoice first. Import the oldest first so the
// initial invoice lands on the subscription's first order.
$invoice_data = array_reverse($invoice_data);
foreach ($invoice_data as $invoice) {
    self::sync_invoice_resource($invoice, $sub, '', null, $ppcart_currency, '', $stripe);
}

try {
    $stripe_sub = $stripe->subscriptions->retrieve($sub->subscription_id);
    $mapped = self::map_subscription($stripe_sub);
    $customer = self::get($stripe_sub, 'customer', '');
    if ($customer && ! empty($mapped['sub_next_bill_date'])) {
        $upcoming = $stripe->invoices->createPreview(
            [
                'customer'     => $customer,
                'subscription' => $sub->subscription_id,
            ]
        );
        if ($upcoming && self::get($upcoming, 'next_payment_attempt')) {
            PPCart_Stripe_Sync_Context::run(
                function () use ($sub, $upcoming) {
                    $sub->sub_next_bill_date = self::get($upcoming, 'next_payment_attempt');
                    $sub->store();
                }
            );
        }
    } elseif (empty($mapped['sub_next_bill_date'])) {
        PPCart_Stripe_Sync_Context::run(
            function () use ($sub) {
                $sub->sub_next_bill_date = '';
                $sub->store();
            }
        );
    }
} catch (Exception $e) {
    // No upcoming invoice is valid for canceled, paused, or fully completed billing.
}

return true;
