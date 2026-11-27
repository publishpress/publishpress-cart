<?php

defined('ABSPATH') || exit;

/**
 * Stripe processing fees and net amounts for Stripe orders.
 *
 * Cart reads each charge's balance transaction from Stripe (GET only) and
 * stores the fee, net, settlement currency, and exchange rate on the order.
 * Refund balance transactions are stored per refund, because a re-sync must
 * not count a refund twice.
 *
 * Stored amounts are in the settlement currency, in major units. Reports
 * convert them to the order currency with the stored exchange rate.
 */
class PPCart_Stripe_Fees
{
    public const CRON_HOOK = 'ppcart_stripe_fees_fetch';

    public const FETCH_DELAY = 60;

    /**
     * Register the hooks that keep fee data current.
     *
     * @return void
     */
    public static function register()
    {
        add_action(self::CRON_HOOK, [ self::class, 'fetch_scheduled' ]);
        add_action('transition_post_status', [ self::class, 'on_status_change' ], 10, 3);
        add_action('added_post_meta', [ self::class, 'on_meta_change' ], 10, 3);
        add_action('updated_post_meta', [ self::class, 'on_meta_change' ], 10, 3);
    }

    /**
     * The Stripe charge, PaymentIntent, or payment ID that paid a Stripe order.
     *
     * @param int $order_id Order post ID.
     * @return string The ID, or '' when the order has no Stripe payment.
     */
    public static function payment_id($order_id)
    {
        if ('stripe' !== (string) ppcart_get_post_meta($order_id, 'pay_method', true)) {
            return '';
        }

        foreach ([ 'stripe_charge_id', 'transaction_id' ] as $field) {
            $id = ppcart_get_post_meta($order_id, $field, true);
            if (is_string($id) && 1 === preg_match('/^(ch|pi|py)_[A-Za-z0-9]+$/', $id)) {
                return $id;
            }
        }

        return '';
    }

    /**
     * Whether a Stripe order has no fee data yet.
     *
     * @param int $order_id Order post ID.
     * @return bool
     */
    public static function needs_fetch($order_id)
    {
        return '' !== self::payment_id($order_id)
            && (float) ppcart_get_post_meta($order_id, 'amount', true) > 0
            && ! ppcart_get_post_meta($order_id, 'stripe_fees_updated', true);
    }

    /**
     * Fetch fees a minute after a Stripe order is paid or refunded. The delay
     * keeps Stripe calls out of the checkout request.
     *
     * @param string  $new_status New post status.
     * @param string  $old_status Old post status.
     * @param WP_Post $post       Post.
     * @return void
     */
    public static function on_status_change($new_status, $old_status, $post)
    {
        if ($new_status === $old_status || ! $post || ! ppcart_is_order_post_type($post->post_type)) {
            return;
        }

        if (in_array(PPCart_Status_Labels::logical_slug($new_status), [ 'paid', 'refunded' ], true)) {
            self::schedule((int) $post->ID);
        }
    }

    /**
     * Fetch refund fees after the order's refund log changes.
     *
     * @param int    $meta_id  Meta ID.
     * @param int    $order_id Post ID.
     * @param string $meta_key Meta key.
     * @return void
     */
    public static function on_meta_change($meta_id, $order_id, $meta_key)
    {
        if (ppcart_meta_key('refund_log') === $meta_key) {
            self::schedule((int) $order_id);
        }
    }

    /**
     * @param int $order_id Order post ID.
     * @return void
     */
    public static function schedule($order_id)
    {
        $order_id = (int) $order_id;
        if ($order_id > 0 && ! wp_next_scheduled(self::CRON_HOOK, [ $order_id ])) {
            wp_schedule_single_event(time() + self::FETCH_DELAY, self::CRON_HOOK, [ $order_id ]);
        }
    }

    /**
     * Cron callback.
     *
     * @param int $order_id Order post ID.
     * @return void
     */
    public static function fetch_scheduled($order_id)
    {
        if ('' === self::payment_id((int) $order_id)) {
            return;
        }

        $result = self::fetch((int) $order_id);
        if (is_wp_error($result) && class_exists('PPCart_Debug_Logger')) {
            PPCart_Debug_Logger::log_debug_st(
                sprintf('Stripe fees for order #%d were not stored: %s', (int) $order_id, $result->get_error_message()),
                3
            );
        }
    }

