<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Pure mapping from a Cart subscription to the PayPal billing cycles.
 *
 * Shape:
 *   currency, interval_unit (DAY|WEEK|MONTH|YEAR), interval_count,
 *   regular_amount, regular_cycles (0 = no end),
 *   trial: null | [unit, count, amount] (amount "0.00" = free)
 *
 * The on-page flow charges what Cart recorded: the first order amount for
 * the first payment and sub_amount for each later payment. Both include tax
 * and fees, so no setup_fee and no taxes block is sent.
 *
 * @package PPCart
 * @subpackage PPCart/public
 */
final class PPCart_Paypal_Billing_Shape
{
    public const INTERVAL_UNITS = [
        'day'   => 'DAY',
        'week'  => 'WEEK',
        'month' => 'MONTH',
        'year'  => 'YEAR',
    ];

    public const UNIT_MAX = [
        'DAY'   => 365,
        'WEEK'  => 52,
        'MONTH' => 12,
        'YEAR'  => 1,
    ];

    /**
     * @param PPCart_Subscription $sub      Subscription, stored or not.
     * @param PPCart_Order        $order    First order.
     * @param string              $currency Currency code.
     * @return array|null Null when PayPal cannot express the plan.
     */
    public static function build($sub, $order, $currency)
    {
        $unit = self::INTERVAL_UNITS[ strtolower((string) $sub->sub_interval) ] ?? '';
        if ('' === $unit) {
            return null;
        }

        $count = (int) $sub->sub_frequency;
        if ($count < 1 || $count > self::UNIT_MAX[ $unit ]) {
            return null;
        }

        $trial_days = (int) $sub->free_trial_days;
        if ($trial_days > self::UNIT_MAX['DAY']) {
            return null;
        }

        $installments  = (int) $sub->sub_installments;
        $first_amount  = self::money($order->amount);
        $sub_amount    = self::money($sub->sub_amount);
        $shape         = [
            'currency'       => strtoupper((string) $currency),
            'interval_unit'  => $unit,
            'interval_count' => $count,
            'regular_amount' => $sub_amount,
            'regular_cycles' => $installments > 0 ? $installments : 0,
            'trial'          => null,
        ];

        if ($trial_days > 0) {
            $shape['trial'] = [ 'unit' => 'DAY', 'count' => $trial_days, 'amount' => $first_amount ];
            return $shape;
        }

        if ($first_amount === $sub_amount) {
            return $shape;
        }

        if (1 === $installments) {
            $shape['regular_amount'] = $first_amount;
            $shape['regular_cycles'] = 1;
            return $shape;
        }

        $shape['trial']          = [ 'unit' => $unit, 'count' => $count, 'amount' => $first_amount ];
        $shape['regular_cycles'] = $installments > 0 ? $installments - 1 : 0;

        return $shape;
    }

    /**
     * Cache key of the PayPal plan a shape maps to. Amounts and cycle counts
     * are sent per subscription, so they are not part of the key.
     *
     * @param array  $shape      Billing shape.
     * @param string $mode       sandbox|live
     * @param string $client_id  PayPal client ID.
     * @param int    $product_id Cart product ID.
     * @return string
     */
    public static function plan_key($shape, $mode, $client_id, $product_id)
    {
        $trial = $shape['trial'];

        return md5(wp_json_encode([
            $mode,
            (string) $client_id,
            (int) $product_id,
            $shape['currency'],
            $shape['interval_unit'],
            (int) $shape['interval_count'],
            $trial ? $trial['unit'] : '',
            $trial ? (int) $trial['count'] : 0,
            $trial ? self::is_free($trial['amount']) : false,
        ]));
    }

    /**
     * Billing cycles for POST /v1/billing/plans, with placeholder prices.
     *
     * @param array $shape Billing shape.
     * @return array
     */
    public static function plan_billing_cycles($shape)
    {
        $cycles = [];
        $trial  = $shape['trial'];

        if ($trial) {
            $cycle = [
                'tenure_type'  => 'TRIAL',
                'sequence'     => 1,
                'frequency'    => [ 'interval_unit' => $trial['unit'], 'interval_count' => (int) $trial['count'] ],
                'total_cycles' => 1,
            ];
            if (! self::is_free($trial['amount'])) {
                $cycle['pricing_scheme'] = self::pricing('1.00', $shape['currency']);
            }
            $cycles[] = $cycle;
        }

        $cycles[] = [
            'tenure_type'    => 'REGULAR',
            'sequence'       => $trial ? 2 : 1,
            'frequency'      => [ 'interval_unit' => $shape['interval_unit'], 'interval_count' => (int) $shape['interval_count'] ],
            'total_cycles'   => 0,
            'pricing_scheme' => self::pricing('1.00', $shape['currency']),
        ];

        return $cycles;
    }

    /**
     * Billing cycle override for POST /v1/billing/subscriptions, with real amounts.
     *
     * @param array $shape Billing shape.
     * @return array
     */
    public static function subscription_billing_cycles($shape)
    {
        $cycles = [];
        $trial  = $shape['trial'];

        if ($trial) {
            $cycle = [ 'sequence' => 1, 'total_cycles' => 1 ];
            if (! self::is_free($trial['amount'])) {
                $cycle['pricing_scheme'] = self::pricing($trial['amount'], $shape['currency']);
            }
            $cycles[] = $cycle;
        }

        $cycles[] = [
            'sequence'       => $trial ? 2 : 1,
            'total_cycles'   => (int) $shape['regular_cycles'],
            'pricing_scheme' => self::pricing($shape['regular_amount'], $shape['currency']),
        ];

        return $cycles;
    }

    /**
     * @param mixed $amount Amount.
     * @return string Two-decimal string, as the redirect flow sends it.
     */
    public static function money($amount)
    {
        return number_format((float) $amount, 2, '.', '');
    }

    public static function is_free($amount)
    {
        return (float) $amount < 0.005;
    }

    private static function pricing($value, $currency)
    {
        return [ 'fixed_price' => [ 'value' => (string) $value, 'currency_code' => $currency ] ];
    }
}
