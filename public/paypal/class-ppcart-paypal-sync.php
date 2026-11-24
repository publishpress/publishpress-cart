<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Makes local on-page PayPal orders and subscriptions match what PayPal reports.
 *
 * Called by the approve AJAX handler, the REST webhook, and the hourly
 * reconcile job. Every public method takes a per-record lock first, loads the
 * records under the lock, reads the resource from PayPal with the merchant's
 * credentials, checks that it belongs to the local record, and only then
 * writes. Nothing from an event body or the browser is trusted for state.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */
class PPCart_Paypal_Sync
{
    public const RESULT_COMPLETE    = 'complete';
    public const RESULT_PENDING     = 'pending';
    public const RESULT_PROCESSING  = 'processing';
    public const RESULT_FAILED      = 'failed';
    public const RESULT_BUSY        = 'busy';
    public const RESULT_UNAVAILABLE = 'unavailable';

    /**
     * @var array<string, PPCart_Paypal_Rest_Client>
     */
    private $clients = [];

    /**
     * Reconcile the Cart order of an on-page checkout, one-time or subscription.
     *
     * @param int $cart_order_id Cart order ID.
     * @return string One of the RESULT_* values.
     */
    public function sync_order($cart_order_id)
    {
        $cart_order_id = absint($cart_order_id);
        if (! $cart_order_id || ! self::is_onpage_order($cart_order_id)) {
            return self::RESULT_FAILED;
        }

        $sub_post_id = absint(ppcart_get_post_meta($cart_order_id, 'subscription_id'));
        if ($sub_post_id) {
            return $this->sync_subscription($sub_post_id);
        }

        return $this->with_lock($cart_order_id, function () use ($cart_order_id) {
            $order = new PPCart_Order($cart_order_id);
            if (! $order->id) {
                return self::RESULT_FAILED;
            }

            return $this->sync_onetime($order);
        });
    }

    /**
     * Reconcile an on-page subscription and its payments.
     *
     * @param PPCart_Subscription|int $sub Local subscription or its post ID.
     * @return string One of the RESULT_* values.
     */
    public function sync_subscription($sub)
    {
        $sub_post_id = is_object($sub) ? absint($sub->id) : absint($sub);
        if (! $sub_post_id) {
            return self::RESULT_FAILED;
        }

        return $this->with_lock($sub_post_id, function () use ($sub_post_id) {
            $sub = new PPCart_Subscription($sub_post_id);
            if (! $sub->id) {
                return self::RESULT_FAILED;
            }

            return $this->sync_subscription_locked($sub);
        });
    }

    /**
     * @param int $order_id Cart order ID.
     * @return bool
     */
    public static function is_onpage_order($order_id)
    {
        return (bool) ppcart_get_post_meta(absint($order_id), 'paypal_onpage');
    }

    /**
     * @param int $order_id Cart order ID.
     * @return void
     */
    public static function mark_onpage($order_id)
    {
        ppcart_update_post_meta(absint($order_id), 'paypal_onpage', 1);
    }

    /**
     * @param string $paypal_order_id PayPal order ID.
     * @return PPCart_Order|false
     */
    public static function find_order_by_paypal_order_id($paypal_order_id)
    {
        $paypal_order_id = sanitize_text_field((string) $paypal_order_id);
        if ('' === $paypal_order_id) {
            return false;
        }

        $posts = get_posts([
            'post_type'      => ppcart_query_post_types('order'),
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Lookup by the stored PayPal order ID.
            'meta_query'     => [
                [
                    'key'   => ppcart_meta_key('paypal_order_id'),
                    'value' => $paypal_order_id,
                ],
            ],
        ]);

        return empty($posts) ? false : new PPCart_Order((int) $posts[0]);
    }

    /**
     * @param PPCart_Order|PPCart_Subscription $record Record with a currency.
     * @return string Upper-case currency code.
     */
    public static function record_currency($record)
    {
        $currency = isset($record->currency) ? (string) $record->currency : '';
        if ('' === $currency) {
            $currency = (string) get_option('_ppcart_currency', 'USD');
        }

        return strtoupper($currency);
    }

