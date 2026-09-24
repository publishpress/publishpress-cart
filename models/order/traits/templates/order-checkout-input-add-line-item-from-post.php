<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product;
$posted = $this->get_posted_data();

$custom_fields = PPCart_Public_Order_Controller::get_custom_fields_from_post($ppcart_product);
if (!empty($custom_fields)) {
    $this->custom_fields = $custom_fields;
}

// Store all custom field data for possible user creation in webhook
$custom_fields_post = PPCart_Public_Order_Controller::get_custom_fields_post_data($ppcart_product->ID);
if ($custom_fields_post) {
    $this->custom_fields_post_data = $custom_fields_post;
}

// process pricing fields
foreach ($ppcart_product->custom_fields as $field) {
    $posted_custom_fields = $posted['ppcart_custom_fields'] ?? [];
    if ($field['field_type'] == 'quantity' && isset($field['qty_price']) && isset($posted_custom_fields[$field['field_id']]) && !empty($posted_custom_fields[$field['field_id']])) {
        $qty = intval($posted_custom_fields[$field['field_id']]);
        $qty_price = $field['qty_price'] * $qty;
        $this->custom_prices[$field['field_id']] = $qty_price;

        $this->add_item([
            'product_id'     => $this->product_id,
            'price_id'       => $field['field_id'],
            'item_type'      => 'line item',
            'product_name'   => $field['field_label'],
            'price_name'     => $field['field_name'],
            'unit_price'     => $field['qty_price'],
            'quantity'       => $qty,
            'subtotal'       => $qty_price,
            'total_amount'   => $qty_price,
        ]);
    }
}
