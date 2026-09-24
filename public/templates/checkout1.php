<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . 'template-functions.php';

global $ppcart_product, $post;

$product_id = $post instanceof WP_Post ? $post->ID : get_the_ID();
$ppcart_product = ppcart_checkout_product_context($product_id);
if (! is_object($ppcart_product) || ! isset($ppcart_product->ID)) {
    return;
}

$prod_id        = $ppcart_product->ID;
$post_types = (array) apply_filters('ppcart_product_post_type', ppcart_live_post_type('product'));
if (!in_array(get_post_type($prod_id), $post_types)) {
    return;
}
$cart_closed    = ppcart_is_cart_closed();
$request_ppcart_order = filter_input(INPUT_GET, 'ppcart-order', FILTER_VALIDATE_INT);
$request_ppcart_method_change = ppcart_filter_input(INPUT_GET, 'ppcart-method-change', FILTER_VALIDATE_INT);
$show_confirm   = (false !== $request_ppcart_order && null !== $request_ppcart_order && absint($request_ppcart_order) > 0) ? true : false;
$orderID        = ($show_confirm) ? absint($request_ppcart_order) : false;

$method_change  = (false !== $request_ppcart_method_change && null !== $request_ppcart_method_change && absint($request_ppcart_method_change) > 0) ? true : false;
$orderID        = ($method_change) ? absint($request_ppcart_method_change) : false;
$hide_labels = (bool) apply_filters('ppcart_checkout_hide_labels', false, $ppcart_product);

if (isset($ppcart_product->show_optin)) {
    unset($ppcart_product->upsell_path, $ppcart_product->order_bump, $ppcart_product->order_bump_options, $ppcart_product->show_coupon_field);
}

if (function_exists('ppcart_do_payment_method_change')) {
    add_action('ppcart_payment_method_change', 'ppcart_do_payment_method_change', 10);
}

add_action('ppcart_payment_confirmation', 'ppcart_do_payment_confirmation', 10);
add_action('ppcart_checkout_page_heading', 'ppcart_do_error_messages', 15);
add_action('ppcart_checkout_form_scripts', 'ppcart_do_checkout_form_scripts', 10, 2);

add_action('ppcart_checkout_form', 'ppcart_do_checkout_form', 10, 3);
add_action('ppcart_card_details_fields', 'ppcart_do_card_details_fields', 10, 2);
add_action('ppcart_before_payment_info', 'ppcart_do_test_mode_message', 10);
add_action('ppcart_order_summary', 'ppcart_do_order_summary', 10, 2);

if (!isset($ppcart_product->show_2_step)) {
    add_action('ppcart_checkout_form_open', 'ppcart_do_checkout_form_open', 10);
    add_action('ppcart_card_details_fields', 'ppcart_payment_plan_options', 1, 3);
    add_action('ppcart_card_details_fields', 'ppcart_do_checkoutform_fields', 5, 2);
    add_action('ppcart_checkout_form_close', 'ppcart_do_checkout_form_close', 10);

    if (isset($ppcart_product->show_address_fields)) {
        add_action('ppcart_checkout_form_fields', 'ppcart_address_fields', 10, 2);
    }
} else {
    add_action('ppcart_checkout_form_open', 'ppcart_do_2step_checkout_form_open', 10);
    add_action('ppcart_card_details_fields', 'ppcart_step_wrappers_1', 1);
    add_action('ppcart_card_details_fields', 'ppcart_do_2step_checkoutform_fields', 1, 2);

    if (isset($ppcart_product->show_address_fields)) {
        add_action('ppcart_card_details_fields', 'ppcart_address_fields', 1, 2);
    }

    add_action('ppcart_card_details_fields', 'ppcart_step_wrappers_2', 1);
    add_action('ppcart_card_details_fields', 'ppcart_payment_plan_options', 5, 3);
    add_action('ppcart_checkout_form_close', 'ppcart_step_wrappers_3', 5);
    add_action('ppcart_checkout_form_close', 'ppcart_do_checkout_form_close', 10);
}
?>