    /**
     * Read the order's charge and refund balance transactions from Stripe and store them.
     *
     * @param int         $order_id Order post ID.
     * @param object|null $stripe   Stripe client. Defaults to the client for the order's mode.
     * @return true|WP_Error
     */
    public static function fetch($order_id, $stripe = null)
    {
        $payment_id = self::payment_id($order_id);
        if ('' === $payment_id) {
            return new WP_Error('ppcart_stripe_fees_no_payment', __('This order has no Stripe payment.', 'publishpress-cart'));
        }

        try {
            if (! $stripe) {
                $mode   = ppcart_normalize_stripe_mode((string) ppcart_get_post_meta($order_id, 'gateway_mode', true));
                $stripe = PPCart_Stripe_Sync::get_client_for_mode($mode);
            }

            if (0 === strpos($payment_id, 'pi_')) {
                $intent = $stripe->paymentIntents->retrieve($payment_id, [ 'expand' => [ 'latest_charge.balance_transaction' ] ]);
                $charge = self::value($intent, 'latest_charge');
            } else {
                $charge = $stripe->charges->retrieve($payment_id, [ 'expand' => [ 'balance_transaction' ] ]);
            }

            if (! is_object($charge) || 'succeeded' !== self::value($charge, 'status')) {
                return new WP_Error('ppcart_stripe_fees_not_paid', __('The Stripe payment has not succeeded.', 'publishpress-cart'));
            }

            if (! is_object(self::value($charge, 'balance_transaction'))) {
                return new WP_Error('ppcart_stripe_fees_pending', __('Stripe has no balance transaction for this payment yet.', 'publishpress-cart'));
            }

            $refunds = [];
            if ((int) self::value($charge, 'amount_refunded') > 0) {
                $list    = $stripe->refunds->all(
                    [
                        'charge' => self::value($charge, 'id'),
                        'limit'  => 100,
                        'expand' => [ 'data.balance_transaction' ],
                    ]
                );
                $refunds = (array) self::value($list, 'data');
            }
        } catch (Exception $e) {
            return new WP_Error('ppcart_stripe_fees_request_failed', $e->getMessage());
        }

        self::store($order_id, $charge, $refunds);

        return true;
    }

    /**
     * Store fee data from a charge with its balance transaction expanded.
     *
     * @param int    $order_id Order post ID.
     * @param object $charge   Stripe charge.
     * @param array  $refunds  Stripe refunds with balance transactions expanded.
     * @return void
     */
    public static function store($order_id, $charge, array $refunds = [])
    {
        $transaction = self::value($charge, 'balance_transaction');
        $currency    = strtolower((string) self::value($transaction, 'currency'));

        $platform_fee = 0;
        foreach ((array) self::value($transaction, 'fee_details') as $detail) {
            if ('application_fee' === self::value($detail, 'type')) {
                $platform_fee += (int) self::value($detail, 'amount');
            }
        }

        $refund_fees = [];
        foreach ($refunds as $refund) {
            $refund_transaction = self::value($refund, 'balance_transaction');
            $refund_id          = (string) self::value($refund, 'id');
            if ('' === $refund_id || ! is_object($refund_transaction)) {
                continue;
            }

            $refund_currency = strtolower((string) self::value($refund_transaction, 'currency'));
            $refund_fees[ $refund_id ] = [
                'fee' => self::to_major((int) self::value($refund_transaction, 'fee'), $refund_currency),
                'net' => self::to_major((int) self::value($refund_transaction, 'net'), $refund_currency),
            ];
        }

        $rate = self::value($transaction, 'exchange_rate');

        ppcart_update_post_meta($order_id, 'stripe_fee', self::to_major((int) self::value($transaction, 'fee') - $platform_fee, $currency));
        ppcart_update_post_meta($order_id, 'stripe_platform_fee', self::to_major($platform_fee, $currency));
        ppcart_update_post_meta($order_id, 'stripe_net', self::to_major((int) self::value($transaction, 'net'), $currency));
        ppcart_update_post_meta($order_id, 'stripe_fee_currency', $currency);
        ppcart_update_post_meta($order_id, 'stripe_exchange_rate', null === $rate ? '' : (float) $rate);
        ppcart_update_post_meta($order_id, 'stripe_refund_fees', $refund_fees);
        ppcart_update_post_meta($order_id, 'stripe_fees_updated', time());
    }

