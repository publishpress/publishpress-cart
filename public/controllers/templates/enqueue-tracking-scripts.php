<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * This function is provided for demonstration purposes only.
 *
 * An instance of this class should be passed to the run() function
 * defined in Nc_Cart_Loader as all of the hooks are defined
 * in that particular class.
 *
 * The Nc_Cart_Loader will then create the relationship
 * between the defined hooks and the functions defined in this
 * class.
*/

global $ppcart_stripe, $ppcart_currency, $ppcart_product;

// Heavy assets (Font Awesome, selectize, cart CSS) only on cart-related pages.
// Styles are gated in enqueue_styles(); scripts that remain below still register
// core cart JS for checkout routing when needed.
if (method_exists($this, 'frontend_assets_needed') && $this->frontend_assets_needed()) {
    if (method_exists($this, 'enqueue_frontend_assets')) {
        $this->enqueue_frontend_assets();
    }
}

$ppcart_order_get      = filter_input(INPUT_GET, 'ppcart-order', FILTER_VALIDATE_INT);
$ppcart_pid_get        = ppcart_filter_input(INPUT_GET, 'ppcart-pid', FILTER_VALIDATE_INT);
$ppcart_plan_get       = filter_input(INPUT_GET, 'ppcart-plan', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$ppcart_oto_get        = ppcart_filter_input(INPUT_GET, 'ppcart-oto', FILTER_VALIDATE_INT);
$ppcart_preview_get    = filter_input(INPUT_GET, 'ppcart-preview', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$step_get          = filter_input(INPUT_GET, 'step', FILTER_VALIDATE_INT);
$email_get         = filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL);
$purchase_amount   = ppcart_filter_input(INPUT_POST, 'ppcart_purchase_amount', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$posted_order_id   = ppcart_filter_input(INPUT_POST, 'ppcart_order_id', FILTER_VALIDATE_INT);
$posted_ppcart_order   = ppcart_filter_input_array(
    INPUT_POST,
    [
        'ppcart_order' => [
            'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'flags'  => FILTER_REQUIRE_ARRAY,
        ],
    ]
);
$posted_order_bump = ppcart_filter_input_array(
    INPUT_POST,
    [
        'ppcart-orderbump' => [
            'filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'flags'  => FILTER_REQUIRE_ARRAY,
        ],
    ]
);
$posted_ppcart_order   = isset($posted_ppcart_order['ppcart_order']) && is_array($posted_ppcart_order['ppcart_order']) ? $posted_ppcart_order['ppcart_order'] : [];
$posted_order_bump = isset($posted_order_bump['ppcart-orderbump']) && is_array($posted_order_bump['ppcart-orderbump']) ? $posted_order_bump['ppcart-orderbump'] : [];
$ppcart_order_post     = ppcart_parse_tracking_order_fields($posted_ppcart_order, false);
$ppcart_order_post_id  = isset($ppcart_order_post['ID']) ? absint($ppcart_order_post['ID']) : 0;
$pay_method        = $ppcart_order_post['pay_method'] ?? '';
$upsell_nonce      = ppcart_filter_input(INPUT_POST, 'ppcart_upsell_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$downsell_nonce    = ppcart_filter_input(INPUT_POST, 'ppcart_downsell_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$ppcart_order_post_get = filter_input(INPUT_POST, 'ppcart-order', FILTER_VALIDATE_INT);
$purchase_amount   = is_string($purchase_amount) ? sanitize_text_field($purchase_amount) : '';
$posted_order_id   = (false !== $posted_order_id && null !== $posted_order_id) ? absint($posted_order_id) : 0;
$posted_order_bump = is_array($posted_order_bump) ? $posted_order_bump : [];
$upsell_nonce      = is_string($upsell_nonce) ? sanitize_text_field($upsell_nonce) : '';
$downsell_nonce    = is_string($downsell_nonce) ? sanitize_text_field($downsell_nonce) : '';
$ppcart_order_post_get = (false !== $ppcart_order_post_get && null !== $ppcart_order_post_get) ? absint($ppcart_order_post_get) : 0;

// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checkout/upsell nonce validation happens before these tracking-only values are consumed.
if (empty($ppcart_order_post) && isset($_POST['ppcart_order']) && is_array($_POST['ppcart_order'])) {
    $ppcart_order_post = ppcart_parse_tracking_order_fields(wp_unslash($_POST['ppcart_order']), false); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Parser sanitizes by field after unslash at the read site.
    $ppcart_order_post_id = isset($ppcart_order_post['ID']) ? absint($ppcart_order_post['ID']) : 0;
    $pay_method = $ppcart_order_post['pay_method'] ?? '';
}

if ('' === $upsell_nonce && isset($_POST['ppcart_upsell_nonce']) && is_string($_POST['ppcart_upsell_nonce'])) {
    $upsell_nonce = sanitize_text_field(wp_unslash($_POST['ppcart_upsell_nonce']));
}

if ('' === $downsell_nonce && isset($_POST['ppcart_downsell_nonce']) && is_string($_POST['ppcart_downsell_nonce'])) {
    $downsell_nonce = sanitize_text_field(wp_unslash($_POST['ppcart_downsell_nonce']));
}
// phpcs:enable WordPress.Security.NonceVerification.Missing

if (! $ppcart_product && $ppcart_order_get && $ppcart_pid_get) {
    $ppcart_product = ppcart_setup_product(absint($ppcart_pid_get));
}

// selectize is enqueued via enqueue_frontend_assets() when needed.
wp_register_script('ppcart', PPCART_BASE_URL . 'public/js/ppcart-public.js', [ 'jquery' ], $this->version, true);

if (! empty($ppcart_plan_get) || is_object($ppcart_product)) {
    wp_register_script('ppcart-stripe', PPCART_BASE_URL . 'public/js/ppcart-stripe.js', [ 'jquery' ], $this->version, true);
}

wp_localize_script('ppcart', 'ppcart_translate_frontend', ppcart_translate_js('ppcart-public.js'));
wp_localize_script('ppcart', 'ppcart_currency', ppcart_currency_settings());

do_action('ppcart_enqueue_public_tax_settings', $this->plugin_name);

$remote_addr = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
$user        = [ is_string($remote_addr) ? $remote_addr : '' ];
if (is_string($email_get) && '' !== $email_get) {
    $user[] = sanitize_email($email_get);
}
wp_localize_script('ppcart', 'ppcart_user', $user);

if (isset($ppcart_stripe['pk'])) {
    global $post;
    $stripe = $ppcart_stripe['pk'];
    wp_localize_script('ppcart', 'ppcart_stripe_key', [ $stripe ]);
    // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Stripe controls this remote script URL and recommends loading it directly from js.stripe.com.
    wp_register_script('ppcart-stripe-api-v3', 'https://js.stripe.com/v3/', [], null, true);

    $post_types = (array) apply_filters('ppcart_product_post_type', ppcart_live_post_type('product'));
    if (is_object($ppcart_product) || in_array(get_post_type(), $post_types, true) || '' !== $purchase_amount || ! empty($ppcart_plan_get) || ($ppcart_order_get && $step_get)) {
        wp_enqueue_script('ppcart-stripe-api-v3');
    }
}

wp_enqueue_script('ppcart');

$ppcart = ['ajax' => admin_url('admin-ajax.php'),'page_id' => get_the_ID()];
$ppcart['product_singular_selector'] = function_exists('ppcart_product_singular_body_selector')
    ? ppcart_product_singular_body_selector()
    : '.single-ppcart_product';

if (! empty($ppcart_stripe['is_hosted_checkout'])) {
    $ppcart['is_hosted_checkout'] = 1;
}
if (! empty($ppcart_stripe['is_payment_element'])) {
    $ppcart['is_payment_element'] = 1;
}

wp_enqueue_script('ppcart-stripe');

if (is_object($ppcart_product) && ! ($ppcart_order_post_get || $ppcart_order_get || $upsell_nonce || $downsell_nonce)) {
    $ppcart['is_ppcart_checkout'] = $ppcart_product->ID;
}

if (is_object($ppcart_product) && get_option('_ppcart_fb_add_payment_info')) {
    $ppcart['fb_add_payment_info'] = 'enabled';
    $ppcart['content_id'] = $ppcart_product->ID;
}

if (is_object($ppcart_product) && get_option('_ppcart_fb_lead')) {
    $ppcart['fb_lead_event'] = 'enabled';
    $ppcart['content_id'] = $ppcart_product->ID;
}

if ('' !== $purchase_amount || ($ppcart_order_get && ! $ppcart_oto_get)) {
    if ('' === $purchase_amount) {
        $order_info = (array) ppcart_setup_order(absint($ppcart_order_get));
        $purchase_amount = isset($order_info['amount']) ? (string) $order_info['amount'] : '';
        $posted_order_id = absint($ppcart_order_get);
        if (isset($order_info['order_bumps']) && is_array($order_info['order_bumps'])) {
            $posted_order_bump = $order_info['order_bumps'];
        }
    }
    if (get_option('_ppcart_fb_purchase')) {
        $ppcart['amount'] = $purchase_amount;
        $ppcart['currency'] = $ppcart_currency;
        $ppcart['fb_purchase_event'] = 'enabled';
        unset($ppcart['fb_add_payment_info'], $ppcart['content_id']);
    }
}

$ppcart = apply_filters('ppcart_enqueue_scripts_upsell_downsell', $ppcart, [
    'pay_method'       => $pay_method,
    'downsell_nonce'   => $downsell_nonce,
    'upsell_nonce'     => $upsell_nonce,
    'ppcart_oto_get'       => $ppcart_oto_get,
    'step_get'             => $step_get,
    'ppcart_order_get'     => $ppcart_order_get,
    'ppcart_order_post_id' => $ppcart_order_post_id,
    'ppcart_preview_get'   => $ppcart_preview_get,
    'ppcart_order_post'    => $ppcart_order_post,
], $ppcart_product);

if ($ppcart_order_get) {
    $ppcart = apply_filters('ppcart_script_vars', $ppcart, absint($ppcart_order_get), $ppcart_product);
}

if (!empty($ppcart)) {
    wp_localize_script('ppcart', 'ppcart', $ppcart);
    add_action('wp_footer', [$this, 'js_order_tracking']);
}
