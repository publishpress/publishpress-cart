<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.SelfOutsideClass -- Included from PPCart_Subscription_Orders::new_order().

$new_order = $this->first_order();
if ($new_order) {
    $new_order->id = 0;
    $new_order->pay_method = $this->pay_method;
    $new_order->gateway_mode = $this->gateway_mode;
    $new_order->invoice_total = $this->sub_amount;
    $new_order->invoice_subtotal = $this->sub_amount - $this->tax_amount;
    $new_order->amount = $this->sub_amount;
    $new_order->main_offer_amt = $this->sub_amount;
    $new_order->tax_amount = $this->tax_amount;
    $new_order->quantity = $this->quantity;

    $children = [
      'transaction_id'    => null,
      'status'            => self::$pending_str,
      'payment_status'    => self::$pending_str,
      'coupon'            => null,
      'coupon_id'         => null,
      'on_sale'           => 0,
      'accept_terms'      => null,
      'accept_privacy'    => null,
      'consent'           => null,
      'order_log'         => null,
      'refund_log'        => null,
      'order_bumps'       => null,
      'us_parent'         => null,
      'ds_parent'         => null,
      'order_parent'      => null,
      'order_type'        => null,
    ];
    foreach ($children as $k => $v) {
        $new_order->$k = $v;
    }

    $new_order->items = [
      [
        'product_id'     => $this->product_id,
        'price_id'       => $this->option_id,
        'product_name'   => $this->product_name,
        'price_name'     => $this->sub_item_name,
        'unit_price'     => $this->sub_amount,
        'item_type'      => 'main',
        'quantity'       => $this->quantity,
        'subtotal'       => $new_order->invoice_subtotal,
        'total_amount'   => $new_order->amount,
        'tax_amount'     => $this->tax_amount,
      ],
    ];
}
return $new_order;
