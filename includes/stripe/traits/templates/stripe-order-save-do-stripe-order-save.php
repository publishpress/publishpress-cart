<?php

if (! defined('ABSPATH')) {
    exit;
}


if ($order_info['intent_id'] && $this->find_stripe_webhook_order($order_info)) {
    $order_post_id = $this->find_stripe_webhook_order($order_info);
    ppcart_update_post_meta($order_post_id, 'product_id', $order_info['product_id']);
    ppcart_update_post_meta($order_post_id, 'item_name', $order_info['item_name']);
    ppcart_update_post_meta($order_post_id, 'plan_id', $order_info['plan_id']);
    ppcart_update_post_meta($order_post_id, 'ip_address', $order_info['ip_address']);
    ppcart_update_post_meta($order_post_id, 'user_account', $order_info['user_account']);
    ppcart_update_post_meta($order_post_id, 'accept_terms', $order_info['accept_terms']);
    ppcart_update_post_meta($order_post_id, 'consent', $order_info['consent']);
    ppcart_update_post_meta($order_post_id, 'stripe_mode', $order_info['stripe_mode']);
    ppcart_update_post_meta($order_post_id, 'pay_method', $order_info['pay_method']);
    ppcart_update_post_meta($order_post_id, 'currency', $order_info['currency']);
    ppcart_update_post_meta($order_post_id, 'sub_total', $order_info['amount']);
    ppcart_update_post_meta($order_post_id, 'vat_customer_type', $order_info['vat_customer_type']);
    ppcart_update_post_meta($order_post_id, 'vat_number', $order_info['vat_number']);
    $vat_applied = 0;
    if (isset($order_info['vat']) && !empty($order_info['vat'])) {
        $vat_applied = $order_info['plan_price'] * $order_info['vat']['vat_rate'] / 100;
        $vat_applied = round($vat_applied, 2);
        ppcart_update_post_meta($order_post_id, 'vat_amount', $vat_applied);
        ppcart_update_post_meta($order_post_id, 'vat_data', $order_info['vat']);
    }

    if (!empty($order_info['tax']) && $order_info['tax']['tax_type'] != 'inclusive_tax' && $vat_applied == 0) {
        $tax_applied = $order_info['plan_price'] * $order_info['tax']['tax_rate'] / 100;
        $tax_applied = round($tax_applied, 2);
        ppcart_update_post_meta($order_post_id, 'tax_amount', $tax_applied);
        ppcart_update_post_meta($order_post_id, 'tax_data', $order_info['tax']);
    }

    if (isset($order_info['us_vat_data']) && !empty($order_info['us_vat_data'])) {
        ppcart_update_post_meta($order_post_id, 'vat_amount', $order_info['us_vat_amount']);
        ppcart_update_post_meta($order_post_id, 'vat_data', $order_info['us_vat_data']);
    }

    if (isset($order_info['ds_vat_data']) && !empty($order_info['ds_vat_data'])) {
        ppcart_update_post_meta($order_post_id, 'vat_amount', $order_info['ds_vat_amount']);
        ppcart_update_post_meta($order_post_id, 'vat_data', $order_info['ds_vat_data']);
    }

    if (isset($order_info['us_tax_data']) && !empty($order_info['us_tax_data']) && isset($order_info['us_tax_amount']) && !empty($order_info['us_tax_amount']) && !isset($order_info['us_vat_amount'])) {
        ppcart_update_post_meta($order_post_id, 'tax_amount', $order_info['us_tax_amount']);
        ppcart_update_post_meta($order_post_id, 'tax_data', $order_info['us_tax_data']);
    }

    if (isset($order_info['ds_tax_data']) && !empty($order_info['ds_tax_data']) && isset($order_info['ds_tax_amount']) && !empty($order_info['ds_tax_amount']) && !isset($order_info['ds_vat_amount'])) {
        ppcart_update_post_meta($order_post_id, 'tax_amount', $order_info['ds_tax_amount']);
        ppcart_update_post_meta($order_post_id, 'tax_data', $order_info['ds_tax_data']);
    }

    if ($order_info['pay_method'] == 'stripe') {
        ppcart_update_post_meta($order_post_id, 'intent_id', $order_info['intent_id']);
        ppcart_update_post_meta($order_post_id, 'stripe_customer_id', $order_info['customer']);
        ppcart_update_post_meta($order_post_id, 'stripe_mode', $order_info['stripe_mode']);
        $this->maybe_store_connect_fee_meta($order_post_id, $order_info['intent_id'], ppcart_price_in_cents($order_info['amount'], $order_info['currency']), $order_info['currency']);
    }

    add_filter('ppcart_is_order_complete', [$this, 'is_stripe_order_complete'], 10, 2);
    $this->maybe_do_order_complete($order_post_id, $order_info);
    remove_filter('ppcart_is_order_complete', [$this, 'is_stripe_order_complete'], 10, 2);
} else {
    $order_post_id = $this->do_order_save($order_info);
    if ($order_info['pay_method'] == 'stripe') {
        ppcart_update_post_meta($order_post_id, 'intent_id', $order_info['intent_id']);
        ppcart_update_post_meta($order_post_id, 'stripe_customer_id', $order_info['customer']);
        ppcart_update_post_meta($order_post_id, 'stripe_mode', $order_info['stripe_mode']);
        $this->maybe_store_connect_fee_meta($order_post_id, $order_info['intent_id'], ppcart_price_in_cents($order_info['amount'], $order_info['currency']), $order_info['currency']);
    }
    ppcart_update_post_meta($order_post_id, 'sub_total', $order_info['amount']);

    $vat_applied = 0;
    if (isset($order_info['vat']) && !empty($order_info['vat'])) {
        $vat_applied = $order_info['plan_price'] * $order_info['vat']['vat_rate'] / 100;
        $vat_applied = round($vat_applied, 2);
        ppcart_update_post_meta($order_post_id, 'vat_amount', $vat_applied);
        ppcart_update_post_meta($order_post_id, 'vat_data', $order_info['vat']);
    }

    if (!empty($order_info['tax']) && $order_info['tax']['tax_type'] != 'inclusive_tax' && $vat_applied == 0) {
        $tax_applied = $order_info['plan_price'] * $order_info['tax']['tax_rate'] / 100;
        $tax_applied = round($tax_applied, 2);
        ppcart_update_post_meta($order_post_id, 'tax_amount', $tax_applied);
        ppcart_update_post_meta($order_post_id, 'tax_data', $order_info['tax']);
    }

    if (isset($order_info['us_vat_data']) && !empty($order_info['us_vat_data'])) {
        ppcart_update_post_meta($order_post_id, 'vat_amount', $order_info['us_vat_amount']);
        ppcart_update_post_meta($order_post_id, 'vat_data', $order_info['us_vat_data']);
    }

    if (isset($order_info['ds_vat_data']) && !empty($order_info['ds_vat_data'])) {
        ppcart_update_post_meta($order_post_id, 'vat_amount', $order_info['ds_vat_amount']);
        ppcart_update_post_meta($order_post_id, 'vat_data', $order_info['ds_vat_data']);
    }

    if (isset($order_info['us_tax_data']) && !empty($order_info['us_tax_data']) && isset($order_info['us_tax_amount']) && !empty($order_info['us_tax_amount']) && !isset($order_info['us_vat_amount'])) {
        ppcart_update_post_meta($order_post_id, 'tax_amount', $order_info['us_tax_amount']);
        ppcart_update_post_meta($order_post_id, 'tax_data', $order_info['us_tax_data']);
    }

    if (isset($order_info['ds_tax_data']) && !empty($order_info['ds_tax_data']) && isset($order_info['ds_tax_amount']) && !empty($order_info['ds_tax_amount']) && !isset($order_info['ds_vat_amount'])) {
        ppcart_update_post_meta($order_post_id, 'tax_amount', $order_info['ds_tax_amount']);
        ppcart_update_post_meta($order_post_id, 'tax_data', $order_info['ds_tax_data']);
    }
}

return $order_post_id;
