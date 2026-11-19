<?php

if (! defined('ABSPATH')) {
    exit;
}


/**
 * The Template for displaying customer's card details
 * This template can be overridden by copying it to <active-theme-folder>/publishpress-cart/shortcodes/receipt.php.
 */

?>

<div id="ppcart-order-details">
    <h3><?php esc_html_e("Order Details", "publishpress-cart"); ?></h3>
    <div class="ppcart-order-table">
        <div class="item ppcart-heading"><strong><?php esc_html_e("Product", "publishpress-cart"); ?></strong></div>
        <div class="order-total ppcart-heading"><strong><?php esc_html_e("Price", "publishpress-cart"); ?></strong></div>
        <?php foreach ($attr['items'] as $item) : ?>
            <?php $item['item_type'] ??= ''; ?>
            <div class="item">
                <?php
                    // Fix extra hypen
                    echo esc_html($item['product_name']);
            // Only append price_name for main items with a distinct, non-empty plan name
            if (isset($item['price_name']) && !empty($item['price_name']) && $item['item_type'] === 'main' && $item['price_name'] !== $item['product_name']) {
                echo ' - ' . esc_html($item['price_name']);
            }
            ?>
                <?php if (isset($item['sub_summary'])) : ?>
                    <br><small><?php echo wp_kses_post($item['sub_summary']); ?></small>
                <?php endif; ?>
                <?php if (isset($item['purchase_note'])) : ?>
                    <br><span class="ppcart-purchase-note"><?php echo wp_kses_post($item['purchase_note']); ?></span>
                <?php endif; ?>
            </div>
            <div class="order-total">
                <?php echo isset($item['subtotal']) ? wp_kses_post(ppcart_format_price($item['subtotal'])) : ''; ?>
            </div>
        <?php endforeach; ?>

        <?php if (isset($attr['discounts']) || (isset($attr['tax']) && $attr['tax']['product_name']) || isset($attr['shipping'])) : ?>
            <div class="item" style="border: 0"><strong><?php echo esc_html($attr['subtotal']['product_name']); ?></strong></div>
            <div class="order-total" style="border: 0">
                <strong><?php ppcart_formatted_price($attr['subtotal']['total_amount']); ?></strong>
            </div>
            <br><br>
        <?php endif; ?>

        <?php if (isset($attr['discounts'])) : ?>
            <?php foreach ($attr['discounts'] as $item) : ?>
                <div class="item"><?php echo esc_html($item['product_name']); ?></div>
                <div class="order-total">
                    -<?php ppcart_formatted_price($item['total_amount']); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (isset($attr['shipping'])) : ?>
            <div class="item"><?php echo esc_html($attr['shipping']['product_name']); ?></div>
            <div class="order-total">
                <?php ppcart_formatted_price($attr['shipping']['total_amount']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($attr['tax']) && isset($attr['tax']['product_name']) && $attr['tax']['product_name']) : ?>
            <div class="item"><?php echo esc_html($attr['tax']['product_name']); ?></div>
            <div class="order-total">
                <?php ppcart_formatted_price($attr['tax']['total_amount']); ?>
            </div>
        <?php endif; ?>

        <div class="item" style="border: 0"><strong><?php echo esc_html($attr['total']['product_name']); ?></strong></div>
        <div class="order-total" style="border: 0">
            <strong><?php ppcart_formatted_price($attr['total']['total_amount']); ?></strong>
        </div>
    </div>
    <?php do_action('ppcart_receipt_after_order_details', $attr); ?>
</div>

