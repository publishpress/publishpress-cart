<?php

if (! defined('ABSPATH')) {
    exit;
}


require_once plugin_dir_path(__FILE__) . 'template-functions.php';

global $ppcart_product,$ppcart_currency_symbol;

$post_types = (array) apply_filters('ppcart_product_post_type', ppcart_live_post_type('product'));

if (!isset($product_id) || !in_array(get_post_type($product_id), $post_types)) {
    return;
}

$ppcart_product = ppcart_setup_product($product_id);
$cart_closed = ppcart_is_cart_closed();

// 2-step option now stored in _ppcart_display meta
if (isset($template) && $template == 'opt-in') {
    $ppcart_product->show_optin = true;
    unset($ppcart_product->upsell_path, $ppcart_product->order_bump, $ppcart_product->order_bump_options, $ppcart_product->show_coupon_field);
}

add_action('ppcart_closed_message', 'ppcart_do_cart_closed_message');
add_action('ppcart_checkout_form_scripts', 'ppcart_do_checkout_form_scripts', 10, 2);
add_action('ppcart_checkout_page_heading', 'ppcart_do_error_messages', 15);
add_action('ppcart_checkout_form_open', 'ppcart_do_checkout_form_open', 10);
add_action('ppcart_checkout_form', 'ppcart_do_checkout_form', 10, 3);
add_action('ppcart_card_details_fields', 'ppcart_payment_plan_options', 1, 3);
add_action('ppcart_card_details_fields', 'ppcart_do_checkoutform_fields', 5, 2);
add_action('ppcart_card_details_fields', 'ppcart_do_card_details_fields', 10, 2);
add_action('ppcart_checkout_form_close', 'ppcart_do_checkout_form_close', 10);
add_action('ppcart_order_summary', 'ppcart_do_order_summary', 10, 2);
add_action('ppcart_before_payment_info', 'ppcart_do_test_mode_message', 10);

if (!empty($ppcart_product->show_address_fields)) {
    add_action('ppcart_checkout_form_fields', 'ppcart_address_fields', 10, 2);
}

if (!$builder) :
    ob_start();
    ?>
        .ppcart button,
        .ppcart .ppcart-btn-block {
            background-color: <?php echo esc_attr($ppcart_product->button_color); ?>
        }

        <?php
        $show_bump = isset($ppcart_product->order_bump_options);
    $show_bump = apply_filters('ppcart_show_orderbump', $show_bump, $product_id);
    if ($show_bump) {
        for ($k = 0; $k < count($ppcart_product->order_bump_options); $k++) {
            if ($ppcart_product->order_bump_options[$k]['bump_bg_color']) { ?>
                    .ppcart #ppcart-payment-form #ppcart-orderbump-<?php echo esc_attr($k); ?>.ppcart-section.orderbump {
                        background-color: <?php echo esc_attr($ppcart_product->order_bump_options[$k]['bump_bg_color']); ?>
                    }
                    .ppcart #ppcart-payment-form #ppcart-orderbump-<?php echo esc_attr($k); ?>.orderbump .title {
                        background: transparent;
                    }
                    .ppcart #ppcart-payment-form #ppcart-orderbump-<?php echo esc_attr($k); ?>.orderbump {
                        border: none;
                    }
                    <?php
            }
        }
    }
    ?>

    <?php
    $checkout_css = trim(ob_get_clean());

    ppcart_enqueue_checkout_inline_style($checkout_css);
endif; ?>

<section id="ppcart-form-container" class="ppcart ppcart-shortcode">
  <div class="ppcart-container">
    <?php
    if (isset($cart_closed) && $cart_closed) {
        $redirect = isset($ppcart_product->checkout_ended_redirect) ? trim((string) $ppcart_product->checkout_ended_redirect) : '';
        if (isset($ppcart_product->checkout_ended_action) && 'redirect' === $ppcart_product->checkout_ended_action && '' !== $redirect) {
            wp_add_inline_script(
                'ppcart',
                'window.location.replace(' . wp_json_encode(esc_url_raw($redirect)) . ');'
            );
        } else {
            do_action('ppcart_closed_message', $product_id);
        }
    } else {
        do_action('ppcart_checkout_page_heading', $product_id);
        do_action('ppcart_checkout_form_open', $product_id);
        do_action('ppcart_checkout_form', $product_id, $hide_labels, $plan);
        do_action('ppcart_checkout_form_close');
    } ?>

  </div>
</section>

<?php do_action('ppcart_checkout_form_scripts', $product_id, $coupon); ?>
