<?php

if (!defined('ABSPATH')) {
    exit;
}


$past_product_ids = ppcart_get_post_meta($order['id'], 'sub_cancel_integration_runs');
if (is_array($past_product_ids) && in_array($int['ppcart_sub_prod_id'] . ':' . $int['ppcart_sub_plan_id'], $past_product_ids)) {
    return;
}

ppcart_log_entry($order['id'], sprintf('Subscription cancel integration called by product ID: %s', $ppcart_product_id));

$args = [];
if ($order['user_account']) {
    $args[] = 'user_id=' . $order['user_account'];
}
if ($order['email']) {
    $args[] = 'email=' . $order['email'];
}

$shortcode = sprintf('[ppcart_customer_has_subscription product_id=%s plan_id=%s %s]', $int['ppcart_sub_prod_id'], $int['ppcart_sub_plan_id'], implode(' ', $args));

if ($subscription_id = do_shortcode($shortcode)) {
    $sub = new \PPCart_Subscription($subscription_id);
    $now = ($int['ppcart_sub_cancel'] == 'yes') ? true : false;

    $out = ppcart_do_cancel_subscription($sub, $sub->subscription_id, $now, $echo = false);

    if ($out == 'OK') {
        /* translators: %s: subscription ID. */
        ppcart_log_entry($order['id'], sprintf(__('Subscription ID: %s has been canceled', 'publishpress-cart'), $subscription_id));
        ppcart_add_post_meta($order['id'], 'sub_cancel_integration_runs', $int['ppcart_sub_prod_id'] . ':' . $int['ppcart_sub_plan_id']);
    } else {
        /* translators: 1: subscription ID, 2: error message. */
        ppcart_log_entry($order['id'], sprintf(__('Error canceling subscription ID: %1$s! Message: %2$s', 'publishpress-cart'), $subscription_id, $out));
    }
} else {
    /* translators: 1: product ID, 2: plan ID. */
    ppcart_log_entry($order['id'], sprintf(__('No active subscriptions found for product ID: %1$s and plan ID=%2$s', 'publishpress-cart'), $int['ppcart_sub_prod_id'], $int['ppcart_sub_plan_id']));
}
