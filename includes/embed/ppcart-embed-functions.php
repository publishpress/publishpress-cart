<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-ppcart-embed-appearance.php';

/**
 * Header lines an embed response sends so the host page may frame it.
 *
 * Pure PHP. A source is kept only when it is a plain CSP source expression
 * (`*`, `'self'`, `https://shop.example`, `https://*.example.com:8443`).
 * With no valid source nothing may frame the page.
 *
 * @param mixed $sources Frame ancestors from the `ppcart_embed_frame_ancestors` filter.
 * @return array<int, string>
 */
function ppcart_embed_frame_header_lines($sources)
{
    $clean = [];

    foreach ((array) $sources as $source) {
        if (! is_string($source)) {
            continue;
        }

        $source = trim($source);

        if ('' === $source || ! preg_match('#^[A-Za-z0-9*.:/\'_\[\]-]+$#', $source)) {
            continue;
        }

        $clean[ $source ] = $source;
    }

    if ([] === $clean) {
        $clean = [ "'none'" ];
    }

    return [ 'Content-Security-Policy: frame-ancestors ' . implode(' ', array_values($clean)) ];
}

/**
 * Saved embed appearance of a product.
 *
 * @param int $product_id Product post ID.
 * @return array<string, string|int> See PPCart_Embed_Appearance::sanitize().
 */
function ppcart_embed_appearance($product_id)
{
    $raw = [];

    foreach (PPCart_Embed_Appearance::KEYS as $key) {
        $raw[ $key ] = ppcart_get_post_meta(absint($product_id), 'embed_' . $key, true);
    }

    return PPCart_Embed_Appearance::sanitize($raw);
}

/**
 * The `ppcart-embed` query arg of the current request, normalized.
 *
 * @return string '' when absent or empty, 'popup' for the popup link, '1' for any other value.
 */
function ppcart_embed_request_mode()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing flag.
    if (! isset($_GET['ppcart-embed']) || ! is_scalar($_GET['ppcart-embed'])) {
        return '';
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing flag.
    $mode = sanitize_key(wp_unslash($_GET['ppcart-embed']));

    if ('' === $mode) {
        return '';
    }

    return 'popup' === $mode ? 'popup' : '1';
}

/**
 * Whether the current request is an embedded checkout: a singular front-end request
 * for a post the embed may frame, with a non-empty `ppcart-embed` query arg.
 *
 * @return bool
 */
function ppcart_is_embed_request()
{
    if (is_admin() || ! is_singular() || '' === ppcart_embed_request_mode()) {
        return false;
    }

    $post = get_queried_object();

    return $post instanceof WP_Post && ppcart_embed_allows_post($post);
}

/**
 * Posts the embed may frame: products, and pages that show a checkout or a receipt.
 * Any other page keeps the site's own framing policy, so the flag cannot lift
 * X-Frame-Options from, for example, an account page.
 *
 * @param WP_Post $post Queried post.
 * @return bool
 */
function ppcart_embed_allows_post($post)
{
    $allowed = ppcart_is_product_post_type($post->post_type)
        || has_shortcode($post->post_content, 'ppcart_form')
        || has_shortcode($post->post_content, 'ppcart_receipt')
        || has_block('publishpress-cart/checkout-form', $post);

    /**
     * Whether the embedded checkout may frame this post, for example an upsell page.
     *
     * @param bool    $allowed Products and pages with a checkout or a receipt.
     * @param WP_Post $post    Queried post.
     */
    return (bool) apply_filters('ppcart_embed_allows_post', $allowed, $post);
}
