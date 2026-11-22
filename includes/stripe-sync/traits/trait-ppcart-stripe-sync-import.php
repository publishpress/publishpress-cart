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
