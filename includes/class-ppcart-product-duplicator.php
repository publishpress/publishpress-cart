<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Product duplication helpers.
 *
 * @package PPCart
 */

class PPCart_Product_Duplicator
{
    public const PRODUCT_POST_TYPE = 'ppcart_product';
    public const OPTION_NAME       = '_ppcart_product_duplicate_enable';

    /**
     * Live product post type for inserts.
     *
     * @return string
     */
    public static function product_post_type()
    {
        return function_exists('ppcart_live_post_type') ? ppcart_live_post_type('product') : self::PRODUCT_POST_TYPE;
    }

    /**
     * Determine whether product duplication is enabled.
     *
     * @return bool
     */
    public static function is_enabled()
    {
        return '0' !== (string) get_option(self::OPTION_NAME, '1');
    }

    /**
     * Duplicate a PublishPress Cart product.
     *
     * @param int $source_id Source product post ID.
     * @return int|WP_Error
     */
    public static function duplicate($source_id)
    {
        $source_id = absint($source_id);
        $source    = get_post($source_id);

        if (! $source) {
            return new WP_Error('invalid_product', __('Invalid product selected for duplication.', 'publishpress-cart'));
        }

        $is_product = function_exists('ppcart_is_product_post_type')
            ? ppcart_is_product_post_type($source->post_type)
            : self::PRODUCT_POST_TYPE === $source->post_type;

        if (! $is_product) {
            return new WP_Error('invalid_product', __('Invalid product selected for duplication.', 'publishpress-cart'));
        }

        $duplicate_id = wp_insert_post(
            [
                'post_author'    => get_current_user_id() ? get_current_user_id() : $source->post_author,
                'post_content'   => $source->post_content,
                'post_excerpt'   => $source->post_excerpt,
            // Product posts are hierarchical, so child products should stay under the same parent when duplicated.
                'post_parent'    => $source->post_parent,
                'post_status'    => 'draft',
                'post_title'     => sprintf(
                    /* translators: %s: Source product title. */
                    __('Copy of %s', 'publishpress-cart'),
                    $source->post_title
                ),
                'post_type'      => self::product_post_type(),
                'comment_status' => $source->comment_status,
                'ping_status'    => $source->ping_status,
                'menu_order'     => $source->menu_order,
            ],
            true
        );

        if (is_wp_error($duplicate_id)) {
            return $duplicate_id;
        }

        self::copy_meta($source_id, $duplicate_id);
        self::copy_taxonomies($source_id, $duplicate_id);

        return $duplicate_id;
    }

    /**
     * Copy product metadata to the duplicate.
     *
     * Stripe product, price, and coupon IDs are external identities, not local product
     * configuration. Clearing them keeps copied payment plan rows intact while
     * allowing the duplicate's next save to create/reconcile its own Stripe objects.
     *
     * @param int $source_id    Source product post ID.
     * @param int $duplicate_id Duplicate product post ID.
     * @return void
     */
    public static function copy_meta($source_id, $duplicate_id)
    {
        $all_meta      = get_post_meta($source_id);
        $excluded_meta = self::get_excluded_meta_keys();

        foreach ($all_meta as $meta_key => $meta_values) {
            if (in_array($meta_key, $excluded_meta, true)) {
                continue;
            }

            $write_key = $meta_key;
            if (function_exists('apply_filters')) {
                $write_key = apply_filters(
                    'ppcart_product_duplicate_meta_key',
                    $meta_key,
                    $all_meta,
                    $source_id,
                    $duplicate_id
                );
            }

            if (null === $write_key || '' === $write_key) {
                continue;
            }

            // Free copies canonical `_ppcart_*` and core WordPress meta only.
            // cart-compat rewrites leftover source rows via
            // ppcart_product_duplicate_meta_key while on.
            if (! self::is_copyable_product_duplicate_meta_key($write_key)) {
                continue;
            }

            foreach ((array) $meta_values as $meta_value) {
                $meta_value = maybe_unserialize($meta_value);
                $meta_value = self::prepare_meta_value_for_duplicate($write_key, $meta_value);

                add_post_meta($duplicate_id, $write_key, $meta_value);
            }
        }
    }

    /**
     * Copy all taxonomies attached to products.
     *
     * @param int $source_id    Source product post ID.
     * @param int $duplicate_id Duplicate product post ID.
     * @return void
     */
    public static function copy_taxonomies($source_id, $duplicate_id)
    {
        $taxonomies = get_object_taxonomies(self::product_post_type());

        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_object_terms(
                $source_id,
                $taxonomy,
                [
                    'fields' => 'ids',
                ]
            );

            if (is_wp_error($terms)) {
                continue;
            }

            wp_set_object_terms($duplicate_id, array_map('intval', $terms), $taxonomy, false);
        }
    }

    /**
     * Return meta keys that should not be copied to a newly duplicated product.
     *
     * @return array
     */
    public static function get_excluded_meta_keys()
    {
        $excluded_meta = [
            '_edit_last',
            '_edit_lock',
            '_wp_desired_post_slug',
            '_wp_old_slug',
            '_wp_trash_meta_status',
            '_wp_trash_meta_time',
            function_exists('ppcart_meta_key') ? ppcart_meta_key('stripe_prod_id') : '_ppcart_stripe_prod_id',
        ];

        if (function_exists('apply_filters')) {
            $excluded_meta = apply_filters('ppcart_product_duplicate_excluded_meta_keys', $excluded_meta);
        }

        return array_unique(array_map('strval', $excluded_meta));
    }

    /**
     * Whether a meta key may be written to a duplicated product.
     *
     * @param string $meta_key Meta key after filters.
     * @return bool
     */
    private static function is_copyable_product_duplicate_meta_key($meta_key)
    {
        $meta_key = (string) $meta_key;

        if (0 === strpos($meta_key, '_ppcart_')) {
            return true;
        }

        if ('_thumbnail_id' === $meta_key) {
            return true;
        }

        if (0 === strpos($meta_key, '_wp_')) {
            return true;
        }

        return false;
    }

    /**
     * Prepare a copied meta value for a new product.
     *
     * @param string $meta_key   Meta key.
     * @param mixed  $meta_value Meta value.
     * @return mixed
     */
    public static function prepare_meta_value_for_duplicate($meta_key, $meta_value)
    {
        if (! is_array($meta_value)) {
            return $meta_value;
        }

        if (function_exists('ppcart_is_meta_field_id') && ppcart_is_meta_field_id($meta_key, 'pay_options')) {
            foreach ($meta_value as $index => $payment_plan) {
                if (! is_array($payment_plan)) {
                    continue;
                }

                $meta_value[ $index ]['stripe_plan_id']      = '';
                $meta_value[ $index ]['sale_stripe_plan_id'] = '';
            }
        }

        if (function_exists('ppcart_is_meta_field_id') && ppcart_is_meta_field_id($meta_key, 'coupons')) {
            foreach ($meta_value as $index => $coupon) {
                if (! is_array($coupon)) {
                    continue;
                }

                $meta_value[ $index ]['stripe_id'] = '';
            }
        }

        return $meta_value;
    }
}
