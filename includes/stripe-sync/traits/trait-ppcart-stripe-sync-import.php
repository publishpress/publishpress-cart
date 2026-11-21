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
     * @param string $price_id Stripe price ID.
     * @return int Product ID, or 0.
     */
    public static function find_product_for_stripe_price($price_id)
    {
        global $wpdb;

        $price_id = sanitize_text_field((string) $price_id);
        if ('' === $price_id) {
            return 0;
        }

        $post_types = (array) ppcart_query_post_types('product');
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One admin lookup by Stripe price across product price settings.
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders are built from the post type list.
                "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID WHERE p.post_type IN ($placeholders) AND p.post_status <> 'trash' AND m.meta_value LIKE %s ORDER BY p.ID DESC LIMIT 1",
                array_merge($post_types, [ '%' . $wpdb->esc_like($price_id) . '%' ])
            )
        );

        return absint($product_id);
    }
}
