<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Import Stripe subscriptions that have no PublishPress Cart record.
 */
trait PPCart_Stripe_Sync_Import_Trait
{
    /**
     * Create a local subscription from an existing Stripe subscription, then
     * import its paid invoices as orders.
     *
     * Integrations don't run, because the customer's access was handled when
     * they first paid.
     *
     * @param string $stripe_subscription_id Stripe subscription ID (sub_...).
     * @param array  $args {
     *     @type int    $product_id Local product. Found from the Stripe price when empty.
     *     @type string $mode       Stripe mode (test or live). The site's mode when empty.
     * }
     * @return PPCart_Subscription|WP_Error
     */
    public static function import_subscription($stripe_subscription_id, $args = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-import-import-subscription.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Import one Stripe payment as a paid order.
     *
     * For payments with no invoice, such as a charge made by hand in the
     * Stripe Dashboard or a one-time purchase made before the store moved to
     * Cart. Sync with Stripe imports only invoices, so it never finds them.
     * Give a subscription to add the order to it, or a product to import a
     * one-time order. Give exactly one of them. Integrations don't run, and
     * nothing changes in Stripe.
     *
     * @param string $payment_id      Stripe charge (ch_...) or PaymentIntent (pi_...) ID.
     * @param int    $subscription_id Local subscription ID, or 0.
     * @param int    $product_id      Local product ID for a one-time order, or 0.
     * @return PPCart_Order|WP_Error
     */
    public static function import_charge($payment_id, $subscription_id, $product_id = 0)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-import-import-charge.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Import one Stripe payment as a one-time order of a product.
     *
     * @param string $payment_id Stripe charge (ch_...) or PaymentIntent (pi_...) ID, already checked.
     * @param int    $product_id Local product ID.
     * @return PPCart_Order|WP_Error
     */
    private static function import_one_time_charge($payment_id, $product_id)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-import-import-one-time-charge.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Get a Stripe charge that can be imported as an order.
     *
     * Only reads from Stripe. Refuses a payment that didn't succeed, paid an
     * invoice, or is already an order.
     *
     * @param string $payment_id Stripe charge (ch_...) or PaymentIntent (pi_...) ID.
     * @param string $mode       Stripe mode (test or live).
     * @return object|WP_Error The charge.
     */
    private static function retrieve_import_charge($payment_id, $mode)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-import-retrieve-import-charge.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Work out the amounts of a Stripe charge to import.
     *
     * Uses the captured amount when Stripe gives it and the charge isn't
     * fully refunded. A fully refunded charge keeps its full amount.
     *
     * @param object $charge Stripe charge.
     * @return array{cents: int, refunded_cents: int, fully_refunded: bool}
     */
    private static function import_charge_amounts($charge)
    {
        $amount = absint(self::get($charge, 'amount', 0));
        $captured = self::get($charge, 'amount_captured', null);
        $base = null !== $captured ? absint($captured) : $amount;
        $refunded = absint(self::get($charge, 'amount_refunded', 0));
        $fully_refunded = $refunded > 0 && $refunded >= $base;

        return [
            'cents'          => $fully_refunded ? $amount : $base,
            'refunded_cents' => $refunded,
            'fully_refunded' => $fully_refunded,
        ];
    }

    /**
     * Turn a Stripe amount in the currency's smallest unit into a plain
     * number, as checkout stores it. Zero-decimal currencies, such as JPY,
     * aren't divided.
     *
     * ppcart_format_stripe_number() isn't used here, because it applies the
     * store's thousand separator ("1,000.00"), which isn't a number.
     *
     * @param int    $cents    Amount in the smallest unit.
     * @param string $currency Currency code.
     * @return float
     */
    private static function stripe_amount_to_number($cents, $currency)
    {
        $cents = absint($cents);
        if (in_array(strtoupper((string) $currency), array_map('strtoupper', (array) ppcart_get_zero_decimal_currencies()), true)) {
            return (float) $cents;
        }

        return round($cents / 100, 2);
    }

    /**
     * Refuse a payment that is already an order.
     *
     * @param array $ids Stripe charge and PaymentIntent IDs.
     * @return WP_Error|false An error that names the order, or false.
     */
    private static function find_imported_charge_order($ids)
    {
        foreach (array_filter($ids) as $id) {
            $order = PPCart_Order::get_by_trans_id($id);
            if ($order && $order->id) {
                return new WP_Error(
                    'ppcart_import_charge_exists',
                    /* translators: %d: order ID */
                    sprintf(__('This payment is already order #%d.', 'publishpress-cart'), (int) $order->id),
                    [ 'order_id' => (int) $order->id ]
                );
            }
        }

        return false;
    }

    /**
     * Find the local product that sells a Stripe price.
     *
     * A price ID matches only when a payment plan of the product stores
     * exactly that ID as its Stripe price or sale price.
     *
     * @param string $price_id Stripe price ID.
     * @return int Product ID, or 0.
     */
    public static function find_product_for_stripe_price($price_id)
    {
        $price_id = sanitize_text_field((string) $price_id);
        if ('' === $price_id) {
            return 0;
        }

        // The LIKE on the quoted value only narrows the candidates. The loop
        // below compares the plan fields exactly.
        $candidates = get_posts(
            [
                'post_type'      => ppcart_query_post_types('product'),
                'post_status'    => 'any',
                'posts_per_page' => 50,
                'fields'         => 'ids',
                'orderby'        => 'ID',
                'order'          => 'DESC',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- One admin lookup by Stripe price across product payment plans.
                'meta_query'     => [
                    [
                        'key'     => ppcart_meta_key('pay_options'),
                        'value'   => '"' . $price_id . '"',
                        'compare' => 'LIKE',
                    ],
                ],
            ]
        );

        foreach ($candidates as $candidate_id) {
            $plans = ppcart_get_post_meta($candidate_id, 'pay_options', true);
            if (! is_array($plans)) {
                continue;
            }

            foreach ($plans as $plan) {
                if (! is_array($plan)) {
                    continue;
                }

                foreach ([ 'stripe_plan_id', 'sale_stripe_plan_id' ] as $field) {
                    if (isset($plan[ $field ]) && $price_id === (string) $plan[ $field ]) {
                        return (int) $candidate_id;
                    }
                }
            }
        }

        return 0;
    }
}
