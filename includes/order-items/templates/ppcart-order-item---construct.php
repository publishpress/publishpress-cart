<?php

if (! defined('ABSPATH')) {
    exit;
}


$this->initialize(
    [
        'order_id'          => 0,
        'product_id'        => 0,
        'price_id'          => '',
        'item_type'         => '',
        'product_name'      => '',
        'price_name'        => '',
        'total_amount'      => 0.00,
        'tax_amount'        => 0.00,
    ],
    // common meta
    [
        'unit_price'        => 0.00,
        'quantity'          => 0,
        'subtotal'          => 0.00,
        'discount_amount'   => 0.00,
        'shipping_amount'   => 0.00,
        'sign_up_fee'       => 0.00,
        'trial_days'        => 0,
        'tax_rate'          => '',
        'tax_desc'          => '',
        'purchase_note'     => '',
    ],
    $obj
);
