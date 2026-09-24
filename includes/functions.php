<?php

if (! defined('ABSPATH')) {
    exit;
}


/*
* global functions
*/

add_action('wp_ajax_ppcart_unsubscribe_customer', 'ppcart_unsubscribe_customer'); //unsubscribe stripe

add_action('wp_ajax_ppcart_json_search_user', 'ppcart_json_search_user');
add_action('wp_ajax_ppcart_search_report_customers', 'ppcart_ajax_search_report_customers');
add_action('wp_ajax_ppcart_search_report_products', 'ppcart_ajax_search_report_products');

/**
 * Get or set the Compatibility Mode leftover request-key lookup.
 *
 * @param callable|null $lookup Lookup that returns leftover keys for a canonical key, or null to clear.
 * @param bool          $set    Whether to replace the current lookup.
 * @return callable|null
 */
function ppcart_request_field_compatibility_lookup($lookup = null, $set = false)
{
    static $registered_lookup = null;

    if ($set) {
        $registered_lookup = is_callable($lookup) ? $lookup : null;
    }

    return $registered_lookup;
}

/**
 * Whether a canonical request key (or a mapped leftover key) is present.
 *
 * @param int    $type           INPUT_POST or INPUT_GET.
 * @param string $canonical_key Canonical request field name.
 * @return bool
 */
function ppcart_request_has_var($type, $canonical_key)
{
    if (filter_has_var($type, $canonical_key)) {
        return true;
    }

    $lookup = ppcart_request_field_compatibility_lookup();

    if (! is_callable($lookup)) {
        return false;
    }

    foreach ((array) call_user_func($lookup, $canonical_key) as $leftover) {
        if (is_string($leftover) && '' !== $leftover && filter_has_var($type, $leftover)) {
            return true;
        }
    }

    return false;
}

/**
 * Read a canonical request key via filter_input, with leftover fallback when Compat is on.
 *
 * POST-only and GET-only call sites stay on one input type.
 *
 * @param int        $type           INPUT_POST or INPUT_GET.
 * @param string     $canonical_key Canonical request field name.
 * @param int        $filter         filter_input filter constant.
 * @param int|array  $options        Optional filter options/flags.
 * @return mixed|null
 */
function ppcart_filter_input($type, $canonical_key, $filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS, $options = null)
{
    $keys = [ $canonical_key ];
    $lookup = ppcart_request_field_compatibility_lookup();

    if (is_callable($lookup)) {
        foreach ((array) call_user_func($lookup, $canonical_key) as $leftover) {
            if (is_string($leftover) && '' !== $leftover) {
                $keys[] = $leftover;
            }
        }
    }

    foreach ($keys as $key) {
        if (! filter_has_var($type, $key)) {
            continue;
        }

        return null === $options
            ? filter_input($type, $key, $filter)
            : filter_input($type, $key, $filter, $options);
    }

    return null;
}

/**
 * Read request input from POST first, then GET.
 *
 * @param string     $key     Canonical request key.
 * @param int        $filter  filter_input filter constant.
 * @param int|array  $options Optional filter options/flags.
 *
 * @return mixed|null
 */
function ppcart_filter_input_request($key, $filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS, $options = null)
{
    if (ppcart_request_has_var(INPUT_POST, $key)) {
        return ppcart_filter_input(INPUT_POST, $key, $filter, $options);
    }

    if (ppcart_request_has_var(INPUT_GET, $key)) {
        return ppcart_filter_input(INPUT_GET, $key, $filter, $options);
    }

    return null;
}

/**
 * Read a filter_input_array definition, filling missing canonical keys from leftover names.
 *
 * @param int   $type       INPUT_POST or INPUT_GET.
 * @param array $definition Canonical keys and filter_input_array filters.
 * @param bool  $add_empty  Whether missing keys are included as null.
 * @return array|false|null
 */
