<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Stripe_Subscription_Save_Trait
{
    public function do_subscription_save($order)
    {
        //insert SUBSCRIPTION
        $post_id = wp_insert_post(['post_title' => time() . " " . $order['name'], 'post_type' => ppcart_live_post_type('subscription'), 'post_status' => 'publish'], false);

        //update stripe meta
        ppcart_update_post_meta($post_id, 'firstname', $order['firstname']);
        ppcart_update_post_meta($post_id, 'lastname', $order['lastname']);
        ppcart_update_post_meta($post_id, 'email', strtolower(sanitize_email($order['email'])));
        ppcart_update_post_meta($post_id, 'phone', $order['phone']);

        do_action('ppcart_subscription_store_address_fields', $post_id, $order);

        ppcart_update_post_meta($post_id, 'product_id', $order['product_id']);
        ppcart_update_post_meta($post_id, 'product_name', $order['product_name']);
        ppcart_update_post_meta($post_id, 'item_name', $order['item_name']);
        ppcart_update_post_meta($post_id, 'plan_id', $order['plan_id']);
        ppcart_update_post_meta($post_id, 'plan_price', $order['plan_price']);
        ppcart_update_post_meta($post_id, 'option_id', $order['option_id']);
        ppcart_update_post_meta($post_id, 'amount', $order['amount']);
        ppcart_update_post_meta($post_id, 'vat_customer_type', $order['vat_customer_type']);
        ppcart_update_post_meta($post_id, 'vat_number', $order['vat_number']);

        ppcart_update_post_meta($post_id, 'ip_address', $order['ip_address']);
        ppcart_update_post_meta($post_id, 'user_account', $order['user_account']);
        ppcart_update_post_meta($post_id, 'accept_terms', $order['accept_terms']);
        ppcart_update_post_meta($post_id, 'consent', $order['consent']);
        ppcart_update_post_meta($post_id, 'on_sale', $order['on_sale']);

        ppcart_update_post_meta($post_id, 'pay_method', $order['pay_method']);
        ppcart_update_post_meta($post_id, 'sub_amount', $order['sub_amount']);
        ppcart_update_post_meta($post_id, 'sub_item_name', $order['sub_item_name']);
        ppcart_update_post_meta($post_id, 'sub_installments', $order['sub_installments']);
        ppcart_update_post_meta($post_id, 'sub_interval', $order['sub_interval']);
        ppcart_update_post_meta($post_id, 'sub_frequency', $order['sub_frequency']);
        ppcart_update_post_meta($post_id, 'sub_next_bill_date', $order['next_bill_date']);
        ppcart_update_post_meta($post_id, 'sub_end_date', $order['sub_end_date']);

        $vat_applied = 0;
        if (isset($order['vat']) && !empty($order['vat'])) {
            $vat_applied = ($order['sub_amount'] + $order['sign_up_fee']) * $order['vat']['vat_rate'] / 100;
            $vat_applied = round($vat_applied, 2);
            ppcart_update_post_meta($post_id, 'vat_amount', $vat_applied);
            ppcart_update_post_meta($post_id, 'vat_data', $order['vat_data']);
        }

        if (!empty($order['tax']) && $order['tax']['tax_type'] != 'inclusive_tax' && $vat_applied == 0) {
            $tax_applied = ($order['sub_amount'] + $order['sign_up_fee']) * $order['tax']['tax_rate'] / 100;
            $tax_applied = round($tax_applied, 2);
            ppcart_update_post_meta($post_id, 'tax_amount', $tax_applied);
            ppcart_update_post_meta($post_id, 'tax_data', $order['tax']);
        }
        if (!empty($order['page_id'])) {
            ppcart_update_post_meta($post_id, 'page_id', $order['page_id']);
            ppcart_update_post_meta($post_id, 'page_url', $order['page_url']);
        }

        if ($order['product_replaced']) {
            ppcart_update_post_meta($post_id, 'product_replaced', $order['product_replaced']);
        }

        if (!empty($order['free_trial_days'])) {
            ppcart_update_post_meta($post_id, 'free_trial_days', $order['free_trial_days']);
        }

        ppcart_update_post_meta($post_id, 'sign_up_fee', $order['sign_up_fee']);

        ppcart_update_post_meta($post_id, 'order_id', $order['order_id']);
        ppcart_update_post_meta($order['order_id'], 'subscription_id', $post_id);

        do_action('ppcart_subscription_store_pro_metadata', $post_id, $order);

        return $post_id;
    }

    public function do_stripe_subscription_save($order)
    {

        //insert SUBSCRIPTION
        $post_id = $this->do_subscription_save($order);
        if (isset($order['subscription'])) {
            $subscription = $order['subscription'];
            ppcart_update_post_meta($post_id, 'sub_status', $subscription->status);
            ppcart_update_post_meta($post_id, 'status', $subscription->status);
            ppcart_update_post_meta($post_id, 'subscription_id', $subscription->id);
            ppcart_update_post_meta($post_id, 'stripe_subscription_id', $subscription->id);
            ppcart_update_post_meta($post_id, 'stripe_plan_id', $subscription->plan->id);
            ppcart_update_post_meta($post_id, 'stripe_customer_id', $subscription->customer);
            ppcart_update_post_meta($post_id, 'sub_customer_id', $subscription->customer);
            ppcart_update_post_meta($post_id, 'sub_interval', $subscription->plan->interval);
            ppcart_update_post_meta($post_id, 'sub_next_bill_date', PPCart_Public::get_stripe_resource_value($subscription, 'current_period_end', 0));
            ppcart_update_post_meta($post_id, 'stripe_mode', $order['stripe_mode']);

            $sub_intent_id = '';
            $latest_invoice = PPCart_Public::get_stripe_resource_value($subscription, 'latest_invoice', null);
            $payment_intent = PPCart_Public::get_stripe_resource_value($latest_invoice, 'payment_intent', '');
            $sub_intent_id  = sanitize_text_field(PPCart_Public::get_stripe_resource_id($payment_intent));

            $this->maybe_store_connect_fee_meta($post_id, $sub_intent_id, 0, $order['currency'] ?? null);
        }

        $status = $order['subscription']->status ?? 'pending-payment';

        wp_update_post([ 'ID'   =>  $post_id, 'post_status'   =>  $status ]);

        ppcart_log_entry($post_id, __('Creating subscription.', 'publishpress-cart'));
        do_action('ppcart_fter_order_created', $post_id, $status, $order);

        return $post_id;
    }
}
