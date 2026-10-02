<?php

if (! defined('ABSPATH')) {
    exit;
}

$heading     = esc_html__('Active Subscriptions', 'publishpress-cart');
$product     = esc_html__('Sample Subscription', 'publishpress-cart');
$status_text = esc_html__('Active', 'publishpress-cart');
$next        = esc_html__('Feb 1, 2026', 'publishpress-cart');
$price       = esc_html__('$19/month', 'publishpress-cart');
$pay         = esc_html__('Pay', 'publishpress-cart');
$manage      = esc_html__('Manage', 'publishpress-cart');
$product_lbl = esc_html__('Product', 'publishpress-cart');
$status_lbl  = esc_html__('Status', 'publishpress-cart');
$next_lbl    = esc_html__('Next Payment', 'publishpress-cart');
$price_lbl   = esc_html__('Price', 'publishpress-cart');

$ppcart_account_subscriptions_preview_buffer_level = ob_get_level();
$ppcart_account_subscriptions_preview_buffer_active = true;
$ppcart_account_subscriptions_preview_buffer_error = null;
ob_start(static function ($buffer, $phase) use (&$ppcart_account_subscriptions_preview_buffer_active) {
    if ($phase & PHP_OUTPUT_HANDLER_FINAL) {
        $ppcart_account_subscriptions_preview_buffer_active = false;
    }
    return $buffer;
});
try {
    ?>
<div class="tab-container subscriptions-tab">
    <div id="subscriptions" class="tab-content">
        <div id="subscription-all" class="ppcart-account-tab-pane">
            <h4><?php echo esc_html($heading); ?></h4>
            <div class="overflow-x-auto">
                <table class="ppcart-account-table" cellpadding="0" cellspacing="0">
                    <thead><tr><th><?php echo esc_html($product_lbl); ?></th><th><?php echo esc_html($status_lbl); ?></th><th><?php echo esc_html($next_lbl); ?></th><th><?php echo esc_html($price_lbl); ?></th><th></th></tr></thead>
                    <tbody>
                        <tr><td><?php echo esc_html($product); ?></td><td><?php echo esc_html($status_text); ?><br><small><?php esc_html_e('Cancels Feb 1, 2026', 'publishpress-cart'); ?></small></td><td><?php echo esc_html($next); ?></td><td><?php echo esc_html($price); ?></td><td><a class="ppcart-account-action-button" href="#" data-testid="ppcart-account-subscription-preview-pay"><?php echo esc_html($pay); ?></a> <a class="ppcart-account-action-button" href="#" data-testid="ppcart-account-subscription-preview-manage"><?php echo esc_html($manage); ?></a></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
} catch (Throwable $ppcart_account_subscriptions_preview_buffer_exception) {
    $ppcart_account_subscriptions_preview_buffer_error = $ppcart_account_subscriptions_preview_buffer_exception;
} finally {
    $ppcart_account_subscriptions_preview_buffer_output = '';
    // Flush nested buffers into ours; never close a caller's or replacement buffer.
    while ($ppcart_account_subscriptions_preview_buffer_active && ob_get_level() > $ppcart_account_subscriptions_preview_buffer_level + 1) {
        $ppcart_account_subscriptions_preview_buffer_nested_level = ob_get_level();
        try {
            if (! ob_end_flush()) {
                break;
            }
        } catch (Throwable $ppcart_account_subscriptions_preview_buffer_exception) {
            $ppcart_account_subscriptions_preview_buffer_error = $ppcart_account_subscriptions_preview_buffer_error ?? $ppcart_account_subscriptions_preview_buffer_exception;
            if (ob_get_level() >= $ppcart_account_subscriptions_preview_buffer_nested_level) {
                break;
            }
        }
    }
    if ($ppcart_account_subscriptions_preview_buffer_active && ob_get_level() === $ppcart_account_subscriptions_preview_buffer_level + 1) {
        $ppcart_account_subscriptions_preview_buffer_output = (string) ob_get_clean();
    }
}
if (null !== $ppcart_account_subscriptions_preview_buffer_error) {
    throw $ppcart_account_subscriptions_preview_buffer_error;
}
return $ppcart_account_subscriptions_preview_buffer_output;