<!DOCTYPE html>

<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php if (! current_theme_supports('title-tag')) : ?>
        <title><?php echo esc_html(wp_get_document_title()); ?></title>
    <?php endif; ?>
    <?php
    ob_start();
?>
        .ppcart .ppcart-btn-block,
        .ppcart input[type="button"],
        body.<?php echo esc_attr(function_exists('ppcart_product_singular_body_class') ? ppcart_product_singular_body_class() : 'single-ppcart_product'); ?> .ppcart-page .ppcart-container .ppcart-embed-checkout-form-nav .ppcart-checkout-form-steps .steps.ppcart-current a .step-number {
            background-color: <?php echo esc_attr($ppcart_product->button_color); ?>
        }

        <?php
    $show_bump = isset($ppcart_product->order_bump_options);
$show_bump = apply_filters('ppcart_show_orderbump', $show_bump, $ppcart_product->ID);
if ($show_bump || isset($ppcart_product->bump_bg_color)) {
    if (isset($ppcart_product->bump_bg_color)) :
        ?>.ppcart-page .ppcart-container #ppcart-payment-form #ppcart-orderbump-main {
            background-color: <?php echo esc_attr($ppcart_product->bump_bg_color); ?>
        }

    <?php endif;

    for ($k = 0; $k < count($ppcart_product->order_bump_options); $k++) {
        if (isset($ppcart_product->order_bump_options[$k]['bump_bg_color']) && $ppcart_product->order_bump_options[$k]['bump_bg_color']) {
            ?>.ppcart-page .ppcart-container #ppcart-payment-form #ppcart-orderbump-<?php echo esc_attr($k); ?>.ppcart-section.orderbump {
            background-color: <?php echo esc_attr($ppcart_product->order_bump_options[$k]['bump_bg_color']); ?>
        }

                <?php
        }
    }
}
?>.ppcart .ppcart-checkout-form-steps .steps.ppcart-current a .step-heading .step-name {
            color: <?php echo esc_attr($ppcart_product->button_color); ?>
        }

        <?php if (! empty($ppcart_product->header_color)) :
            ?>.ppcart-hero-banner {
            background-color: <?php echo esc_attr($ppcart_product->header_color); ?>;
        }

        <?php endif; ?>
    <?php
    $checkout_css = trim(ob_get_clean());

ppcart_enqueue_checkout_inline_style($checkout_css);

$ppcart_hero_banner_style_parts = [];
if (! empty($ppcart_product->header_color)) {
    $ppcart_hero_banner_style_parts[] = 'background-color: ' . $ppcart_product->header_color;
}
if (! empty($ppcart_product->header_image)) {
    $ppcart_hero_banner_style_parts[] = 'background-image: url(' . esc_url($ppcart_product->header_image) . ')';
}
$ppcart_hero_banner_style = implode('; ', $ppcart_hero_banner_style_parts);
?>
    <?php wp_head(); ?>
</head>

