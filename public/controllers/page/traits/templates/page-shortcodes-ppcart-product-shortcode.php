<?php

if (! defined('ABSPATH')) {
    exit;
}


global $post, $ppcart_stripe;
$ppcart_order_get = filter_input(INPUT_GET, 'ppcart-order', FILTER_VALIDATE_INT);

if (!isset($atts['id']) || !$atts['id']) {
    if (! $post instanceof WP_Post) {
        return '';
    }

    $atts['id'] = $post->ID;
}

$post_types = (array) apply_filters('ppcart_product_post_type', ppcart_live_post_type('product'));
if (! in_array(get_post_type(absint($atts['id'])), $post_types, true)) {
    return '';
}

// handle default confirmations in custom product templates
if (false !== $ppcart_order_get && null !== $ppcart_order_get) {
    if (! ppcart_checkout_claim_request_render([ 'source' => 'shortcode', 'state' => 'confirmation', 'product_id' => absint($atts['id']) ])) {
        return '';
    }

    $closed_msg = ppcart_get_post_meta($atts['id'], 'confirmation_message', true);
    $closed_msg = (!$closed_msg) ? __("Thank you. We've received your order.", "publishpress-cart") : $closed_msg;
    $msg = '<p>' . wp_kses_post(wp_specialchars_decode($closed_msg, 'ENT_QUOTES')) . '</p>';
    return $msg . do_shortcode('[ppcart_receipt]');
}

if (isset($ppcart_stripe['pk'])) {
    wp_enqueue_script('ppcart-stripe-api-v3');
}

// 2-step option now stored in _ppcart_display meta
if (ppcart_get_post_meta($atts['id'], 'show_2_step', true)) {
    ppcart_update_post_meta($atts['id'], 'display', 'two_step');
    ppcart_delete_post_meta($atts['id'], 'show_2_step');
}

$default_template = ppcart_get_post_meta($atts['id'], 'display', true);
if ($default_template == 'two_step') {
    $default_template = '2-step';
}

$product_shortcode_atts = shortcode_atts([
    'product_id' => $atts['id'],
    'plan' => false,
    'hide_labels' => false,
    'template'  => false,
    'skin'  => false,
    'coupon'  => false,
    'builder' => false,
    'ele_popup' => false,
], $atts);

// Explicit assignments instead of extract(). The checkout template included below reads these locals.
$product_id  = $product_shortcode_atts['product_id'];
$plan        = $product_shortcode_atts['plan'];
$hide_labels = $product_shortcode_atts['hide_labels'];
$template    = $product_shortcode_atts['template'];
$skin        = $product_shortcode_atts['skin'];
$coupon      = $product_shortcode_atts['coupon'];
$builder     = $product_shortcode_atts['builder'];
$ele_popup   = $product_shortcode_atts['ele_popup'];
unset($product_shortcode_atts);

if (! ppcart_checkout_claim_request_render([ 'source' => 'shortcode', 'builder' => $builder, 'ele_popup' => $ele_popup, 'product_id' => absint($product_id) ])) {
    return '';
}

ob_start();

if ($skin) {
    $template = $skin;
} elseif (!$template) {
    $template = $default_template;
} elseif ($template == 'normal') {
    $template = '';
}

if ($ele_popup) {
    wp_localize_script('ppcart', 'ppcart_popup', ['is_popup' => 'true']);
} else {
    wp_localize_script('ppcart', 'ppcart_popup', ['is_popup' => 'false']);
}

global $ppcart_checkout_block_arrangement;

$previous_checkout_block_arrangement = $ppcart_checkout_block_arrangement ?? null;
$ppcart_checkout_block_arrangement = apply_filters(
    'ppcart_checkout_block_arrangement',
    null,
    [
        'product_id' => absint($product_id),
        'template'   => $template,
        'atts'       => $atts,
    ]
);

$template_dir              = PPCART_BASE_DIR . 'public/templates/';
$default_checkout_template = $template_dir . 'checkout-shortcode.php';
$checkout_template         = $default_checkout_template;

// The template/skin names come from shortcode attributes: allow only a plain slug in the file path.
if ($template && is_string($template) && preg_match('/^[a-z0-9_-]+$/i', $template) && file_exists($template_dir . 'checkout-shortcode-' . $template . '.php')) {
    $checkout_template = $template_dir . 'checkout-shortcode-' . $template . '.php';
}

$checkout_template = apply_filters(
    'ppcart_checkout_template_path',
    $checkout_template,
    $template,
    [
        'product_id' => absint($product_id),
        'atts'       => $atts,
    ]
);

if (! is_string($checkout_template) || ! is_readable($checkout_template)) {
    $checkout_template = $default_checkout_template;
}

include $checkout_template;

if (null === $previous_checkout_block_arrangement) {
    unset($GLOBALS['ppcart_checkout_block_arrangement']);
} else {
    $ppcart_checkout_block_arrangement = $previous_checkout_block_arrangement;
}

$output_string = ob_get_contents();

ob_end_clean();

return $output_string;
