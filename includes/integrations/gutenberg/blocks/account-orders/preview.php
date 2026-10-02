<?php

if (! defined('ABSPATH')) {
    exit;
}

$product     = esc_html__('Sample Product', 'publishpress-cart');
$date        = esc_html__('Jan 1, 2026', 'publishpress-cart');
$status_text = esc_html__('Paid', 'publishpress-cart');
$total       = esc_html__('$49.00', 'publishpress-cart');
$view        = esc_html__('View Order', 'publishpress-cart');
$product_lbl = esc_html__('Product', 'publishpress-cart');
$date_lbl    = esc_html__('Date', 'publishpress-cart');
$status_lbl  = esc_html__('Status', 'publishpress-cart');
$total_lbl   = esc_html__('Total', 'publishpress-cart');

$ppcart_account_orders_preview_buffer_level = ob_get_level();
$ppcart_account_orders_preview_buffer_active = true;
$ppcart_account_orders_preview_buffer_error = null;
ob_start(static function ($buffer, $phase) use (&$ppcart_account_orders_preview_buffer_active) {
    if ($phase & PHP_OUTPUT_HANDLER_FINAL) {
        $ppcart_account_orders_preview_buffer_active = false;
    }
    return $buffer;
});
try {
    ?>
<div class="tab-container order-history-tab">
    <div id="order-history" class="tab-content">
        <div class="overflow-x-auto">
            <table class="ppcart-account-table" cellpadding="0" cellspacing="0">
                <thead><tr><th><?php echo esc_html($product_lbl); ?></th><th><?php echo esc_html($date_lbl); ?></th><th><?php echo esc_html($status_lbl); ?></th><th><?php echo esc_html($total_lbl); ?></th><th></th></tr></thead>
                <tbody>
                    <tr><td><a href="#" data-testid="ppcart-account-order-preview-1-product"><?php echo esc_html($product); ?></a></td><td><?php echo esc_html($date); ?></td><td><?php echo esc_html($status_text); ?></td><td><?php echo esc_html($total); ?></td><td><a class="ppcart-account-action-button" href="#" data-testid="ppcart-account-order-preview-1-view"><?php echo esc_html($view); ?></a></td></tr>
                    <tr><td><a href="#" data-testid="ppcart-account-order-preview-2-product"><?php echo esc_html($product); ?></a></td><td><?php echo esc_html($date); ?></td><td><?php echo esc_html($status_text); ?></td><td><?php echo esc_html($total); ?></td><td><a class="ppcart-account-action-button" href="#" data-testid="ppcart-account-order-preview-2-view"><?php echo esc_html($view); ?></a></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
} catch (Throwable $ppcart_account_orders_preview_buffer_exception) {
    $ppcart_account_orders_preview_buffer_error = $ppcart_account_orders_preview_buffer_exception;
} finally {
    $ppcart_account_orders_preview_buffer_output = '';
    // Flush nested buffers into ours; never close a caller's or replacement buffer.
    while ($ppcart_account_orders_preview_buffer_active && ob_get_level() > $ppcart_account_orders_preview_buffer_level + 1) {
        $ppcart_account_orders_preview_buffer_nested_level = ob_get_level();
        try {
            if (! ob_end_flush()) {
                break;
            }
        } catch (Throwable $ppcart_account_orders_preview_buffer_exception) {
            $ppcart_account_orders_preview_buffer_error = $ppcart_account_orders_preview_buffer_error ?? $ppcart_account_orders_preview_buffer_exception;
            if (ob_get_level() >= $ppcart_account_orders_preview_buffer_nested_level) {
                break;
            }
        }
    }
    if ($ppcart_account_orders_preview_buffer_active && ob_get_level() === $ppcart_account_orders_preview_buffer_level + 1) {
        $ppcart_account_orders_preview_buffer_output = (string) ob_get_clean();
    }
}
if (null !== $ppcart_account_orders_preview_buffer_error) {
    throw $ppcart_account_orders_preview_buffer_error;
}
return $ppcart_account_orders_preview_buffer_output;
