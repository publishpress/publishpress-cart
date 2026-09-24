<?php

if (! defined('ABSPATH')) {
    exit;
}


if ('checkout.session' !== self::get($session, 'object', '')) {
    return false;
}

$session_id = self::get($session, 'id', '');
if ('' === $session_id) {
    return false;
}

// Only process sessions belonging to this site.
$origin = self::path($session, [ 'metadata', 'origin' ], '');
if ($origin && $origin !== get_site_url()) {
    self::record_webhook_log(
        $event,
        'skipped',
        'Checkout session does not belong to this site.',
        [
            'session_id' => $session_id,
            'origin'     => $origin,
        ]
    );
    return false;
}

$payment_intent_id = self::get_stripe_resource_id_from(self::get($session, 'payment_intent', ''));
$subscription_id   = self::get_stripe_resource_id_from(self::get($session, 'subscription', ''));

$payload  = get_transient('ppcart_hosted_session_' . sanitize_key($session_id));
$order_id = 0;

if (is_array($payload) && ! empty($payload['order_id'])) {
    $order_id = absint($payload['order_id']);
}

if (! $order_id) {
    $order_id = absint(self::metadata($session, 'ppcart_order_id', 0));
}

$cart_order = false;
if ($order_id) {
    $cart_order = new PPCart_Order($order_id);
    if (! $cart_order->id) {
        $cart_order = false;
    }
}

// Fallback: match by payment intent id when the payload is gone.
if (! $cart_order && $payment_intent_id) {
    $cart_order = PPCart_Order::get_by_trans_id($payment_intent_id);
}

if (! $cart_order || ! $cart_order->id) {
    self::record_webhook_log(
        $event,
        'not_matched',
        'Completed checkout session could not be matched to a local order.',
        [
            'session_id'     => $session_id,
            'payment_intent' => $payment_intent_id,
            'subscription'   => $subscription_id,
        ]
    );
    return false;
}

// Idempotent: never re-finalize an already-paid order.
if ('paid' === $cart_order->payment_status && 'paid' === $cart_order->status) {
    self::record_webhook_log(
        $event,
        'ignored',
        'Checkout session already reconciled for this order.',
        [
            'session_id' => $session_id,
            'order_id'   => (int) $cart_order->id,
        ]
    );
    return $cart_order;
}

// Claim the session so webhook + browser-return can't both finalize and double-fire side effects.
if (! self::claim_checkout_session($session_id)) {
    self::record_webhook_log(
        $event,
        'ignored',
        'Checkout session completion already in progress on another path.',
        [
            'session_id' => $session_id,
            'order_id'   => (int) $cart_order->id,
        ]
    );
    return $cart_order;
}

return PPCart_Stripe_Sync_Context::run(
    function () use ($cart_order, $session, $session_id, $payment_intent_id, $subscription_id, $event, $stripe) {
        if ($payment_intent_id) {
            $cart_order->transaction_id = $payment_intent_id;
        }
        $cart_order->status = 'paid';
        $cart_order->payment_status = 'paid';
        $cart_order->store();

        // Link a local subscription so hosted + Card Element flows produce the same record.
        if ($subscription_id) {
            self::link_hosted_checkout_subscription($cart_order, $session, $subscription_id, $stripe);
        }

        ppcart_update_post_meta($cart_order->id, 'checkout_session_id', sanitize_text_field($session_id));
        delete_transient('ppcart_hosted_session_' . sanitize_key($session_id));
        self::mark_resource_event($cart_order->id, $event);
        self::mark_successful_sync($cart_order->id);

        self::record_webhook_log(
            $event,
            'applied',
            'Checkout session completed and order finalized.',
            [
                'session_id'     => $session_id,
                'order_id'       => (int) $cart_order->id,
                'payment_intent' => $payment_intent_id,
                'subscription'   => $subscription_id,
            ]
        );

        do_action('ppcart_stripe_checkout_session_completed', $cart_order->get_data(), $session);

        // Durable guard now set; release the short-lived race claim.
        self::release_checkout_session($session_id);

        return $cart_order;
    }
);