function ppcart_filter_input_array($type, $definition, $add_empty = true)
{
    if (! is_array($definition)) {
        return filter_input_array($type, $definition, $add_empty);
    }

    $canonical = filter_input_array($type, $definition, $add_empty);

    if (! is_array($canonical)) {
        $canonical = [];
    }

    $lookup = ppcart_request_field_compatibility_lookup();

    if (is_callable($lookup)) {
        foreach ($definition as $canonical_key => $filter) {
            if (! is_string($canonical_key) || '' === $canonical_key) {
                continue;
            }

            if (
                array_key_exists($canonical_key, $canonical)
                && null !== $canonical[ $canonical_key ]
                && false !== $canonical[ $canonical_key ]
            ) {
                continue;
            }

            foreach ((array) call_user_func($lookup, $canonical_key) as $leftover) {
                if (! is_string($leftover) || '' === $leftover || ! filter_has_var($type, $leftover)) {
                    continue;
                }

                $leftover_data = filter_input_array($type, [ $leftover => $filter ], $add_empty);

                if (is_array($leftover_data) && array_key_exists($leftover, $leftover_data)) {
                    $canonical[ $canonical_key ] = $leftover_data[ $leftover ];
                    break;
                }
            }
        }
    }

    return $canonical;
}

/**
 * Return the HTML allowlist for frontend callback output.
 *
 * Checkout and account templates need form controls, data attributes, and
 * SVG icons that are not included in the default post-content allowlist.
 * Plan radios must keep data-price / data-installments so checkout JS can
 * size the order and dispatch PayPal instead of the $0 COD save path.
 *
 * @return array
 */
function ppcart_frontend_allowed_html()
{
    $allowed = wp_kses_allowed_html('post');

    $global_attributes = [
        'aria-busy'       => true,
        'aria-checked'    => true,
        'aria-controls'   => true,
        'aria-current'    => true,
        'aria-describedby' => true,
        'aria-disabled'   => true,
        'aria-expanded'   => true,
        'aria-hidden'     => true,
        'aria-label'      => true,
        'aria-live'       => true,
        'aria-selected'   => true,
        'class'           => true,
        'data-active-tab' => true,
        'data-action'     => true,
        'data-form-wrapper' => true,
        'data-frequency'  => true,
        'data-id'         => true,
        'data-icon'       => true,
        'data-installments' => true,
        'data-interval'   => true,
        'data-item-id'    => true,
        'data-ppcart-qty-price' => true,
        'data-prefix'     => true,
        'data-price'      => true,
        'data-signup-fee' => true,
        'data-sitekey'    => true,
        'data-size'       => true,
        'data-status'     => true,
        'data-tab'        => true,
        'data-tax-price-format' => true,
        'data-tax-type'   => true,
        'data-taxable'    => true,
        'data-testid'     => true,
        'data-trial-days' => true,
        'data-val'        => true,
        'hidden'          => true,
        'id'              => true,
        'role'            => true,
        'style'           => true,
    ];

    foreach ($allowed as $tag => $attributes) {
        $allowed[$tag] = array_merge($attributes, $global_attributes);
    }

    $allowed['form'] = array_merge($global_attributes, [
        'action'         => true,
        'autocomplete'   => true,
        'enctype'        => true,
        'method'         => true,
        'name'           => true,
        'novalidate'     => true,
        'target'         => true,
    ]);

    $allowed['input'] = array_merge($global_attributes, [
        'accept'         => true,
        'alt'            => true,
        'autocomplete'   => true,
        'checked'        => true,
        'disabled'       => true,
        'inputmode'      => true,
        'max'            => true,
        'maxlength'      => true,
        'min'            => true,
        'multiple'       => true,
        'name'           => true,
        'pattern'        => true,
        'placeholder'    => true,
        'readonly'       => true,
        'required'       => true,
        'size'           => true,
        'spellcheck'     => true,
        'step'           => true,
        'type'           => true,
        'value'          => true,
    ]);

    $allowed['button'] = array_merge($global_attributes, [
        'disabled' => true,
        'name'     => true,
        'type'     => true,
        'value'    => true,
    ]);

    $allowed['select'] = array_merge($global_attributes, [
        'disabled' => true,
        'multiple' => true,
        'name'     => true,
        'required' => true,
    ]);

    $allowed['option'] = [
        'disabled' => true,
        'label'    => true,
        'selected' => true,
        'value'    => true,
    ];

    $allowed['textarea'] = array_merge($global_attributes, [
        'cols'        => true,
        'disabled'    => true,
        'maxlength'   => true,
        'name'        => true,
        'placeholder' => true,
        'readonly'    => true,
        'required'    => true,
        'rows'        => true,
    ]);

    $allowed['label'] = array_merge($global_attributes, [
        'for' => true,
    ]);

    $allowed['fieldset'] = $global_attributes;
    $allowed['legend']   = $global_attributes;
    $allowed['svg']      = array_merge($global_attributes, [
        'fill'    => true,
        'height'  => true,
        'stroke'  => true,
        'viewbox' => true,
        'width'   => true,
        'xmlns'   => true,
    ]);
    $allowed['path']     = array_merge($global_attributes, [
        'd'     => true,
        'fill'  => true,
        'style' => true,
    ]);
    $allowed['g'] = array_merge($global_attributes, [
        'fill'         => true,
        'fill-rule'    => true,
        'stroke'       => true,
        'stroke-width' => true,
        'transform'    => true,
    ]);
    $allowed['circle'] = array_merge($global_attributes, [
        'cx'             => true,
        'cy'             => true,
        'fill'           => true,
        'r'              => true,
        'stroke'         => true,
        'stroke-opacity' => true,
        'stroke-width'   => true,
        'transform'      => true,
    ]);
    $allowed['animatetransform'] = [
        'attributename' => true,
        'dur'           => true,
        'from'          => true,
        'repeatcount'   => true,
        'to'            => true,
        'type'          => true,
    ];

    return apply_filters('ppcart_frontend_allowed_html', $allowed);
}

