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
 * Every Content-Security-Policy the site already sent is kept without its
 * `frame-ancestors` directive, so the embed lifts the framing rule and no other
 * protection. Each line is sent as its own header; browsers enforce them all.
 *
 * @param mixed              $sources           Frame ancestors from the `ppcart_embed_frame_ancestors` filter.
 * @param array<int, string> $existing_policies Values of the CSP headers already set for this response.
 * @return array<int, string>
 */
function ppcart_embed_frame_header_lines($sources, $existing_policies = [])
{
    $lines = [];

    foreach ((array) $existing_policies as $policy) {
        $kept = [];

        foreach (explode(';', (string) $policy) as $directive) {
            $directive = trim($directive);
            $name      = strtolower((string) strtok($directive, " \t"));

            if ('' !== $directive && 'frame-ancestors' !== $name) {
                $kept[] = $directive;
            }
        }

        if ([] !== $kept) {
            $lines[] = 'Content-Security-Policy: ' . implode('; ', $kept);
        }
    }

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

    $lines[] = 'Content-Security-Policy: frame-ancestors ' . implode(' ', array_values($clean));

    return $lines;
}

/**
 * Whether a redirect target is on another origin than the site. Pure PHP.
 *
 * @param string $location Redirect target.
 * @param string $site_url Site home URL.
 * @return bool
 */
function ppcart_embed_redirect_leaves_site($location, $site_url)
{
    $target = parse_url((string) $location);
    $site   = parse_url((string) $site_url);

    if (! is_array($target) || empty($target['host'])) {
        return false;
    }

    $origin = static function ($parts) {
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : 'https';
        $port   = isset($parts['port']) ? (int) $parts['port'] : ('http' === $scheme ? 80 : 443);

        return $scheme . '://' . strtolower(isset($parts['host']) ? $parts['host'] : '') . ':' . $port;
    };

    return ! is_array($site) || $origin($target) !== $origin($site);
}

/**
 * The page an embed request returns instead of an HTTP redirect to another site.
 * Pure PHP. A 302 would load that site inside the frame, where it is often
 * refused; the frame bridge asks the host page to open it in the top window.
 *
 * @param string $location   Redirect target.
 * @param string $bridge_src URL of public/js/ppcart-embed-frame.js.
 * @return string
 */
function ppcart_embed_bounce_page($location, $bridge_src)
{
    $flags  = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;
    $href   = htmlspecialchars((string) $location, ENT_QUOTES, 'UTF-8');
    $source = htmlspecialchars((string) $bridge_src, ENT_QUOTES, 'UTF-8');

    return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="robots" content="noindex"><title>Redirecting</title></head>'
        . '<body><p><a href="' . $href . '" target="_top">Continue</a></p>'
        . '<script src="' . $source . '"></script>'
        . '<script>window.ppcartNavigate(' . json_encode((string) $location, $flags) . ');</script>'
        . '</body></html>';
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
 * Posts the embed may frame: products, pages that show a checkout or a receipt,
 * and any page reached with a valid order access token (a confirmation page the
 * merchant picked). Any other page keeps the site's own framing policy, so the
 * flag cannot lift X-Frame-Options from, for example, an account page.
 *
 * @param WP_Post $post Queried post.
 * @return bool
 */
function ppcart_embed_allows_post($post)
{
    $allowed = ppcart_is_product_post_type($post->post_type)
        || has_shortcode($post->post_content, 'ppcart_form')
        || has_shortcode($post->post_content, 'ppcart_receipt')
        || has_block('publishpress-cart/checkout-form', $post)
        || ppcart_embed_request_has_order_access();

    /**
     * Whether the embedded checkout may frame this post, for example an upsell page.
     *
     * @param bool    $allowed Products and pages with a checkout or a receipt.
     * @param WP_Post $post    Queried post.
     */
    return (bool) apply_filters('ppcart_embed_allows_post', $allowed, $post);
}

/**
 * Whether the request carries `ppcart-order` with a token that opens that order,
 * as a checkout's confirmation redirect does.
 *
 * @return bool
 */
function ppcart_embed_request_has_order_access()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; access is proven by the order token.
    $order_id = isset($_GET['ppcart-order']) && is_scalar($_GET['ppcart-order']) ? absint(wp_unslash($_GET['ppcart-order'])) : 0;

    return $order_id > 0 && class_exists('PPCart_Order') && PPCart_Order::visitor_can_view($order_id);
}