    /**
     * Fee totals for an order, in the order currency.
     *
     * @param int $order_id Order post ID.
     * @return array{fee: float, platform_fee: float, net: float, settlement_currency: string}|null
     *         Null when the order has no fee data or it cannot be converted.
     */
    public static function get_order_fees($order_id)
    {
        if (! ppcart_get_post_meta($order_id, 'stripe_fees_updated', true)) {
            return null;
        }

        $fee          = (float) ppcart_get_post_meta($order_id, 'stripe_fee', true);
        $platform_fee = (float) ppcart_get_post_meta($order_id, 'stripe_platform_fee', true);
        $net          = (float) ppcart_get_post_meta($order_id, 'stripe_net', true);

        $refund_fees = ppcart_get_post_meta($order_id, 'stripe_refund_fees', true);
        foreach (is_array($refund_fees) ? $refund_fees : [] as $refund) {
            $fee += (float) ($refund['fee'] ?? 0);
            $net += (float) ($refund['net'] ?? 0);
        }

        $settlement_currency = (string) ppcart_get_post_meta($order_id, 'stripe_fee_currency', true);
        $order_currency      = strtolower((string) ppcart_get_post_meta($order_id, 'currency', true));

        // Stripe: amount in the charge currency × exchange_rate = amount in the settlement currency.
        if ('' !== $order_currency && $order_currency !== $settlement_currency) {
            $rate = (float) ppcart_get_post_meta($order_id, 'stripe_exchange_rate', true);
            if ($rate <= 0) {
                return null;
            }

            $fee          /= $rate;
            $platform_fee /= $rate;
            $net          /= $rate;
        }

        return [
            'fee'                 => round($fee, 2),
            'platform_fee'        => round($platform_fee, 2),
            'net'                 => round($net, 2),
            'settlement_currency' => $settlement_currency,
        ];
    }

    /**
     * Add up fees for a set of orders.
     *
     * @param int[] $order_ids Order post IDs.
     * @return array{fee: float, platform_fee: float, missing: int}
     */
    public static function summarize(array $order_ids)
    {
        $summary = [
            'fee'          => 0.0,
            'platform_fee' => 0.0,
            'missing'      => 0,
        ];

        foreach ($order_ids as $order_id) {
            $fees = self::get_order_fees((int) $order_id);
            if ($fees) {
                $summary['fee']          += $fees['fee'];
                $summary['platform_fee'] += $fees['platform_fee'];
            } elseif (self::needs_fetch((int) $order_id)) {
                $summary['missing']++;
            }
        }

        return $summary;
    }

    /**
     * Stripe orders that have no fee data yet, oldest first.
     *
     * @param int $limit  Max orders.
     * @param int $offset Orders to skip, for example ones that failed in this run.
     * @return array{ids: int[], total: int}
     */
    public static function find_orders_without_fees($limit, $offset = 0)
    {
        $query = new WP_Query(
            [
                'post_type'      => ppcart_query_post_types('order'),
                'post_status'    => [ 'paid', 'refunded' ],
                'fields'         => 'ids',
                'posts_per_page' => max(1, (int) $limit),
                'offset'         => max(0, (int) $offset),
                'orderby'        => 'ID',
                'order'          => 'ASC',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin maintenance batch over Stripe orders.
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'key'   => ppcart_meta_key('pay_method'),
                        'value' => 'stripe',
                    ],
                    [
                        'key'     => ppcart_meta_key('transaction_id'),
                        'value'   => '^(ch|pi|py)_[A-Za-z0-9]+$',
                        // phpcs:ignore WordPressVIPMinimum.Performance.RegexpCompare.compare_compare -- Admin maintenance batch; matches the IDs payment_id() accepts.
                        'compare' => 'REGEXP',
                    ],
                    [
                        'key'     => ppcart_meta_key('stripe_fees_updated'),
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ]
        );

        return [
            'ids'   => array_map('intval', $query->posts),
            'total' => (int) $query->found_posts,
        ];
    }

    /**
     * @param int    $amount   Amount in the currency's smallest unit.
     * @param string $currency Currency code.
     * @return float
     */
    private static function to_major($amount, $currency)
    {
        $zero_decimal = array_map('strtoupper', (array) ppcart_get_zero_decimal_currencies());

        return in_array(strtoupper($currency), $zero_decimal, true) ? (float) $amount : $amount / 100;
    }

    /**
     * @param mixed  $resource Stripe object or array.
     * @param string $key      Field.
     * @return mixed
     */
    private static function value($resource, $key)
    {
        if (is_array($resource)) {
            return $resource[ $key ] ?? null;
        }

        if (is_object($resource) && isset($resource->$key)) {
            return $resource->$key;
        }

        return null;
    }
}