/**
 * Escape frontend template HTML returned by shortcodes and block callbacks.
 *
 * @param mixed $html Callback output.
 * @return string
 */
function ppcart_kses_frontend_html($html)
{
    return wp_kses((string) $html, ppcart_frontend_allowed_html());
}

/**
 * Return the HTML allowlist for admin settings and metabox markup.
 *
 * Starts from the frontend allowlist, then adds settings-panel tags and
 * data attributes so kses at echo sites does not break admin JS.
 *
 * @return array
 */
function ppcart_admin_allowed_html()
{
    $allowed = ppcart_frontend_allowed_html();

    $extra = [
        'aria-level'       => true,
        'aria-labelledby'  => true,
        'aria-modal'       => true,
        'aria-pressed'     => true,
        'autocapitalize'   => true,
        'autocorrect'      => true,
        'tabindex'         => true,
        'data-balloon-length' => true,
        'data-balloon-pos' => true,
        'data-disabled-label' => true,
        'data-enabled-label' => true,
        'data-label'       => true,
        'data-lpignore'    => true,
        'data-nonce'       => true,
        'data-note-element' => true,
        'data-note-hosted' => true,
        'data-placeholder' => true,
        'data-plaintext-count' => true,
        'data-ppcart-editor-field' => true,
        'data-ppcart-editor-settings' => true,
        'data-ppcart-migrate-secrets' => true,
        'data-ppcart-migrate-secrets-result' => true,
        'data-ppcart-notif-preview' => true,
        'data-ppcart-secrets-getting-started' => true,
        'data-ppcart-secrets-maintenance' => true,
        'data-ppcart-toggle' => true,
        'data-ppcart-toggle-encrypt-secrets' => true,
        'data-pp-debug-actions' => true,
        'data-pp-debug-log-card' => true,
        'data-pp-debug-meta' => true,
        'data-pp-discard'  => true,
        'data-pp-email-body-field' => true,
        'data-pp-email-headline-field' => true,
        'data-pp-email-modal-close' => true,
        'data-pp-email-modal-open' => true,
        'data-pp-email-preview' => true,
        'data-pp-email-reset-template' => true,
        'data-pp-email-subject-field' => true,
        'data-pp-email-template' => true,
        'data-pp-integration-card' => true,
        'data-pp-integration-category' => true,
        'data-pp-integration-close' => true,
        'data-pp-integration-detail' => true,
        'data-pp-integration-filter' => true,
        'data-pp-integration-locked' => true,
        'data-pp-integration-manage' => true,
        'data-pp-integration-panel' => true,
        'data-pp-integration-search' => true,
        'data-pp-integration-toggle' => true,
        'data-pp-payment-close' => true,
        'data-pp-payment-detail' => true,
        'data-pp-payment-manage' => true,
        'data-pp-payment-method' => true,
        'data-pp-payment-panel' => true,
        'data-pp-payment-status' => true,
        'data-pp-payment-toggle' => true,
        'data-pp-paypal-mode' => true,
        'data-pp-paypal-mode-field' => true,
        'data-pp-paypal-mode-option' => true,
        'data-pp-paypal-mode-value' => true,
        'data-pp-save'     => true,
        'data-pp-secret-stored' => true,
        'data-pp-stripe-connect-mode' => true,
        'data-pp-stripe-experience' => true,
        'data-pp-stripe-mode' => true,
        'data-pp-stripe-mode-option' => true,
        'data-pp-stripe-mode-value' => true,
        'data-pp-stripe-webhook-log-actions' => true,
        'data-pp-stripe-webhook-log-card' => true,
        'data-pp-stripe-webhook-log-meta' => true,
        'data-pp-tab'      => true,
        'data-pp-tab-target' => true,
        'data-pp-tax-rates' => true,
        'data-pp-tax-rates-table' => true,
        'data-section-id'  => true,
    ];

    foreach ($allowed as $tag => $attributes) {
        $allowed[ $tag ] = array_merge(is_array($attributes) ? $attributes : [], $extra);
    }

    $svg_stroke = [
        'cx'              => true,
        'cy'              => true,
        'fill'            => true,
        'height'          => true,
        'r'               => true,
        'rx'              => true,
        'ry'              => true,
        'stroke'          => true,
        'stroke-linecap'  => true,
        'stroke-linejoin' => true,
        'stroke-width'    => true,
        'viewbox'         => true,
        'width'           => true,
        'x'               => true,
        'y'               => true,
    ];

    $merged = $allowed['div'] ?? $extra;

    $allowed['aside']  = array_merge($merged, $extra, [
        'hidden' => true,
    ]);
    $allowed['svg']    = array_merge($allowed['svg'] ?? [], $extra, $svg_stroke);
    $allowed['path']   = array_merge($allowed['path'] ?? [], $extra, $svg_stroke);
    $allowed['circle'] = array_merge($merged, $extra, $svg_stroke);
    $allowed['rect']   = array_merge($merged, $extra, $svg_stroke);
    $allowed['button'] = array_merge($allowed['button'] ?? [], [
        'form' => true,
    ]);
    $allowed['input'] = array_merge($allowed['input'] ?? [], [
        'form' => true,
    ]);
    $allowed['select'] = array_merge($allowed['select'] ?? [], [
        'form' => true,
    ]);
    $allowed['textarea'] = array_merge($allowed['textarea'] ?? [], [
        'form' => true,
    ]);
    $allowed['label'] = array_merge($allowed['label'] ?? [], [
        'form' => true,
    ]);

    return apply_filters('ppcart_admin_allowed_html', $allowed);
}

