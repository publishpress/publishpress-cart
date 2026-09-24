<?php

if (! defined('ABSPATH')) {
    exit;
}

class PPCart_Admin_Legacy_Tracking_Notice_Controller
{
    private const DISMISS_QUERY_ARG = 'ppcart_dismiss_legacy_tracking_notice';
    private const DISMISS_META_KEY = '_ppcart_dismiss_admin_notice_legacy_tracking';

    public function maybe_dismiss_notice()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Route check only; nonce is verified below.
        if (empty($_GET[ self::DISMISS_QUERY_ARG ])) {
            return;
        }

        ppcart_check_admin_referer(self::DISMISS_QUERY_ARG);

        if (! current_user_can('manage_options')) {
            return;
        }

        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, self::DISMISS_META_KEY, '1');
        }

        wp_safe_redirect(remove_query_arg([ self::DISMISS_QUERY_ARG, '_wpnonce' ]));
        exit;
    }

    public function render_notice()
    {
        if (! current_user_can('manage_options') || $this->is_dismissed() || ! $this->is_cart_admin_screen()) {
            return;
        }

        $products = $this->get_products_with_legacy_tracking_meta();
        if ([] === $products) {
            return;
        }

        $dismiss_url = wp_nonce_url(add_query_arg(self::DISMISS_QUERY_ARG, '1'), self::DISMISS_QUERY_ARG);
        $settings_url = admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS . '#integrations');

        echo '<div class="notice notice-warning">';
        echo '<p><strong>' . esc_html__('Studiocart custom tracking scripts are not available in PublishPress Cart.', 'publishpress-cart') . '</strong> ';
        echo esc_html__('They are replaced by structured Meta Pixel and Google Analytics events. Leftover tracking code on these products is kept in post meta for reference and is not executed.', 'publishpress-cart') . '</p>';
        echo '<p>' . esc_html__('Affected products:', 'publishpress-cart') . ' ';
        $links = [];
        foreach ($products as $product) {
            $links[] = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url(get_edit_post_link($product->ID, '')),
                esc_html(get_the_title($product))
            );
        }
        echo wp_kses_post(implode(', ', $links));
        echo '</p><p><a href="' . esc_url($settings_url) . '">' . esc_html__('Review analytics settings', 'publishpress-cart') . '</a> | ';
        echo '<a href="' . esc_url($dismiss_url) . '">' . esc_html__('Dismiss this notice', 'publishpress-cart') . '</a></p>';
        echo '</div>';
    }

    private function is_dismissed()
    {
        $user_id = get_current_user_id();
        return $user_id && get_user_meta($user_id, self::DISMISS_META_KEY, true);
    }

    private function is_cart_admin_screen()
    {
        return class_exists('PPCart_Admin_Screens') && PPCart_Admin_Screens::is_plugin_screen();
    }

    private function get_products_with_legacy_tracking_meta()
    {
        $post_types = function_exists('ppcart_query_post_types') ? ppcart_query_post_types('product') : [ 'ppcart_product' ];
        $query = new WP_Query([
            'post_type'              => $post_types,
            'post_status'            => 'any',
            'posts_per_page'         => 10,
            'fields'                 => 'all',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => [
                'relation' => 'OR',
                [
                    'key'     => '_ppcart_tracking_main',
                    'compare' => 'EXISTS',
                ],
                [
                    'key'     => '_ppcart_tracking_lead',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        return $query->posts;
    }
}
