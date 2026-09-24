<?php

if (! defined('ABSPATH')) {
    exit;
}


ppcart_update_post_meta($post_id, 'sub_status', $subscription->status);
ppcart_update_post_meta($post_id, 'status', $subscription->status);
ppcart_update_post_meta($post_id, 'subscription_id', $subscription->id);
ppcart_update_post_meta($post_id, 'stripe_subscription_id', $subscription->id);
ppcart_update_post_meta($post_id, 'stripe_plan_id', $subscription->plan->id);
ppcart_update_post_meta($post_id, 'stripe_customer_id', $subscription->customer);
ppcart_update_post_meta($post_id, 'sub_customer_id', $subscription->customer);
ppcart_update_post_meta($post_id, 'sub_interval', $subscription->plan->interval);
ppcart_update_post_meta($post_id, 'sub_next_bill_date', $this->get_stripe_resource_value($subscription, 'current_period_end', 0));

if (isset($subscription->plan->trial_period_days)) {
    ppcart_update_post_meta($post_id, 'free_trial_days', $subscription->plan->trial_period_days);
}
if (isset($subscription->sign_up_fee)) {
    ppcart_update_post_meta($post_id, 'sign_up_fee', $subscription->sign_up_fee);
}

wp_update_post([ 'ID'   =>  $post_id, 'post_status'   =>  $subscription->status ]);
/* translators: %s: subscription status */
ppcart_log_entry($post_id, sprintf(__('Subscription status updated to %s', 'publishpress-cart'), $subscription->status));
ppcart_trigger_integrations($subscription->status, $post_id);

// run integrations
if (isset($subscription->status) && $subscription->status != 'incomplete') {
    $ppcart_order_post = filter_input(INPUT_POST, 'ppcart-order', FILTER_VALIDATE_INT);
    if (false === $ppcart_order_post || null === $ppcart_order_post) { // only check post data when this is not an upsell
        $order_info = $this->order_info_from_post();
        $order_info['ID'] = $post_id;
        ppcart_trigger_integrations('paid', $order_info);
    }
}