/**
 * Adds inline CSS to an enqueued handle, or prints a fallback handle if the
 * primary handle has already been printed.
 *
 * @param string $handle          Preferred style handle.
 * @param string $css             CSS to add.
 * @param string $fallback_handle Fallback handle used for late rendering.
 */
function ppcart_enqueue_or_print_inline_style($handle, $css, $fallback_handle = 'ppcart-inline-style')
{
    $css = trim((string) $css);

    if ('' === $css) {
        return;
    }

    // Product templates call this before wp_head(). The `ppcart` handle is
    // registered on wp_enqueue_scripts, so attaching now would print on a
    // fallback handle *before* ppcart-public.css and lose to equal-specificity
    // rules such as `.ppcart-hero-banner { background: #234854 }`.
    if (! did_action('wp_enqueue_scripts') && ! did_action('admin_enqueue_scripts')) {
        $callback = static function () use ($handle, $css, $fallback_handle) {
            ppcart_enqueue_or_print_inline_style($handle, $css, $fallback_handle);
        };
        add_action('wp_enqueue_scripts', $callback, 20);
        add_action('admin_enqueue_scripts', $callback, 20);
        return;
    }

    if ((wp_style_is($handle, 'registered') || wp_style_is($handle, 'enqueued')) && ! wp_style_is($handle, 'done')) {
        wp_enqueue_style($handle);
        wp_add_inline_style($handle, $css);
        return;
    }

    if (! wp_style_is($fallback_handle, 'registered')) {
        wp_register_style($fallback_handle, false, [], defined('PPCART_VERSION') ? PPCART_VERSION : null);
    }

    wp_enqueue_style($fallback_handle);
    wp_add_inline_style($fallback_handle, $css);

    if (did_action('wp_print_styles') || did_action('admin_print_styles')) {
        wp_print_styles([ $fallback_handle ]);
    }
}

