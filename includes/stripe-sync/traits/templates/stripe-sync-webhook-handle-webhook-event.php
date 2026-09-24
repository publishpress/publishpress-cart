<?php

if (! defined('ABSPATH')) {
    exit;
}


$event_type = self::get($event, 'type', '');
$object = self::get(self::get($event, 'data'), 'object');
$object_type = self::get($object, 'object', '');

switch ($object_type) {
    case 'charge':
        if ('charge.succeeded' === $event_type) {
            return self::sync_charge_status($object, $event, self::get_client_for_mode(self::guess_mode_from_event($event)));
        }

        if ('charge.refunded' === $event_type) {
            return self::sync_charge_refund($object, null, $event);
        }
        break;

    case 'refund':
        if ('refund.created' === $event_type) {
            return self::sync_refund($object, null, $event);
        }
        break;

    case 'invoice':
        if (in_array($event_type, [ 'invoice.payment_succeeded', 'invoice.payment_failed', 'invoice.marked_uncollectible' ], true)) {
            return self::sync_invoice($object, null, $event_type, $event);
        }
        break;

    case 'subscription':
        if (in_array($event_type, [ 'customer.subscription.updated', 'customer.subscription.deleted', 'customer.subscription.paused', 'customer.subscription.resumed' ], true)) {
            return self::sync_subscription_resource($object, self::get_client_for_mode(self::guess_mode_from_event($event)), $event);
        }
        break;

    case 'checkout.session':
        if ('checkout.session.completed' === $event_type) {
            return self::sync_checkout_session_completed($object, self::get_client_for_mode(self::guess_mode_from_event($event)), $event);
        }
        break;
}

self::record_webhook_log($event, 'ignored', 'Unsupported Stripe webhook event type or object.');
return false;
