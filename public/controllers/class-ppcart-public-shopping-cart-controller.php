<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PPCart_Public_Shopping_Cart_Controller
{
    public function __construct(private PPCart_Shopping_Cart_Settings $settings = new PPCart_Shopping_Cart_Settings())
    {
        add_shortcode('ppcart_shop', 'ppcart_render_shop');
        add_shortcode('ppcart_mini_cart', 'ppcart_render_mini_cart');
        add_shortcode('ppcart_cart', 'ppcart_render_shopping_cart');
        add_shortcode('ppcart_cart_checkout', 'ppcart_render_shopping_cart_checkout');
    }

    public function template_redirect(): void
    {
        $post = get_queried_object();
        $pages = [absint(get_option('_ppcart_cart_page_id')), absint(get_option('_ppcart_cart_checkout_page_id'))];
        if (($post instanceof WP_Post && in_array($post->ID, array_filter($pages), true)) || ($post instanceof WP_Post && $this->has_private_cart($post->post_content))) {
            ppcart_shopping_cart_nocache();
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_key(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- handle_post validates the operation's nonce before any mutation.
        if ('post' !== $method || (! isset($_POST['ppcart_add_to_cart']) && ! isset($_POST['ppcart_remove_from_cart']))) {
            return;
        }
        ppcart_shopping_cart_nocache();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- handle_post validates the operation's nonce before any mutation.
        $result = $this->handle_post(wp_unslash($_POST));
        if (is_wp_error($result)) {
            wp_die(esc_html($result->get_error_message()), '', ['response' => 'ppcart_cart_invalid_nonce' === $result->get_error_code() ? 403 : 422]);
        }
        wp_safe_redirect($result, 303);
        exit;
    }

    public function handle_post(array $post): string|WP_Error
    {
        $adding = isset($post['ppcart_add_to_cart']);
        $action = $adding ? 'ppcart_add_to_cart' : 'ppcart_remove_from_cart';
        if (! is_string($post['ppcart_shopping_cart_nonce'] ?? null) || ! wp_verify_nonce($post['ppcart_shopping_cart_nonce'], $action)) {
            return new WP_Error('ppcart_cart_invalid_nonce', __('Your session expired. Reload the page and try again.', 'publishpress-cart'));
        }
        $store = new PPCart_Shopping_Cart_Store();
        if ($adding) {
            if (! is_scalar($post['ppcart_add_to_cart']) || ! is_string($post['plan_id'] ?? null)) {
                return new WP_Error('ppcart_cart_invalid_input', __('Choose a product and payment plan.', 'publishpress-cart'));
            }
            $product_id = absint($post['ppcart_add_to_cart']);
            $plan_id = sanitize_text_field($post['plan_id']);
            $cart = $store->update(static fn (PPCart_Shopping_Cart $cart) => $cart->add_item($product_id, $plan_id), true);
        } else {
            if (! is_string($post['ppcart_remove_from_cart'] ?? null)) {
                return new WP_Error('ppcart_cart_invalid_input', __('Choose an item to remove.', 'publishpress-cart'));
            }
            $key = sanitize_text_field($post['ppcart_remove_from_cart']);
            $cart = $store->update(static fn (PPCart_Shopping_Cart $cart) => $cart->remove_item($key));
        }
        if (is_wp_error($cart) && 'ppcart_cart_already_in_cart' !== $cart->get_error_code()) {
            return $cart;
        }
        $urls = $this->settings->urls();
        return ($adding && 'checkout' === $this->settings->after_add() ? $urls['checkout'] : $urls['cart']) ?: ($urls['shop'] ?: home_url('/'));
    }

    public function footer(): void
    {
        if (! wp_script_is('ppcart-shopping-cart', 'enqueued')) {
            return;
        }
        echo '<div class="ppcart-shopping-cart ppcart-shopping-cart-overlay" data-ppcart-shopping-cart-overlay hidden>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The View renders escaped templates.
        echo (new PPCart_Shopping_Cart_View())->render('drawer', PPCart_Shopping_Cart_Priced::empty());
        echo '</div><div class="ppcart-shopping-cart-sr" data-ppcart-shopping-cart-announcement role="status" aria-live="polite" aria-atomic="true"></div>';
    }

    public function script_tag(string $tag, string $handle): string
    {
        return 'ppcart-shopping-cart' === $handle ? str_replace('<script ', '<script type="module" ', $tag) : $tag;
    }

    private function has_private_cart(string $content): bool
    {
        if (has_shortcode($content, 'ppcart_cart') || has_shortcode($content, 'ppcart_cart_checkout') || has_block('publishpress-cart/cart', $content)) {
            return true;
        }
        foreach (parse_blocks($content) as $block) {
            if ('publishpress-cart/checkout-form' === $block['blockName'] && 'cart' === ($block['attrs']['pid'] ?? '')) {
                return true;
            }
            if (! empty($block['innerBlocks']) && $this->has_private_cart(serialize_blocks($block['innerBlocks']))) {
                return true;
            }
        }
        return false;
    }
}
