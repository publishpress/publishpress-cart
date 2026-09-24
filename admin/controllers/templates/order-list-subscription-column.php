<?php

if (! defined('ABSPATH')) {
    exit;
}


$ppcart_subscription = new PPCart_Subscription($post_id);
$sub = $ppcart_subscription->get_data();

switch ($column) {
    case 'sub_id':
        echo '<a href="' . esc_url(get_edit_post_link($post_id)) . '">#' . esc_html($post_id) . '</a>';
        break;
    case 'status':
        echo '<span class="ppcart-status ' . esc_attr($sub['status']) . '">' . esc_html($sub['status_label']) . '</span>';
        break;
    case 'name':
        if ($sub['user_account']) {
            $user_id = $sub['user_account'] ?>
            <a href="<?php echo esc_url(get_edit_user_link($user_id)); ?>">
                <?php echo esc_html($sub['customer_name']); ?>
            </a>
        <?php
        } else {
            echo esc_html($sub['customer_name']);
        }
        break;
    case 'email':
        echo '<a href="mailto:' . esc_attr($sub['email']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($sub['email']) . '</a>';
        break;
    case 'start_date':
        echo esc_html($sub['start_date']);
        break;
    case 'next_payment':
        echo ($sub['next_pay_date']) ? esc_html($sub['next_pay_date']) : '-';
        break;
    case 'amount':
        echo wp_kses_post($sub['sub_payment']);
        break;
    case 'product':
        $product_url = ppcart_get_edit_post_url($sub['product_id'] ?? 0);
        $product_name = (string) $sub['product_name'];
        if ($product_url) {
            echo '<a href="' . esc_url($product_url) . '" class="ppcart-order-item-name">' . esc_html($product_name) . '</a>';
        } else {
            echo '<span class="ppcart-order-item-name">' . esc_html($product_name) . '</span>';
        }
        break;
}
