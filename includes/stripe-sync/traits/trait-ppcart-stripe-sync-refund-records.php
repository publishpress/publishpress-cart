<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Builds an order's refund record from the refunds on its Stripe charge.
 */
trait PPCart_Stripe_Sync_Refund_Records_Trait
{
    /**
     * Whether the last refund sync left a refund record for a person to check.
     *
     * @var bool
     */
    private static $refund_needs_check = false;

    /**
     * Build the refund log to store for an order from its Stripe charge.
     *
     * Refunds the log already has keep their record. Stripe refunds it lacks
     * are added. When the order also has manual refund entries (for example
     * one migrated from StudioCart), the manual entries are replaced by the
     * Stripe refunds if their totals match, or if Stripe fully refunded the
     * charge, so a refund isn't counted twice. Otherwise nothing changes and
     * the order log says so.
     *
     * @param int          $order_id Local order ID.
     * @param object|array $charge   Stripe charge resource with refunds expanded.
     * @param string       $currency Stripe currency code.
     * @return array|false The refund log to store, or false to leave the refund record alone.
     */
    private static function refund_log_for_charge($order_id, $charge, $currency)
    {
        self::$refund_needs_check = false;

        $current = ppcart_get_post_meta($order_id, 'refund_log', true);
        $current = self::normalize_refund_log(is_array($current) ? $current : []);
        // A refund logged while pending that Stripe now reports as failed or
        // canceled returned no money.
        $current = array_diff_key($current, array_flip(self::returned_refund_ids($charge)));

        $stripe_refunds = self::refund_entries_from_charge($charge, $currency);
        $stripe_entries = wp_list_pluck($stripe_refunds, 'entry');
        $new_refunds = array_diff_key($stripe_refunds, $current);
        $manual_entries = array_filter(
            $current,
            static function ($entry) {
                return isset($entry['refundID']) && 'manual' === $entry['refundID'];
            }
        );
        if (! $new_refunds || ! $manual_entries) {
            return self::drop_recorded_manual_refunds($order_id, $current + $stripe_entries, $stripe_refunds, $charge, $currency);
        }

        $manual_minor = self::log_total_minor($manual_entries, $currency);
        $stripe_minor = array_sum(wp_list_pluck($new_refunds, 'minor'));
        $stripe_ids = implode(', ', array_keys($new_refunds));
        $amount = absint(self::get($charge, 'amount', 0));
        $fully_refunded = $amount > 0 && absint(self::get($charge, 'amount_refunded', 0)) >= $amount;

        if ($manual_minor === $stripe_minor) {
            /* translators: 1: manual refund total, 2: Stripe refund IDs */
            $note = __('Manual refund of %1$s matched to Stripe refund %2$s.', 'publishpress-cart');
        } elseif ($fully_refunded) {
            /* translators: 1: manual refund total, 2: Stripe refund IDs */
            $note = __('Manual refund of %1$s replaced by Stripe refund %2$s, because Stripe fully refunded this payment.', 'publishpress-cart');
        } else {
            self::$refund_needs_check = true;
            self::add_order_log_note(
                $order_id,
                sprintf(
                    /* translators: 1: Stripe refund IDs, 2: Stripe refund total, 3: manual refund total */
                    __('Stripe refund %1$s (%2$s) was not recorded because the manual refund total (%3$s) is different. Check the refunds in Stripe and update this order.', 'publishpress-cart'),
                    $stripe_ids,
                    ppcart_format_stripe_number($stripe_minor, $currency),
                    ppcart_format_stripe_number($manual_minor, $currency)
                )
            );
            return false;
        }

        self::add_order_log_note($order_id, sprintf($note, ppcart_format_stripe_number($manual_minor, $currency), $stripe_ids));

        return array_diff_key($current, $manual_entries) + $stripe_entries;
    }

    /**
     * Drop manual refund entries that Stripe refunds in the log already record.
     *
     * An order can keep a manual entry next to the Stripe refunds for the same
     * money, for example after an earlier sync. When the Stripe entries add
     * up to the charge's refunded total, the manual entries are counted twice.
     *
     * @param int          $order_id       Local order ID.
     * @param array        $log            Refund log.
     * @param array        $stripe_refunds Refund entries from the charge, by refund ID.
     * @param object|array $charge         Stripe charge resource.
     * @param string       $currency       Stripe currency code.
     * @return array
     */
    private static function drop_recorded_manual_refunds($order_id, $log, $stripe_refunds, $charge, $currency)
    {
        $manual_entries = array_filter(
            $log,
            static function ($entry) {
                return isset($entry['refundID']) && 'manual' === $entry['refundID'];
            }
        );
        $logged_refunds = array_intersect_key($stripe_refunds, $log);
        $amount_refunded = absint(self::get($charge, 'amount_refunded', 0));
        if (! $manual_entries || ! $logged_refunds || $amount_refunded !== (int) array_sum(wp_list_pluck($logged_refunds, 'minor'))) {
            return $log;
        }

        self::add_order_log_note(
            $order_id,
            sprintf(
                /* translators: 1: manual refund total, 2: Stripe refund IDs */
                __('Manual refund of %1$s removed, because Stripe refund %2$s already records it.', 'publishpress-cart'),
                ppcart_format_stripe_number(self::log_total_minor($manual_entries, $currency), $currency),
                implode(', ', array_keys($logged_refunds))
            )
        );

        return array_diff_key($log, $manual_entries);
    }