if (! function_exists('ppcart_testid')) {
    /**
     * Normalize a value for stable data-testid attributes.
     *
     * @param mixed $value Source value.
     * @return string
     */
    function ppcart_testid($value)
    {
        if (! is_scalar($value)) {
            return '';
        }

        $normalized = strtolower((string) $value);
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized);
        $normalized = trim((string) $normalized, '-');

        return preg_replace('/-{2,}/', '-', $normalized);
    }
}

/**
 * Safely unserialize post meta without allowing object payload instantiation.
 *
 * @param mixed $value Raw meta value.
 *
 * @return mixed
 */
function ppcart_safe_meta_unserialize($value)
{
    if (! is_string($value) || ! is_serialized($value)) {
        return $value;
    }

    $trimmed = trim($value);

    // Reject serialized object payloads to avoid object-injection vectors.
    if (preg_match('/^[OC]:\d+:/', $trimmed)) {
        return $value;
    }

    // Plan data is stored as a plain stdClass inside an array, so stdClass has to
    // survive. It carries no __wakeup, __destruct, or other magic, which is what an
    // object-injection gadget needs. Every other class is still refused, at any depth.
    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Guarded unserialize limited to stdClass, with object payload rejection above and incomplete-object rejection below.
    $parsed = @unserialize($trimmed, [ 'allowed_classes' => [ 'stdClass' ] ]);

    if (false === $parsed && 'b:0;' !== $trimmed) {
        return $value;
    }

    if (is_object($parsed)) {
        return $value;
    }

    // A refused class comes back as __PHP_Incomplete_Class. Handing that to a caller
    // is worse than handing back the raw string: the object cannot be read, and
    // writing it back through update_post_meta() raises a fatal error in wp_unslash().
    if (ppcart_meta_value_has_incomplete_object($parsed)) {
        return $value;
    }

    return $parsed;
}

/**
 * Report whether an unserialized meta value holds an unreadable object at any depth.
 *
 * @param mixed $value Unserialized meta value.
 * @param int   $depth Current recursion depth.
 *
 * @return bool
 */
