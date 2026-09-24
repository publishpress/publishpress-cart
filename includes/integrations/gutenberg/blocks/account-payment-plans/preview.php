<?php

if (! defined('ABSPATH')) {
    exit;
}

$heading     = esc_html__('Active Plans', 'publishpress-cart');
$product     = esc_html__('Sample Plan', 'publishpress-cart');
$status_text = esc_html__('Active', 'publishpress-cart');
$next        = esc_html__('Feb 1, 2026', 'publishpress-cart');
$price       = esc_html__('$99 x 6', 'publishpress-cart');
$pay         = esc_html__('Pay', 'publishpress-cart');
$manage      = esc_html__('Manage', 'publishpress-cart');
$product_lbl = esc_html__('Product', 'publishpress-cart');
$status_lbl  = esc_html__('Status', 'publishpress-cart');
$next_lbl    = esc_html__('Next Payment', 'publishpress-cart');
$price_lbl   = esc_html__('Price', 'publishpress-cart');

ob_start();
?>
<div class="tab-container payment-plans-tab">
    <div id="payment-plans" class="tab-content">
        <div id="plan-all" class="ppcart-account-tab-pane">
            <h4><?php echo esc_html($heading); ?></h4>
            <div class="overflow-x-auto">
                <table class="ppcart-account-table" cellpadding="0" cellspacing="0">
                    <thead><tr><th><?php echo esc_html($product_lbl); ?></th><th><?php echo esc_html($status_lbl); ?></th><th><?php echo esc_html($next_lbl); ?></th><th><?php echo esc_html($price_lbl); ?></th><th></th></tr></thead>
                    <tbody>
                        <tr><td><?php echo esc_html($product); ?></td><td><?php echo esc_html($status_text); ?></td><td><?php echo esc_html($next); ?></td><td><?php echo esc_html($price); ?></td><td><a class="ppcart-account-action-button" href="#" data-testid="ppcart-account-payment-plan-preview-pay"><?php echo esc_html($pay); ?></a> <a class="ppcart-account-action-button" href="#" data-testid="ppcart-account-payment-plan-preview-manage"><?php echo esc_html($manage); ?></a></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
return ob_get_clean();
