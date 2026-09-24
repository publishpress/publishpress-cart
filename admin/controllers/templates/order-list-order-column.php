<?php

if (! defined('ABSPATH')) {
    exit;
}



$ppcart_order = new PPCart_Order($post_id);
$ppcart_order = apply_filters('ppcart_order', $ppcart_order);
$order_data = $ppcart_order->get_data();

switch ($column) {
    case 'order':
        echo '<a href="' . esc_url(get_edit_post_link($post_id)) . '">#' . esc_html($post_id) . '</a>';
        break;
    case 'status':
        $status_class = $order_data['status'];
        $status_label = $order_data['status_label'];
        if (class_exists('PPCart_Order_Refunds') && PPCart_Order_Refunds::is_partially_refunded($post_id, $order_data['amount'])) {
            $status_class = 'partially-refunded';
            $status_label = __('Partially refunded', 'publishpress-cart');
        }
        echo '<span class="ppcart-status ' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span>';
        break;
    case 'name':
        if ($order_data['user_account']) {
            $user_id = $order_data['user_account'] ?>
            <a href="<?php echo esc_url(get_edit_user_link($user_id)); ?>">
                <?php echo esc_html($order_data['customer_name']); ?>
            </a>
        <?php } else {
            echo esc_html($order_data['customer_name']);
        }
        break;
    case 'email':
        echo '<a href="mailto:' . esc_attr($order_data['email']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($order_data['email']) . '</a>';
        break;
    case 'order_date':
        echo esc_html(get_the_time('M j, Y', $post_id));
        break;
    case 'amount':
        $refund_amount = class_exists('PPCart_Order_Refunds') ? PPCart_Order_Refunds::get_refund_total($post_id) : 0;
        if ($refund_amount > 0) {
            $total_amount = PPCart_Order_Refunds::get_net_amount($post_id, $order_data['amount']);
            echo '<s>' . wp_kses_post(ppcart_format_price($order_data['amount'])) . '</s> ' . wp_kses_post(ppcart_format_price($total_amount));
        } else {
            echo wp_kses_post(ppcart_format_price($order_data['amount']));
        }
        break;
    case 'product':
        $product_url = ppcart_get_edit_post_url($order_data['product_id'] ?? 0);
        $product_name = (string) $order_data['product_name'];
        if ($product_url) {
            echo '<a href="' . esc_url($product_url) . '" class="ppcart-order-item-name">' . esc_html($product_name) . '</a>';
        } else {
            echo '<span class="ppcart-order-item-name">' . esc_html($product_name) . '</span>';
        }
        break;
    case 'invoice_number':
        $invoice_number = ppcart_get_post_meta($post_id, 'invoice_number', true);
        echo esc_html($invoice_number);
        break;
}