function ppcart_meta_value_has_incomplete_object($value, $depth = 0)
{
    if ($depth > 10) {
        return false;
    }

    if ($value instanceof __PHP_Incomplete_Class) {
        return true;
    }

    if (is_array($value)) {
        foreach ($value as $item) {
            if (ppcart_meta_value_has_incomplete_object($item, $depth + 1)) {
                return true;
            }
        }

        return false;
    }

    if ($value instanceof stdClass) {
        foreach (get_object_vars($value) as $item) {
            if (ppcart_meta_value_has_incomplete_object($item, $depth + 1)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Determines whether a checkout render should claim the current request.
 *
 * Once a guarded checkout claim wins for a product, every later guarded
 * checkout claim for that same product on the same request returns empty.
 * Builder, Elementor popup, admin, REST, and AJAX contexts bypass the guard.
 *
 * @param array $context Render context.
 * @return bool
 */
function ppcart_checkout_should_guard_duplicate_render($context = [])
{
    if (isset($context['builder']) && filter_var($context['builder'], FILTER_VALIDATE_BOOLEAN)) {
        return false;
    }

    if (isset($context['ele_popup']) && filter_var($context['ele_popup'], FILTER_VALIDATE_BOOLEAN)) {
        return false;
    }

    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST) || (function_exists('wp_doing_ajax') && wp_doing_ajax())) {
        return false;
    }

    return (bool) apply_filters('ppcart_checkout_should_guard_duplicate_render', true, $context);
}

function ppcart_checkout_get_request_render_key($context = [])
{
    $product_id = isset($context['product_id']) ? absint($context['product_id']) : 0;

    if ($product_id) {
        return 'product:' . $product_id;
    }

    return 'request';
}

function ppcart_checkout_claim_request_render($context = [])
{
    if (! ppcart_checkout_should_guard_duplicate_render($context)) {
        return true;
    }

    $render_key = ppcart_checkout_get_request_render_key($context);

    if (empty($GLOBALS['ppcart_checkout_request_rendered']) || ! is_array($GLOBALS['ppcart_checkout_request_rendered'])) {
        $GLOBALS['ppcart_checkout_request_rendered'] = [];
    }

    if (! empty($GLOBALS['ppcart_checkout_request_rendered'][ $render_key ])) {
        return false;
    }

    $GLOBALS['ppcart_checkout_request_rendered'][ $render_key ] = true;

    return true;
}

function ppcart_checkout_reset_request_render_guard()
{
    unset($GLOBALS['ppcart_checkout_request_rendered']);
}

/**
 * Wrapper for VIP-safe GET requests with a non-VIP fallback.
 *
 * @param string $url Request URL.
 * @param array  $args Request arguments.
 * @return array|WP_Error
 */
function ppcart_safe_remote_get($url, $args = [])
{
    if (function_exists('vip_safe_wp_remote_get')) {
        return vip_safe_wp_remote_get($url, $args);
    }

    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get -- vip_safe_wp_remote_get is not guaranteed to be available in all environments for this plugin.
    return wp_remote_get($url, $args);
}

/**
 * Wrapper for POST requests.
 *
 * @param string $url  Request URL.
 * @param array  $args Request arguments.
 * @return array|WP_Error
 */
function ppcart_safe_remote_post($url, $args = [])
{
    return wp_safe_remote_post($url, $args);
}


require_once __DIR__ . '/functions/ajax-security.php';
require_once __DIR__ . '/functions/ajax-and-fields.php';
require_once __DIR__ . '/functions/mailchimp-api.php';
require_once __DIR__ . '/functions/orders-products-and-formatting.php';
require_once __DIR__ . '/functions/integrations-and-stock.php';
require_once __DIR__ . '/functions/users-and-notifications.php';
require_once __DIR__ . '/functions/currencies.php';
require_once __DIR__ . '/functions/prices-and-marketing.php';
require_once __DIR__ . '/functions/product-and-order-setup.php';
require_once __DIR__ . '/functions/plans-and-subscriptions.php';
require_once __DIR__ . '/functions/order-items-and-details.php';
require_once __DIR__ . '/functions/checkout-completion.php';
require_once __DIR__ . '/functions/merge-tags-and-dates.php';
require_once __DIR__ . '/functions/payment-and-locale-lists.php';
require_once __DIR__ . '/helpers/ppcart-stripe-client.php';
require_once __DIR__ . '/functions/payment-actions-and-refunds.php';
require_once __DIR__ . '/functions/admin-ajax-and-notices.php';
require_once __DIR__ . '/functions/admin-conditional-logic.php';
require_once __DIR__ . '/functions/report-filters.php';
require_once __DIR__ . '/functions/request-sanitization.php';
