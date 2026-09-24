<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The Template for displaying an order bump on an order form
 * This template can be overridden by copying it to yourtheme/publishpress-cart/order-form/submit-button.php.
 */

$ppcart_product = $args['ppcart_product'];
do_action('ppcart_before_buy_button', $ppcart_product); ?>

<button id="<?php echo esc_attr($args['id']); ?>" data-form-wrapper="ppcart-payment-form-<?php echo esc_attr($ppcart_product->ID); ?>-<?php echo esc_attr($args['ppcart_uid']); ?>" type="button" class="ppcart-btn ppcart-btn-primary ppcart-btn-block" disabled="disabled" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-order-submit')); ?>">
    <?php if (empty($ppcart_product->button_subtext)) { ?>
    <svg class="spinner" width="24" height="24" viewBox="0 0 24 24">
        <g fill="none" fill-rule="nonzero">
            <path class="ring_thumb" fill="#FCECEA" d="M17.945 3.958A9.955 9.955 0 0 0 12 2c-2.19 0-4.217.705-5.865 1.9L5.131 2.16A11.945 11.945 0 0 1 12 0c2.59 0 4.99.82 6.95 2.217l-1.005 1.741z"></path>
            <path class="ring_track" fill="#FCECEA" d="M5.13 2.16L6.136 3.9A9.987 9.987 0 0 0 2 12c0 5.523 4.477 10 10 10s10-4.477 10-10a9.986 9.986 0 0 0-4.055-8.042l1.006-1.741A11.985 11.985 0 0 1 24 12c0 6.627-5.373 12-12 12S0 18.627 0 12c0-4.073 2.029-7.671 5.13-9.84z" style="opacity: 0.35"></path>
        </g>
    </svg>
    <?php } ?>
    <span class="text" data-testid="<?php echo esc_attr(ppcart_data_testid_attribute('ppcart-order-submit-text')); ?>">
        <?php do_action('ppcart_buy_button_icon', $ppcart_product, 'left'); ?>

        <?php echo esc_html($ppcart_product->button_text ?? __('Order Now', 'publishpress-cart')); ?>

        <?php do_action('ppcart_buy_button_icon', $ppcart_product, 'right'); ?>
    </span>

    <?php do_action('ppcart_buy_button_subtext', $ppcart_product); ?>
</button>
<?php do_action('ppcart_after_buy_button', $ppcart_product); ?>