    /**
     * @param array $res Client result.
     * @return bool
     */
    public static function ok($res)
    {
        return $res['code'] >= 200 && $res['code'] < 300;
    }

    /**
     * @param string $message Message without secrets or payer data.
     * @return void
     */
    public static function log($message)
    {
        global $ppcart_debug_logger;

        if (is_object($ppcart_debug_logger) && method_exists($ppcart_debug_logger, 'log_debug')) {
            $ppcart_debug_logger->log_debug('PayPal on-page sync: ' . $message);
        }
    }

    private function sync_onetime(PPCart_Order $order)
    {
        if (PPCart_Order::$paid_str === $order->status) {
            return self::RESULT_COMPLETE;
        }

        $paypal_order_id = sanitize_text_field((string) ppcart_get_post_meta($order->id, 'paypal_order_id'));
        if ('' === $paypal_order_id) {
            return self::RESULT_FAILED;
        }

        $client = $this->client($order->gateway_mode);
        $path   = '/v2/checkout/orders/' . rawurlencode($paypal_order_id);
        $res    = $client->request('GET', $path);
        if (! self::ok($res)) {
            return self::RESULT_UNAVAILABLE;
        }

        $paypal_order = $res['body'];
        if (! $this->onetime_matches($paypal_order, $order)) {
            self::log('PayPal order ' . $paypal_order_id . ' does not match Cart order #' . $order->id);
            return self::RESULT_FAILED;
        }

        $status = (string) ($paypal_order['status'] ?? '');
        if ('APPROVED' === $status) {
            $capture = $client->request('POST', $path . '/capture', null, [
                'PayPal-Request-Id' => sanitize_text_field((string) ppcart_get_post_meta($order->id, 'paypal_request_id')),
                'Prefer'            => 'return=representation',
            ]);

            if (self::ok($capture)) {
                $paypal_order = $capture['body'];
                if (ppcart_order_is_shopping_cart($order)) {
                    // Capture responses can omit purchase-unit fields. Read the full order before matching its capture.
                    $res = $client->request('GET', $path);
                    if (! self::ok($res)) {
                        return self::RESULT_UNAVAILABLE;
                    }
                    $paypal_order = $res['body'];
                }
            } elseif (422 === $capture['code'] && self::has_issue($capture, 'ORDER_ALREADY_CAPTURED')) {
                $res = $client->request('GET', $path);
                if (! self::ok($res)) {
                    return self::RESULT_UNAVAILABLE;
                }
                $paypal_order = $res['body'];
            } elseif (422 === $capture['code']) {
                $order->status = PPCart_Order::$failed_str;
                $order->store();
                return self::RESULT_FAILED;
            } else {
                return self::RESULT_UNAVAILABLE;
            }
        } elseif (in_array($status, [ 'CREATED', 'SAVED', 'PAYER_ACTION_REQUIRED' ], true)) {
            return self::RESULT_PENDING;
        } elseif ('VOIDED' === $status) {
            $order->status = PPCart_Order::$failed_str;
            $order->store();
            return self::RESULT_FAILED;
        }

        $capture = $paypal_order['purchase_units'][0]['payments']['captures'][0] ?? null;
        if (! is_array($capture) || empty($capture['id'])) {
            return self::RESULT_PENDING;
        }

        if (
            ppcart_order_is_shopping_cart($order) && (
                ! $this->onetime_matches($paypal_order, $order)
                || count($paypal_order['purchase_units']) !== 1
                || count($paypal_order['purchase_units'][0]['payments']['captures']) !== 1
                || strtoupper((string) ($capture['amount']['currency_code'] ?? '')) !== self::record_currency($order)
                || PPCart_Paypal_Billing_Shape::money($capture['amount']['value'] ?? -1) !== PPCart_Paypal_Billing_Shape::money($order->amount)
            )
        ) {
            return self::RESULT_FAILED;
        }

        $capture_id = sanitize_text_field((string) $capture['id']);
        switch ((string) ($capture['status'] ?? '')) {
            case 'COMPLETED':
            case 'PARTIALLY_REFUNDED':
                self::mark_paid($order, $capture_id, null);
                $order->store();
                return self::RESULT_COMPLETE;
            case 'PENDING':
                $order->transaction_id = $capture_id;
                $order->store();
                return self::RESULT_PROCESSING;
            case 'REFUNDED':
                $order->transaction_id = $capture_id;
                $order->status         = PPCart_Order::$refunded_str;
                $order->payment_status = PPCart_Order::$refunded_str;
                $order->store();
                return self::RESULT_FAILED;
            case 'DECLINED':
            case 'FAILED':
                $order->transaction_id = $capture_id;
                $order->status         = PPCart_Order::$failed_str;
                $order->store();
                return self::RESULT_FAILED;
        }

        return self::RESULT_PENDING;
    }

