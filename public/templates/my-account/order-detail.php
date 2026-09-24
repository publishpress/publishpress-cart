<?php

if (! defined('ABSPATH')) {
    exit;
}

$show_back_link = ! isset($attr['showBackLink']) || (bool) $attr['showBackLink'];
?>

<div class="ppcart-my-account ppcart-my-subscription-page">
    <?php if ($show_back_link && ($account_page_id = get_option('_ppcart_myaccount_page_id'))) : ?>
    <div class="back-btn">
        <a href="<?php echo esc_url(get_permalink($account_page_id)); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-order-back')); ?>"><img src="<?php echo esc_url(PPCART_BASE_URL . 'public/images/arrow-back.svg'); ?>" alt="Icon" /> <?php esc_html_e('Back', 'publishpress-cart'); ?></a>
    </div>
    <?php endif; ?>
    <div class="ppcart-account-subscription">
        <?php
        $order_post_id = ppcart_filter_input_request('ppcart-order', FILTER_VALIDATE_INT);
if ((false === $order_post_id || null === $order_post_id) && ! empty($attr['order'])) {
    $order_post_id = absint($attr['order']);
}
if (false !== $order_post_id && null !== $order_post_id) {
    $order_post_id = absint($order_post_id);
    $ppcart_order  = new PPCart_Order($order_post_id);
    $order_data    = $ppcart_order->get_data();
    ppcart_template('shortcodes/receipt', '', ppcart_get_item_list($order_post_id, false));
    $address = ppcart_order_address($order_post_id);
    ?>

            <table class="ppcart-subscription-table" border="0">
                <tr>
                    <?php if ($address) : ?>
                    <td>
                        <h3 class="ppcart-account-title"><?php esc_html_e('Address', 'publishpress-cart'); ?></h3>
                        <?php echo wp_kses_post($address); ?>
                    </td>
                    <?php endif; ?>
                    <td width="200">
                        <h3 class="ppcart-account-title"><?php esc_html_e('Invoice', 'publishpress-cart'); ?></h3>
                        <?php echo wp_kses_post($order_data['invoice_link_html']); ?>
                    </td>
                </tr>
            </table>
<?php } else {
    esc_html_e('No orders found.', 'publishpress-cart');
} ?>

    </div>
</div>
