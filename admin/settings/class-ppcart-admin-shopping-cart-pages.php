<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PPCart_Admin_Shopping_Cart_Pages
{
    public function __construct(private PPCart_Shopping_Cart_Settings $settings)
    {
    }

    public function create_missing_pages(): void
    {
        if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
            wp_die(esc_html__('Use the Create missing pages button to create pages.', 'publishpress-cart'), '', ['response' => 405]);
        }
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You cannot create shop pages.', 'publishpress-cart'), '', ['response' => 403]);
        }
        check_admin_referer('ppcart_shopping_cart_create_pages', '_ppcart_shopping_cart_pages_nonce');
        $result = $this->settings->create_missing_pages();
        $notice = is_wp_error($result) ? $result->get_error_message() : __('Shop, Cart and Checkout pages are ready.', 'publishpress-cart');
        set_transient('ppcart_shopping_cart_pages_notice_' . get_current_user_id(), ['message' => $notice, 'error' => is_wp_error($result)], MINUTE_IN_SECONDS);
        wp_safe_redirect(admin_url('admin.php?page=' . PPCart_Admin_Screens::PAGE_SETTINGS . '#shop_cart'));
        exit;
    }

    public static function render_control(): void
    {
        echo '<button type="submit" class="button" form="ppcart-shopping-cart-pages-form">' . esc_html__('Create missing pages', 'publishpress-cart') . '</button>';
    }

    public function render_form(): void
    {
        if (! PPCart_Admin_Screens::is_settings_screen()) {
            return;
        }
        echo '<form id="ppcart-shopping-cart-pages-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" hidden>';
        echo '<input type="hidden" name="action" value="ppcart_shopping_cart_create_pages">';
        wp_nonce_field('ppcart_shopping_cart_create_pages', '_ppcart_shopping_cart_pages_nonce', false);
        echo '</form>';
    }

    public function render_notice(): void
    {
        $key = 'ppcart_shopping_cart_pages_notice_' . get_current_user_id();
        $notice = get_transient($key);
        if (! $notice) {
            return;
        }
        delete_transient($key);
        echo '<div class="notice ' . ($notice['error'] ? 'notice-error' : 'notice-success') . '"><p>' . esc_html($notice['message']) . '</p></div>';
    }
}