    private function onetime_matches($paypal_order, PPCart_Order $order)
    {
        $unit = $paypal_order['purchase_units'][0] ?? [];

        return (! ppcart_order_is_shopping_cart($order) || ($paypal_order['id'] ?? '') === (string) ppcart_get_post_meta($order->id, 'paypal_order_id'))
            && ($unit['custom_id'] ?? '') === 'ppcart:' . $order->id
            && strtoupper((string) ($unit['amount']['currency_code'] ?? '')) === self::record_currency($order)
            && PPCart_Paypal_Billing_Shape::money($unit['amount']['value'] ?? -1) === PPCart_Paypal_Billing_Shape::money($order->amount);
    }

    private function sync_subscription_locked(PPCart_Subscription $sub)
    {
        $first = $sub->first_order();
        if (! $first || ! $first->id || ! self::is_onpage_order($first->id)) {
            return self::RESULT_FAILED;
        }

        $paypal_sub_id = sanitize_text_field((string) $sub->subscription_id);
        if ('' === $paypal_sub_id) {
            return self::RESULT_FAILED;
        }

        $client = $this->client($sub->gateway_mode);
        $path   = '/v1/billing/subscriptions/' . rawurlencode($paypal_sub_id);
        $res    = $client->request('GET', $path);
        if (! self::ok($res)) {
            return self::RESULT_UNAVAILABLE;
        }

        $paypal_sub = $res['body'];
        if (($paypal_sub['custom_id'] ?? '') !== 'ppcart:' . $first->id . ':' . $sub->id) {
            self::log('PayPal subscription ' . $paypal_sub_id . ' does not match subscription #' . $sub->id);
            return self::RESULT_FAILED;
        }

        $paypal_status = (string) ($paypal_sub['status'] ?? '');
        if (in_array($paypal_status, [ 'APPROVAL_PENDING', 'APPROVED' ], true)) {
            return self::RESULT_PENDING;
        }

        $payments = $this->fetch_subscription_payments($client, $path);
        if (null === $payments) {
            return self::RESULT_UNAVAILABLE;
        }

        $local_status = $this->local_subscription_status($paypal_sub, $sub);
        if ('' !== $local_status) {
            $sub->status     = $local_status;
            $sub->sub_status = $local_status;
        }

        if (! empty($paypal_sub['billing_info']['next_billing_time'])) {
            $next = strtotime((string) $paypal_sub['billing_info']['next_billing_time']);
            if ($next) {
                $sub->sub_next_bill_date = $next;
            }
        }

        $this->apply_subscription_payments($payments, $sub, $first);

        if (
            'ACTIVE' === $paypal_status
            && PPCart_Order::$pending_str === $first->status
            && PPCart_Paypal_Billing_Shape::is_free($first->amount)
        ) {
            self::mark_paid($first, $paypal_sub_id, null);
            $first->store();
        }

        $sub->store();

        if (in_array($sub->status, [ PPCart_Subscription::$active_str, PPCart_Subscription::$trial_str ], true)) {
            return self::RESULT_COMPLETE;
        }

        if (in_array($paypal_status, [ 'CANCELLED', 'EXPIRED' ], true) && PPCart_Order::$pending_str === $first->status) {
            return self::RESULT_FAILED;
        }

        return self::RESULT_PENDING;
    }