    /**
     * IDs of the refunds on a Stripe charge that failed or were canceled.
     *
     * @param object|array $charge Stripe charge resource with refunds expanded.
     * @return array<int, string>
     */
    private static function returned_refund_ids($charge)
    {
        $ids = [];
        $refunds = self::path($charge, [ 'refunds', 'data' ], []);
        foreach (is_array($refunds) ? $refunds : [] as $refund) {
            if (in_array((string) self::get($refund, 'status', ''), [ 'failed', 'canceled' ], true)) {
                $ids[] = (string) self::get($refund, 'id', '');
            }
        }

        return array_filter($ids);
    }

    /**
     * Whether the last refund sync left a refund record for a person to check.
     *
     * @return bool
     */
    public static function refund_needs_check()
    {
        return self::$refund_needs_check;
    }

    /**
     * Refund log entries for the refunds on a Stripe charge.
     *
     * Failed and canceled refunds returned no money, so they are left out.
     *
     * @param object|array $charge   Stripe charge resource with refunds expanded.
     * @param string       $currency Stripe currency code.
     * @return array<string, array{entry: array, minor: int}> By refund ID.
     */
    private static function refund_entries_from_charge($charge, $currency)
    {
        $entries = [];
        $refunds = self::path($charge, [ 'refunds', 'data' ], []);
        foreach (is_array($refunds) ? $refunds : [] as $refund) {
            $refund_id = (string) self::get($refund, 'id', '');
            if ('' === $refund_id || in_array((string) self::get($refund, 'status', ''), [ 'failed', 'canceled' ], true)) {
                continue;
            }

            $minor = absint(self::get($refund, 'amount', 0));
            $entries[ $refund_id ] = [
                'entry' => [
                    'refundID' => $refund_id,
                    'date'     => gmdate('Y-m-d H:i', absint(self::get($refund, 'created', time()))),
                    'amount'   => ppcart_format_stripe_number($minor, $currency),
                ],
                'minor' => $minor,
            ];
        }

        return $entries;
    }

    /**
     * Total of refund log entries in Stripe's smallest currency unit.
     *
     * @param array  $entries  Refund log entries.
     * @param string $currency Currency code.
     * @return int
     */
    private static function log_total_minor($entries, $currency)
    {
        $total = 0;
        foreach ($entries as $entry) {
            if (is_array($entry) && isset($entry['amount'])) {
                $total += abs(self::amount_to_minor_units($entry['amount'], $currency));
            }
        }

        return $total;
    }

    /**
     * Read a stored money amount in Stripe's smallest currency unit.
     *
     * Stored amounts use the store's number format, for example "1.039,00"
     * with a decimal comma. The last separator is the decimal separator when
     * one or two digits follow it. With three digits it is a thousand
     * separator, unless it is the store's decimal separator.
     *
     * @param mixed  $amount   Stored amount.
     * @param string $currency Currency code.
     * @return int
     */
    public static function amount_to_minor_units($amount, $currency)
    {
        $factor = in_array(strtoupper((string) $currency), (array) ppcart_get_zero_decimal_currencies(), true) ? 1 : 100;
        if (is_int($amount) || is_float($amount)) {
            return (int) round($amount * $factor);
        }

        $amount = preg_replace('/[^0-9.,\-]/', '', (string) $amount);
        $negative = 0 === strpos($amount, '-');
        $amount = str_replace('-', '', $amount);
        $last = max((int) strrpos($amount, '.'), (int) strrpos($amount, ','));
        $has_separator = false !== strpos($amount, '.') || false !== strpos($amount, ',');

        $decimals = '';
        if ($has_separator) {
            $separator = $amount[ $last ];
            $after = substr($amount, $last + 1);
            $store_decimal = (string) get_option('_ppcart_decimal_separator', '.');
            $store_decimal = '' === $store_decimal ? '.' : $store_decimal;
            if (3 !== strlen($after) || $separator === $store_decimal) {
                $decimals = $after;
                $amount = substr($amount, 0, $last);
            }
        }

        $whole = (int) str_replace([ '.', ',' ], '', $amount);
        $fraction = 1 === $factor ? 0 : (int) round((float) ('0.' . ('' === $decimals ? '0' : $decimals)) * $factor);
        $minor = $whole * $factor + $fraction;

        return $negative ? -$minor : $minor;
    }

    /**
     * Note on the order that its refund needs a person to check.
     *
     * Order-level sync then says so.
     *
     * @param int    $order_id Local order ID.
     * @param string $note     Log entry.
     * @return void
     */
    private static function flag_refund_for_check($order_id, $note)
    {
        self::$refund_needs_check = true;
        self::add_order_log_note($order_id, $note);
    }

    /**
     * Add an order log entry unless the log already has it, so repeated
     * syncs don't fill the log.
     *
     * @param int    $order_id Local order ID.
     * @param string $note     Log entry.
     * @return void
     */
    private static function add_order_log_note($order_id, $note)
    {
        if (! in_array(sanitize_text_field($note), array_values(ppcart_order_log($order_id)), true)) {
            ppcart_log_entry($order_id, $note);
        }
    }
}
