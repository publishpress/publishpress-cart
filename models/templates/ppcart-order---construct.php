<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Order::__construct().

$this->initialize(
    [
    'id'                => 0,
    'date'              => '',
    'transaction_id'    => null,
    'status'            => self::$pending_str,
    'payment_status'    => self::$pending_str,
    'custom_fields_post_data' => null,
    'custom_fields'     => null,
    'custom_prices'     => null,
    'product_id'        => null,
    'product_name'      => null,
    'page_id'           => null,
    'page_url'          => null,
    'item_name'         => null,
    'plan'              => null,
    'plan_id'           => null,
    'option_id'         => null,
    'invoice_total'     => 0.00,
    'invoice_subtotal'  => 0.00,
    'amount'            => 0.00,
    'main_offer_amt'    => 0.00,
    'pre_tax_amount'    => 0.00,
    'tax_amount'        => 0.00,
    'auto_login'        => null,
    'coupon'            => null,
    'coupon_id'         => null,
    'on_sale'           => 0,
    'accept_terms'      => null,
    'accept_privacy'    => null,
    'consent'           => null,
    'purchase_note'     => null,
    'order_log'         => null,
    'order_bumps'       => null,
    'us_parent'         => null,
    'ds_parent'         => null,
    'us_offer'          => null,
    'order_parent'      => null,
    'refund_log'        => null,
    'order_type'        => null,
    'subscription_id'   => 0,
    'quantity'          => 1,
    'shipping_amount'   => 0.00,
    'shipping_tax'      => 0.00,
      ],
    // shared child order keys
    [
      'firstname'         => null, // backwards compatibility
      'lastname'          => null, // backwards compatibility
      'first_name'        => null,
      'last_name'         => null,
      'customer_name'     => null,
      'customer_id'       => null,
      'company'           => null,
      'email'             => null,
      'phone'             => null,
      'country'           => null,
      'address1'          => null,
      'address2'          => null,
      'city'              => null,
      'state'             => null,
      'zip'               => null,
      'ip_address'        => null,
      'user_account'      => 0,
      'pay_method'        => 'cod',
      'gateway_mode'      => null,
      'currency'          => 'USD',
      'tax_rate'          => 0.00,
      'tax_desc'          => '',
      'tax_data'          => '',
      'tax_type'          => 'tax',
      'vat_number'        => '',
      'stripe_tax_id'     => '',
      'invoice_number'    => null,
    ],
    $obj
);
