<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="tab-container order-history-tab">
    <?php $show_order_detail_link = (bool) apply_filters('ppcart_my_account_show_order_detail_link', false); ?>
    <div id="order-history" class="tab-content">
        <div class="overflow-x-auto">
            <table class="ppcart-account-table" cellpadding="0" cellspacing="0">
                <thead>
                    <th><?php esc_html_e('Product', 'publishpress-cart'); ?></th>
                    <th><?php esc_html_e('Date', 'publishpress-cart'); ?></th>
                    <th><?php esc_html_e('Status', 'publishpress-cart'); ?></th>
                    <th><?php esc_html_e('Total', 'publishpress-cart'); ?></th>
                    <?php if ($show_order_detail_link) {
                        ?><th></th><?php
                    } ?>
                </thead>
                <tbody>
                    <?php
                    $hide_free = (bool) get_option('_ppcart_myaccount_hide_free');
$orders = ppcart_get_user_orders(get_current_user_id(), ['paid', 'completed', 'refunded'], 0, false, $hide_free);
if ($orders) {
    foreach ($orders as $user_order) {
        $order_status = (in_array($user_order['status'], ['pending','pending-payment','initiated'])) ? 'pending' : $user_order['status']; ?>
                    <tr>
                        <td><a href="<?php echo esc_url(add_query_arg('ppcart-order', absint($user_order['ID']))); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-order-' . $user_order['ID'] . '-product')); ?>"><?php echo esc_html($user_order['product_name']); ?></a></td>
                        <td><?php echo esc_html($user_order['date']); ?></td>
                        <td data-status="<?php echo esc_attr(sanitize_html_class($order_status)); ?>"><?php echo esc_html($user_order['status_label']); ?></td>
                        <td><?php echo wp_kses_post(ppcart_format_price($user_order['amount'])); ?></td>

                        <?php if ($show_order_detail_link) { ?>
                        <td>
                            <?php if ($order_status == 'pending') { ?>
                            <?php } else {
                                $invoice_id = $user_order['ID'];
                                if (isset($user_order['ob_parent']) || isset($user_order['us_parent']) || isset($user_order['ds_parent'])) {
                                    if (isset($user_order['ob_parent'])) {
                                        $invoice_id = $user_order['ob_parent'];
                                    } elseif (isset($user_order['ds_parent'])) {
                                        $invoice_id = $user_order['ds_parent'];
                                    } else {
                                        $invoice_id = $user_order['us_parent'];
                                    }
                                }
                                ?>
                                    <a class="ppcart-account-action-button" href="<?php echo esc_url(add_query_arg('ppcart-order', absint($user_order['ID']))); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-order-' . $user_order['ID'] . '-view')); ?>"><?php esc_html_e('View Order', 'publishpress-cart'); ?></a>
                            <?php } ?>
                        </td>
                        <?php } ?>
                    </tr>
    <?php }
} else { ?>
                    <tr>
                        <td class="ppcart-account-orders-empty" colspan="<?php echo esc_attr($show_order_detail_link ? 5 : 4); ?>"><?php esc_html_e('No orders found', 'publishpress-cart'); ?></td>
                    </tr>
<?php } ?>

                </tbody>
            </table>
        </div>
    </div>
</div><!-- container -->
