<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Routes PayPal REST webhook events to the sync classes.
 *
 * The event body is never trusted for state. It only names a resource; the
 * sync reads that resource from PayPal. Unknown ids get 200 and no work, and
 * one id is handled at most once per 30 seconds, which bounds what a forged
 * request can make the site do.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */
class PPCart_Paypal_Webhook
{
    private const THROTTLE_SECONDS = 30;

    /**
     * @param array $event Decoded webhook event.
     * @return int HTTP status to answer with.
     */
    public static function handle($event)
    {
        $event_type = isset($event['event_type']) ? sanitize_text_field((string) $event['event_type']) : '';
        $resource   = isset($event['resource']) && is_array($event['resource']) ? $event['resource'] : [];
        $target     = self::target($event_type, $resource);

        if (null === $target) {
            return 200;
        }

        list($kind, $id) = $target;

        $sub   = false;
        $order = false;
        if ('subscription' === $kind) {
            $sub = PPCart_Subscription::get_by_sub_id($id);
            if (! $sub) {
                return 200;
            }
        } elseif ('order' === $kind) {
            $order = PPCart_Paypal_Sync::find_order_by_paypal_order_id($id);
            if (! $order) {
                return 200;
            }
        }

        $throttle_key = 'ppcart_pp_wh_' . md5($id);
        if (get_transient($throttle_key)) {
            return 503;
        }
        set_transient($throttle_key, 1, self::THROTTLE_SECONDS);

        switch ($kind) {
            case 'subscription':
                $result = (new PPCart_Paypal_Sync())->sync_subscription($sub);
                break;
            case 'order':
                $result = (new PPCart_Paypal_Sync())->sync_order($order->id);
                break;
            case 'refund_sale':
                $result = (new PPCart_Paypal_Refund_Sync())->sync_refund_sale($id);
                break;
            default:
                $result = (new PPCart_Paypal_Refund_Sync())->sync_refund_capture($id);
                break;
        }

        PPCart_Paypal_Sync::log('webhook ' . $event_type . ' for ' . $id . ': ' . $result);

        return in_array($result, [ PPCart_Paypal_Sync::RESULT_BUSY, PPCart_Paypal_Sync::RESULT_UNAVAILABLE ], true) ? 503 : 200;
    }

    /**
     * @param string $event_type Event type.
     * @param array  $resource   Event resource.
     * @return array{0: string, 1: string}|null Kind and PayPal id, or null when the event is not handled.
     */
    private static function target($event_type, $resource)
    {
        $id = static function ($value) {
            return is_scalar($value) ? sanitize_text_field((string) $value) : '';
        };

        if (0 === strpos($event_type, 'BILLING.SUBSCRIPTION.')) {
            $kind  = 'subscription';
            $value = $id($resource['id'] ?? '');
        } elseif (in_array($event_type, [ 'PAYMENT.SALE.COMPLETED', 'PAYMENT.SALE.DENIED', 'PAYMENT.SALE.REVERSED' ], true)) {
            $kind  = 'subscription';
            $value = $id($resource['billing_agreement_id'] ?? '');
        } elseif ('PAYMENT.SALE.REFUNDED' === $event_type) {
            $kind  = 'refund_sale';
            $value = $id($resource['id'] ?? '');
        } elseif ('CHECKOUT.ORDER.APPROVED' === $event_type) {
            $kind  = 'order';
            $value = $id($resource['id'] ?? '');
        } elseif (in_array($event_type, [ 'PAYMENT.CAPTURE.COMPLETED', 'PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.PENDING', 'PAYMENT.CAPTURE.REVERSED' ], true)) {
            $kind  = 'order';
            $value = $id($resource['supplementary_data']['related_ids']['order_id'] ?? '');
        } elseif ('PAYMENT.CAPTURE.REFUNDED' === $event_type) {
            $kind  = 'refund_capture';
            $value = $id($resource['id'] ?? '');
        } else {
            return null;
        }

        return '' === $value ? null : [ $kind, $value ];
    }
}
