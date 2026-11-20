<?php

if (! defined('ABSPATH')) {
    exit;
}


$ppcart_subscription = new PPCart_Subscription($post_id);
$sub = $ppcart_subscription->get_data();

// A subscription that is set to cancel shows when it ends instead of a next payment.
$ppcart_pending_cancellation = ! empty($sub['cancel_date']) && ! in_array($sub['status'], [ 'canceled', 'completed' ], true);
$ppcart_cancel_ends_on = '';
if ($ppcart_pending_cancellation) {
    if (is_numeric($sub['cancel_at'] ?? '') && (int) $sub['cancel_at'] > 0) {
        $ppcart_cancel_ends_on = get_date_from_gmt(gmdate('Y-m-d H:i:s', (int) $sub['cancel_at']), 'M j, Y');
    } elseif (! empty($sub['next_pay_date']) && 'n/a' !== $sub['next_pay_date']) {
        $ppcart_cancel_ends_on = $sub['next_pay_date'];
    } elseif (! empty($sub['end_date'])) {
        $ppcart_cancel_ends_on = $sub['end_date'];
    }
}

switch ($column) {
    case 'sub_id':
        echo '<a href="' . esc_url(get_edit_post_link($post_id)) . '">#' . esc_html($post_id) . '</a>';
        $subscription_post = get_post($post_id);
        if ($subscription_post instanceof WP_Post) {
            get_inline_data($subscription_post);
        }
        break;
    case 'status':
        if ($ppcart_pending_cancellation) {
            $ppcart_cancel_label = $ppcart_cancel_ends_on
                /* translators: %s: date the subscription ends. */
                ? sprintf(__('Cancels %s', 'publishpress-cart'), $ppcart_cancel_ends_on)
                : __('Cancellation Pending', 'publishpress-cart');
            echo '<span class="ppcart-status pending-cancellation">' . esc_html($ppcart_cancel_label) . '</span>';
            break;
        }
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
        $email_url = 'mailto:' . (string) $sub['email'];
        echo '<a href="' . esc_url($email_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($sub['email']) . '</a>';
        break;
    case 'start_date':
        echo esc_html($sub['start_date']);
        break;
    case 'next_payment':
        echo ($sub['next_pay_date'] && ! $ppcart_pending_cancellation) ? esc_html($sub['next_pay_date']) : '-';
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
