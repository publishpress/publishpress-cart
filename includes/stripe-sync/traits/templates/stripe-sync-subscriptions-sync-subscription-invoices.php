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

$invoices = $stripe->invoices->search(
    [
        'query' => 'subscription:"' . $sub->subscription_id . '"',
    ]
);

$invoice_data = isset($invoices->data) ? array_reverse($invoices->data) : [];
foreach ($invoice_data as $invoice) {
    self::sync_invoice_resource($invoice, $sub, '', null, $ppcart_currency);
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