<body <?php body_class('ppcart-checkout-1'); ?>>

    <?php while (have_posts()) :
        the_post(); ?>

        <div class="ppcart-hero-banner"<?php echo $ppcart_hero_banner_style !== '' ? ' style="' . esc_attr($ppcart_hero_banner_style) . '"' : ''; ?>>
            <div class="ppcart-container">
                <?php if (!isset($ppcart_product->hide_title)) : ?>
                    <h2><?php echo esc_html(ppcart_get_public_product_name()); ?></h2>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $page_classes = [
            'ppcart-page',
            'payment-page',
            ($show_confirm || $cart_closed) ? 'page-closed' : '',
            (isset($ppcart_product->show_splitin) ? 'splitin-page' : ''),
        ];
        ?>
        <main class="<?php echo esc_attr(implode(' ', $page_classes)); ?>">
            <?php if ($show_confirm || $cart_closed) : ?>
                <div class="ppcart-container">
                    <?php do_action('ppcart_payment_confirmation', $prod_id); ?>
                </div>

            <?php elseif ($method_change || $cart_closed) : ?>
                <?php if (! isset($ppcart_product->show_splitin)) : ?>
                    <div class="ppcart-container">
                        <div class="main-content">
                            <?php the_content(); ?>
                        </div>
                <?php endif; ?>

                    <section id="ppcart-form-container" class="ppcart <?php echo esc_attr(isset($ppcart_product->show_splitin) ? 'ppcart-splitin-form' : ''); ?>">
                        <?php if (isset($ppcart_product->show_splitin)) : ?>
                            <div class="splitin-checkout-wrap">
                                <div class="checkout-lhs checkout-inner bg-light">
                                    <?php do_action('ppcart_order_summary_items', $prod_id); ?>
                                </div>
                                <div class="checkout-rhs checkout-inner bg-white">
                                    <?php
                                    do_action('ppcart_checkout_page_heading', $prod_id);
                            do_action('ppcart_checkout_form_open', $prod_id);
                            ?>
                                    <h2 class="page-title"><?php echo esc_html(ppcart_checkout_text_setting('splitFormHeading', esc_html__('Get ready to start selling', 'publishpress-cart'))); ?></h2>
                                    <?php
                            do_action('ppcart_checkout_form', $prod_id, $hide_labels);
                            do_action('ppcart_checkout_form_close');
                            do_action('ppcart_payment_method_change', $prod_id);
                            ?>
                                </div>
                            </div>
                        <?php else : ?>
                            <?php
                            do_action('ppcart_checkout_page_heading', $prod_id);
                            do_action('ppcart_checkout_form_open', $prod_id);
                            do_action('ppcart_checkout_form', $prod_id, $hide_labels);
                            do_action('ppcart_checkout_form_close');
                            do_action('ppcart_payment_method_change', $prod_id);
                            ?>
                        <?php endif; ?>
                    </section>

                    <?php if (! isset($ppcart_product->show_splitin)) : ?>
                    </div>
                    <?php endif; ?>

            <?php else : ?>
                <?php if (! isset($ppcart_product->show_splitin)) : ?>
                    <div class="ppcart-container">
                        <div class="main-content">
                            <?php the_content(); ?>
                        </div>
                <?php endif; ?>

                    <section id="ppcart-form-container" class="ppcart <?php echo esc_attr(isset($ppcart_product->show_splitin) ? 'ppcart-splitin-form' : ''); ?>">
                        <?php if (isset($ppcart_product->show_splitin)) : ?>
                            <div class="splitin-checkout-wrap">
                                <div class="checkout-lhs checkout-inner bg-light">
                                    <?php do_action('ppcart_order_summary_items', $prod_id); ?>
                                </div>
                                <div class="checkout-rhs checkout-inner bg-white">
                                    <?php
                                    do_action('ppcart_checkout_page_heading', $prod_id);
                            do_action('ppcart_checkout_form_open', $prod_id);
                            ?>
                                    <h2 class="page-title"><?php echo esc_html(ppcart_checkout_text_setting('splitFormHeading', esc_html__('Get ready to start selling', 'publishpress-cart'))); ?></h2>
                                    <?php
                            do_action('ppcart_checkout_form', $prod_id, $hide_labels);
                            do_action('ppcart_checkout_form_close');
                            ?>
                                </div>
                            </div>
                        <?php else : ?>
                            <?php
                            do_action('ppcart_order_summary_items', $prod_id);
                            do_action('ppcart_checkout_page_heading', $prod_id);
                            do_action('ppcart_checkout_form_open', $prod_id);
                            do_action('ppcart_checkout_form', $prod_id, $hide_labels);
                            do_action('ppcart_checkout_form_close');
                            ?>
                        <?php endif; ?>
                    </section>

                    <?php if (! isset($ppcart_product->show_splitin)) : ?>
                    </div>
                    <?php endif; ?>

            <?php endif; ?>
        </main>

    <?php endwhile; ?>

    <?php if (!$show_confirm && !$cart_closed) {
        do_action('ppcart_checkout_form_scripts', $prod_id);
    }

wp_footer();
?>

</body>

</html>