    private function local_subscription_status($paypal_sub, PPCart_Subscription $sub)
    {
        switch ((string) ($paypal_sub['status'] ?? '')) {
            case 'ACTIVE':
                return $this->in_trial_window($sub) ? PPCart_Subscription::$trial_str : PPCart_Subscription::$active_str;
            case 'SUSPENDED':
                $failed = (int) ($paypal_sub['billing_info']['failed_payments_count'] ?? 0);
                return $failed > 0 ? PPCart_Subscription::$past_due_str : PPCart_Subscription::$paused_str;
            case 'CANCELLED':
                return PPCart_Subscription::$canceled_str;
            case 'EXPIRED':
                return PPCart_Subscription::$completed_str;
        }

        return '';
    }

    /**
     * Same rule as the IPN handler: active inside the trial window is "trialing".
     */
    private function in_trial_window(PPCart_Subscription $sub)
    {
        $trial_days = (int) $sub->free_trial_days;
        if ($trial_days < 1) {
            return false;
        }

        $trial_ends = gmdate('Y-m-d', strtotime(get_the_time('Y-m-d', $sub->id) . ' +' . $trial_days . ' day'));

        return gmdate('Y-m-d') < $trial_ends;
    }

    /**
     * The payments PayPal took in the last 30 days, oldest first. A payment
     * that was refunded later still happened, so it is listed too.
     *
     * @return array[]|null Null when PayPal could not be read.
     */
    private function fetch_subscription_payments(PPCart_Paypal_Rest_Client $client, $path)
    {
        $res = $client->request('GET', $path . '/transactions?' . http_build_query([
            'start_time' => gmdate('Y-m-d\TH:i:s\Z', time() - 30 * DAY_IN_SECONDS),
            'end_time'   => gmdate('Y-m-d\TH:i:s\Z', time() + DAY_IN_SECONDS),
        ]));
        if (! self::ok($res)) {
            return null;
        }

        $paid         = [ 'COMPLETED', 'PARTIALLY_REFUNDED', 'REFUNDED' ];
        $transactions = [];
        foreach ((array) ($res['body']['transactions'] ?? []) as $transaction) {
            if (is_array($transaction) && in_array($transaction['status'] ?? '', $paid, true) && ! empty($transaction['id'])) {
                $transactions[] = $transaction;
            }
        }
        usort($transactions, static function ($a, $b) {
            return strcmp((string) ($a['time'] ?? ''), (string) ($b['time'] ?? ''));
        });

        return $transactions;
    }

    /**
     * Record each PayPal payment once.
     */
    private function apply_subscription_payments(array $transactions, PPCart_Subscription $sub, PPCart_Order $first)
    {
        foreach ($transactions as $transaction) {
            $txn_id = sanitize_text_field((string) $transaction['id']);
            if (PPCart_Order::get_by_trans_id($txn_id)) {
                continue;
            }

            $gross = $transaction['amount_with_breakdown']['gross_amount']['value'] ?? null;
            if (PPCart_Order::$pending_str === $first->status) {
                self::mark_paid($first, $txn_id, $gross);
                $first->store();
                continue;
            }

            $renewal = $sub->new_order();
            if (! $renewal) {
                continue;
            }
            self::mark_paid($renewal, $txn_id, $gross);
            $renewal->store();
            self::mark_onpage($renewal->id);
        }
    }

    private static function mark_paid(PPCart_Order $order, $transaction_id, $amount)
    {
        $order->transaction_id = $transaction_id;
        if (null !== $amount) {
            $order->amount = (float) $amount;
        }
        $order->status         = PPCart_Order::$paid_str;
        $order->payment_status = 'Completed';
    }

    private static function has_issue($res, $issue)
    {
        foreach ((array) ($res['body']['details'] ?? []) as $detail) {
            if (is_array($detail) && ($detail['issue'] ?? '') === $issue) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $gateway_mode test|live|'' (empty = the current setting).
     * @return PPCart_Paypal_Rest_Client
     */
    private function client($gateway_mode = '')
    {
        $gateway_mode = (string) $gateway_mode;
        if (! isset($this->clients[ $gateway_mode ])) {
            $this->clients[ $gateway_mode ] = PPCart_Paypal_Rest_Client::for_gateway_mode($gateway_mode);
        }

        return $this->clients[ $gateway_mode ];
    }

    private function with_lock($record_id, callable $callback)
    {
        $lock = new PPCart_Paypal_Record_Lock($record_id);
        if (! $lock->acquire()) {
            return self::RESULT_BUSY;
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
}
