<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Order_Cart_Trait
{
    public function conditional_order_confirmations($order_id, $ppcart_product)
    {

        $ppcart_cc = ppcart_filter_input(INPUT_GET, 'ppcart-cc', FILTER_VALIDATE_INT);

        // does this product have multiple confirmations?
        if (!isset($ppcart_product->confirmations) || $ppcart_cc) {
            return;
        }

        do_action('ppcart_confirmation_match_conditions', $order_id, $ppcart_product);
    }

    public function maybe_change_thank_you_page($formAction, $order_id, $product_id)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-cart-maybe-change-thank-you-page.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function update_cart_amount()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-cart-ppcart-update-cart-amount.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}

/**
 * Build the update-cart-amount AJAX payload without mutating order summary rows.
 *
 * @param PPCart_Order $order             Order after load_from_post().
 * @param string       $amount_due_label  One-time total label.
 * @param string       $due_today_label   Recurring due-today label.
 * @return array<string, mixed>
 */
function ppcart_prepare_update_cart_amount_response($order, $amount_due_label, $due_today_label)
{
    $summary_items = [];

    foreach ($order->order_summary_items as $item) {
        $row = $item;
        $subtotal = isset($item['subtotal']) ? (float) $item['subtotal'] : 0.0;
        $formatted_subtotal = ppcart_format_price($subtotal, false);

        if (isset($item['type']) && 'discount' === $item['type']) {
            $formatted_subtotal = '(' . $formatted_subtotal . ')';
        }

        $row['subtotal'] = $formatted_subtotal;

        if (isset($item['type'])) {
            $row['type'] = sanitize_html_class((string) $item['type']);
        }

        $summary_items[] = $row;
    }

    $total_label = $amount_due_label;
    $response = [
        'total'               => $order->amount,
        'order_summary_items' => $summary_items,
        'total_label'         => $total_label,
        'total_price'         => ppcart_format_price($order->amount, false),
    ];

    if (!$order->order_summary_items) {
        $response['empty'] = __('Your cart is empty.', 'publishpress-cart');
    }

    if (isset($order->plan->type) && 'recurring' === $order->plan->type) {
        $sub = PPCart_Subscription::from_order($order);
        $sub = $sub->get_data();
        $sub_summary = apply_filters(
            'ppcart_format_subscription_order_detail',
            $sub['sub_payment_terms_plain'],
            $sub['sub_payment_terms_plain'],
            $order->plan,
            $sub['free_trial_days'],
            $sub['sign_up_fee'],
            $sub['sub_discount'],
            $sub['sub_discount_duration']
        );
        $sub_summary = preg_replace('/<br\\s*\\/?>/i', "\n", (string) $sub_summary);
        $response['sub_summary'] = wp_strip_all_tags((string) $sub_summary);
        $total_label = $due_today_label;
        $response['total_label'] = $total_label;
    }

    $response['total_html'] = '<span class="ppcart-total-label">' . esc_html($total_label) . '</span>';
    $response['total_html'] .= '<div class="total-rhs"><span class="price">' . esc_html($response['total_price']) . '</span></div>';

    return $response;
}
