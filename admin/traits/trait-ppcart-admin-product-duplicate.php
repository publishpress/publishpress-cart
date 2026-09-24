<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Product list-table row action and handler for duplicating a product post.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
trait PPCart_Admin_Product_Duplicate_Trait
{
    /**
     * Add the product duplicate row action on the Products list table.
     *
     * @param array   $actions Existing row actions.
     * @param WP_Post $post    Current post.
     * @return array
     */
    public function add_product_duplicate_row_action($actions, $post)
    {
        if (
            ! PPCart_Product_Duplicator::is_enabled()
            || ! $post
            || ! ppcart_is_product_post_type($post->post_type)
            || ! current_user_can('edit_post', $post->ID)
            || ! $this->current_user_can_create_product()
        ) {
            return $actions;
        }

        $url = wp_nonce_url(
            add_query_arg(
                [
                    'action' => 'ppcart_duplicate_product',
                    'post'   => $post->ID,
                ],
                admin_url('admin.php')
            ),
            'ppcart_duplicate_product_' . $post->ID
        );

        $actions['ppcart_duplicate_product'] = sprintf(
            '<a href="%1$s" aria-label="%2$s">%3$s</a>',
            esc_url($url),
            esc_attr(
                sprintf(
                    /* translators: %s: Product title. */
                    __('Duplicate %s', 'publishpress-cart'),
                    $post->post_title
                )
            ),
            esc_html__('Duplicate', 'publishpress-cart')
        );

        return $actions;
    }

    /**
     * Determine whether the current user can create products.
     *
     * @return bool
     */
    private function current_user_can_create_product()
    {
        $post_type_slug = function_exists('ppcart_live_post_type') ? ppcart_live_post_type('product') : 'ppcart_product';
        $post_type      = get_post_type_object($post_type_slug);

        if (! $post_type || empty($post_type->cap->create_posts)) {
            return false;
        }

        return current_user_can($post_type->cap->create_posts);
    }

    /**
     * Handle the nonce-protected product duplicate action.
     *
     * @return void
     */
    public function handle_product_duplicate_action()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is checked immediately after the source post ID is read.
        $source_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;

        if (! $source_id) {
            wp_die(esc_html__('Invalid product selected for duplication.', 'publishpress-cart'));
        }

        ppcart_check_admin_referer('ppcart_duplicate_product_' . $source_id);

        if (! ppcart_is_product_post_type(get_post_type($source_id))) {
            wp_die(esc_html__('Invalid product selected for duplication.', 'publishpress-cart'));
        }

        if (! current_user_can('edit_post', $source_id)) {
            wp_die(esc_html__('You do not have permission to duplicate this product.', 'publishpress-cart'));
        }

        if (! $this->current_user_can_create_product()) {
            wp_die(esc_html__('You do not have permission to create products.', 'publishpress-cart'));
        }

        if (! PPCart_Product_Duplicator::is_enabled()) {
            wp_die(esc_html__('Product duplication is disabled in settings.', 'publishpress-cart'));
        }

        $duplicate_id = PPCart_Product_Duplicator::duplicate($source_id);

        if (is_wp_error($duplicate_id)) {
            wp_die(esc_html($duplicate_id->get_error_message()));
        }

        wp_safe_redirect(
            add_query_arg(
                [
                    'post'                  => $duplicate_id,
                    'action'                => 'edit',
                    'ppcart_product_duplicated' => $duplicate_id,
                ],
                admin_url('post.php')
            )
        );
        exit;
    }

    /**
     * Print a confirmation notice after product duplication.
     *
     * @return void
     */
    public function product_duplicate_admin_notice()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice routing after a completed admin action.
        $duplicate_id = isset($_GET['ppcart_product_duplicated']) ? absint(wp_unslash($_GET['ppcart_product_duplicated'])) : 0;

        if (! $duplicate_id || ! ppcart_is_product_post_type(get_post_type($duplicate_id))) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && ! ppcart_is_product_post_type($screen->post_type)) {
            return;
        }

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: %d: New product post ID. */
                    __('Product duplicated. New product ID: %d.', 'publishpress-cart'),
                    $duplicate_id
                )
            )
        );
    }

    public function register_importers()
    {
        if (defined('WP_LOAD_IMPORTERS')) {
            do_action('ppcart_register_importers');
        }
    }
}
