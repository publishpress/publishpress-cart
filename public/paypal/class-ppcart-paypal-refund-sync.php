<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Applies refunds that PayPal reports for on-page records.
 *
 * The relation between a refund and its payment always comes from PayPal's
 * authenticated answer, never from the webhook event body.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */
class PPCart_Paypal_Refund_Sync
{
    /**
     * @var PPCart_Paypal_Rest_Client
     */
    private $client;

    public function __construct(?PPCart_Paypal_Rest_Client $client = null)
    {
        $this->client = $client ?: PPCart_Paypal_Rest_Client::from_settings();
    }

    /**
     * Refund of a v2 capture: a one-time on-page order.
     *
     * @param string $refund_id PayPal refund ID.
     * @return string One of the PPCart_Paypal_Sync::RESULT_* values.
     */
    public function sync_refund_capture($refund_id)
    {
        $refund_id = sanitize_text_field((string) $refund_id);
        if ('' === $refund_id) {
            return PPCart_Paypal_Sync::RESULT_FAILED;
        }

        $res = $this->client->request('GET', '/v2/payments/refunds/' . rawurlencode($refund_id));
        if (! PPCart_Paypal_Sync::ok($res)) {
            return PPCart_Paypal_Sync::RESULT_UNAVAILABLE;
        }

        $refund = $res['body'];
        if ('COMPLETED' !== ($refund['status'] ?? '')) {
            return PPCart_Paypal_Sync::RESULT_PENDING;
        }

        $capture_id = self::linked_id($refund, 'up');
        $amount     = (float) ($refund['amount']['value'] ?? 0);

        return $this->apply($refund_id, $capture_id, $amount, function () use ($capture_id) {
            $capture = $this->client->request('GET', '/v2/payments/captures/' . rawurlencode($capture_id));
            if (! PPCart_Paypal_Sync::ok($capture)) {
                return null;
            }

            return 'REFUNDED' === ($capture['body']['status'] ?? '');
        });
    }

    /**
     * Refund of a v1 sale: a payment of an on-page subscription.
     *
     * @param string $refund_id PayPal refund ID.
     * @return string One of the PPCart_Paypal_Sync::RESULT_* values.
     */
    public function sync_refund_sale($refund_id)
    {
        $refund_id = sanitize_text_field((string) $refund_id);
        if ('' === $refund_id) {
            return PPCart_Paypal_Sync::RESULT_FAILED;
        }

        $res = $this->client->request('GET', '/v1/payments/refund/' . rawurlencode($refund_id));
        if (! PPCart_Paypal_Sync::ok($res)) {
            return PPCart_Paypal_Sync::RESULT_UNAVAILABLE;
        }

        $refund = $res['body'];
        if ('completed' !== strtolower((string) ($refund['state'] ?? ''))) {
            return PPCart_Paypal_Sync::RESULT_PENDING;
        }

        $sale_id = sanitize_text_field((string) ($refund['sale_id'] ?? ''));
        $amount  = (float) ($refund['amount']['total'] ?? 0);

        return $this->apply($refund_id, $sale_id, $amount, function () use ($sale_id) {
            $sale = $this->client->request('GET', '/v1/payments/sale/' . rawurlencode($sale_id));
            if (! PPCart_Paypal_Sync::ok($sale)) {
                return null;
            }

            return 'refunded' === strtolower((string) ($sale['body']['state'] ?? ''));
        });
    }

    /**
     * Log the refund once on the order that holds the payment, then set the
     * order status from the payment's own state.
     *
     * @param string   $refund_id      PayPal refund ID.
     * @param string   $transaction_id Capture or sale ID the refund belongs to.
     * @param float    $amount         Refunded amount.
     * @param callable $fully_refunded Returns true, false, or null when PayPal could not be read.
     * @return string
     */
    private function apply($refund_id, $transaction_id, $amount, callable $fully_refunded)
    {
        $found = '' !== $transaction_id ? PPCart_Order::get_by_trans_id($transaction_id) : false;
        if (! $found || ! PPCart_Paypal_Sync::is_onpage_order($found->id)) {
            PPCart_Paypal_Sync::log('refund ' . $refund_id . ' has no on-page order for payment ' . $transaction_id);
            return PPCart_Paypal_Sync::RESULT_FAILED;
        }

        $lock = new PPCart_Paypal_Record_Lock($found->id);
        if (! $lock->acquire()) {
            return PPCart_Paypal_Sync::RESULT_BUSY;
        }

        try {
            $order = new PPCart_Order($found->id);
            if (! self::refund_logged($order, $refund_id)) {
                $order->refund_log($amount, $refund_id);
            }

            $refunded = $fully_refunded();
            if (null === $refunded) {
                return PPCart_Paypal_Sync::RESULT_UNAVAILABLE;
            }

            if ($refunded) {
                $order->status         = PPCart_Order::$refunded_str;
                $order->payment_status = PPCart_Order::$refunded_str;
            }
            $order->store();

            return PPCart_Paypal_Sync::RESULT_COMPLETE;
        } finally {
            $lock->release();
        }
    }

    private static function refund_logged(PPCart_Order $order, $refund_id)
    {
        $log = is_array($order->refund_log) ? $order->refund_log : [];

        return in_array($refund_id, wp_list_pluck($log, 'refundID'), true);
    }

    private static function linked_id($resource, $rel)
    {
        foreach ((array) ($resource['links'] ?? []) as $link) {
            if (is_array($link) && ($link['rel'] ?? '') === $rel && ! empty($link['href'])) {
                $segments = explode('/', rtrim((string) wp_parse_url($link['href'], PHP_URL_PATH), '/'));
                return sanitize_text_field((string) end($segments));
            }
        }

        return '';
    }
}
