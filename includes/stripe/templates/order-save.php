<?php

if (! defined('ABSPATH')) {
    exit;
}


$vat_customer_type = filter_input(INPUT_POST, 'vat-customer-type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$posted_country = filter_input(INPUT_POST, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$vat_customer_type = is_string($vat_customer_type) ? sanitize_text_field($vat_customer_type) : '';
$posted_country = is_string($posted_country) ? sanitize_text_field($posted_country) : '';

$order_status = $status;

//insert order
$order_post_id = wp_insert_post(['post_title' => time() . " " . $order_info['name'], 'post_type' => ppcart_live_post_type('order'), 'post_status' => 'publish'], false);

//update stripe meta
ppcart_update_post_meta($order_post_id, 'firstname', $order_info['firstname']);
ppcart_update_post_meta($order_post_id, 'lastname', $order_info['lastname']);
ppcart_update_post_meta($order_post_id, 'email', strtolower(sanitize_email($order_info['email'])));
ppcart_update_post_meta($order_post_id, 'phone', $order_info['phone']);

ppcart_update_post_meta($order_post_id, 'country', $order_info['country']);
ppcart_update_post_meta($order_post_id, 'address1', $order_info['address1']);
ppcart_update_post_meta($order_post_id, 'address2', $order_info['address2']);
ppcart_update_post_meta($order_post_id, 'city', $order_info['city']);
ppcart_update_post_meta($order_post_id, 'state', $order_info['state']);
ppcart_update_post_meta($order_post_id, 'zip', $order_info['zip']);
ppcart_update_post_meta($order_post_id, 'vat_customer_type', $order_info['vat_customer_type']);
ppcart_update_post_meta($order_post_id, 'vat_number', $order_info['vat_number']);

ppcart_update_post_meta($order_post_id, 'transaction_id', $order_info['transaction_id']);
ppcart_update_post_meta($order_post_id, 'product_id', $order_info['product_id']);
ppcart_update_post_meta($order_post_id, 'product_name', $order_info['product_name']);
ppcart_update_post_meta($order_post_id, 'amount', $order_info['amount']);
ppcart_update_post_meta($order_post_id, 'item_name', $order_info['item_name']);
ppcart_update_post_meta($order_post_id, 'plan_id', $order_info['plan_id']);
ppcart_update_post_meta($order_post_id, 'option_id', $order_info['option_id']);
ppcart_update_post_meta($order_post_id, 'ip_address', $order_info['ip_address']);
ppcart_update_post_meta($order_post_id, 'user_account', $order_info['user_account']);
ppcart_update_post_meta($order_post_id, 'accept_terms', $order_info['accept_terms']);
ppcart_update_post_meta($order_post_id, 'consent', $order_info['consent']);
ppcart_update_post_meta($order_post_id, 'on_sale', $order_info['on_sale']);

if ($order_info['product_replaced']) {
    ppcart_update_post_meta($order_post_id, 'product_replaced', $order_info['product_replaced']);
}

$vat_applied = 0;
if (isset($order_info['vat']) && !empty($order_info['vat'])) {
    $vat_data = PPCart_VAT::get_order_vat_data($order_info);
    $planPrice = $order_info['plan_price'];
    if (isset($vat_data) && !empty($vat_data)) {
        if ('consumer' === $vat_customer_type || ('business' === $vat_customer_type && $posted_country === $vat_data['vat_merchant_country']) || ('business' === $vat_customer_type && isset($vat_data['vat_all_eu_businesses']) && ! empty($vat_data['vat_all_eu_businesses']))) {
            $vat_applied = $planPrice * $vat_data['vat_rate'] / 100;
            $vat_applied = round($vat_applied, 2);
            ppcart_update_post_meta($order_post_id, 'vat_amount', $vat_applied);
            ppcart_update_post_meta($order_post_id, 'vat_data', $vat_data);
        }
    }
}

if (isset($order_info['tax']) && !empty($order_info['tax']) && $vat_applied == 0) {
    $tax_data = PPCart_Tax::get_order_tax_data($order_info);
    $planPrice = $order_info['plan_price'];

    $tax_applied = 0;
    if (!empty($tax_data) && $tax_data['tax_type'] != 'inclusive_tax') {
        $tax_applied = $planPrice * $tax_data['tax_rate'] / 100;
        $tax_applied = round($tax_applied, 2);
        ppcart_update_post_meta($order_post_id, 'tax_amount', $tax_applied);
        ppcart_update_post_meta($order_post_id, 'tax_data', $tax_data);
    }
}

if (!empty($order_info['page_id'])) {
    ppcart_update_post_meta($order_post_id, 'page_id', $order_info['page_id']);
    ppcart_update_post_meta($order_post_id, 'page_url', $order_info['page_url']);
}

if (isset($order_info['custom'])) {
    ppcart_update_post_meta($order_post_id, 'custom_prices', $order_info['custom']);
}

if (isset($order_info['custom_fields'])) {
    ppcart_update_post_meta($order_post_id, 'custom_fields', $order_info['custom_fields']);
}

if (isset($order_info['custom_fields_post_data'])) {
    ppcart_update_post_meta($order_post_id, 'custom_fields_post_data', $order_info['custom_fields_post_data']);
}

if ($order_info['amount'] > 0 || $order_info['plan_type'] == 'recurring') {
    ppcart_update_post_meta($order_post_id, 'pay_method', $order_info['pay_method']);
    ppcart_update_post_meta($order_post_id, 'currency', $order_info['currency']);
    ppcart_update_post_meta($order_post_id, 'payment_status', $order_status);
    ppcart_update_post_meta($order_post_id, 'status', $order_status);

    if ($order_info['pay_method'] == 'cod') {
        $parent_id = $order_info['ID'];
        $order_info['ID'] = $order_post_id;
        ppcart_trigger_integrations('pending', $order_info);
        $order_info['ID'] = $parent_id;
    }

    $submitSuccess = wp_update_post([ 'ID' =>  $order_post_id, 'post_status' => $order_status ]);
    /* translators: %s: order status. */
    ppcart_log_entry($order_post_id, sprintf(__('%s order created.', 'publishpress-cart'), ucwords($order_status)));
} else {
    // Free item
    $order_status = 'completed';
    if (!empty($order_info['coupon'])) {
        $order_status = 'paid';
    }
    ppcart_update_post_meta($order_post_id, 'payment_status', $order_status);
    ppcart_update_post_meta($order_post_id, 'status', $order_status);

    $submitSuccess = wp_update_post([ 'ID' =>  $order_post_id, 'post_status' => $order_status ]);
    ppcart_log_entry($order_post_id, __('Order complete.', 'publishpress-cart'));
}
//data updated

// Add user ID to order if logged in.
if ($order_info['user_account'] && $ppcart_current_user = get_user_by('id', $order_info['user_account'])) {
    $user_data = [];

    if (!$ppcart_current_user->user_firstname) {
        $user_data['first_name'] = $order_info['firstname'];
    }
    if (!$ppcart_current_user->user_lastname) {
        $user_data['last_name'] = $order_info['lastname'];
    }

    if (!empty($user_data)) {
        $user_data['ID'] = $order_info['user_account'];
        $user_id = wp_update_user($user_data);
    }
}

do_action('ppcart_order_store_pro_metadata', $order_post_id, $order_info);

$this->maybe_do_order_complete($order_post_id, $order_info, $order_status);
do_action('ppcart_fter_order_created', $order_post_id, $order_status, $order_info);

return $order_post_id;
