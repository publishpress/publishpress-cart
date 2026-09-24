<?php

if (! defined('ABSPATH')) {
    exit;
}

global $ppcart_stripe;
$subscription = $attr;
$subscription_status = (in_array($subscription->status, ['pending-payment','initiated'])) ? 'pending' : $subscription->status;
if ($subscription_status == 'completed' || $subscription_status == 'pending' || $subscription_status == 'canceled' || $subscription_status == 'paused') {
    $next = "--";
} else {
    if ($subscription->sub_next_bill_date && !is_numeric($subscription->sub_next_bill_date)) {
        $subscription->sub_next_bill_date = strtotime($subscription->sub_next_bill_date);
    }
    $next = date_i18n(get_option('date_format'), $subscription->sub_next_bill_date);
}

?>
<tr>
    <td><?php echo esc_html($subscription->product_name); ?></td>
    <td data-status="<?php echo esc_attr(sanitize_html_class($subscription_status)); ?>"><?php echo esc_html($subscription->status_label); ?>
        <?php if ($subscription->cancel_date && $subscription_status != 'canceled' && $next != '--') : ?>
            <br><small><?php
            /* translators: %s: cancellation date. */
            printf(esc_html__('Cancels %s', 'publishpress-cart'), esc_html($next)); ?></small>
        <?php endif; ?>
    </td>
    <td><?php echo esc_html(($subscription->cancel_date) ? '--' : $next); ?></td>
    <td><?php echo wp_kses_post($subscription->sub_payment); ?></td>

    <td>
        <?php if ($subscription->status == 'incomplete' || $subscription->status == 'past_due' || $subscription->status == 'pending-payment') : ?>
            <a class="ppcart-account-action-button" href="<?php echo esc_url(add_query_arg(['ppcart-plan' => absint($subscription->ID), 'action' => 'pay'])); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-subscription-' . $subscription->ID . '-pay')); ?>"><?php esc_html_e('Pay', 'publishpress-cart'); ?></a>
        <?php  endif; ?>
        <?php
            $additionalParam = "";
$customer_portal = get_option('_ppcart_stripe_customer_portal_enable');
if ($subscription->pay_method == 'stripe' && $customer_portal) {
    $additionalParam = "&ppcart-manage=stripe";
}
$manage_args = ['ppcart-plan' => absint($subscription->ID)];
if ($additionalParam) {
    $manage_args['ppcart-manage'] = 'stripe';
}
?>
        <a class="ppcart-account-action-button" href="<?php echo esc_url(add_query_arg($manage_args)); ?>" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-subscription-' . $subscription->ID . '-manage')); ?>"><?php esc_html_e('Manage', 'publishpress-cart'); ?></a>
    </td>
</tr>
