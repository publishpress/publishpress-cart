<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product, $ppcart_currency;
$posted = $this->get_posted_data();

$this->setup_atts_from_post();
do_action('ppcart_after_setup_atts_from_post', $this, $posted);

$this->add_main_item_from_post();

if ($this->coupon) {
    do_action('ppcart_order_before_apply_plan_coupon', $this, $posted);
    $args = $this->apply_plan_coupon_to_items();
    do_action('ppcart_order_after_apply_plan_coupon', $this, $posted);
}

if (isset($ppcart_product->custom_fields)) {
    $this->add_line_item_from_post();
}

if (isset($posted['ppcart-orderbump'])) {
    $this->order_bumps = [];
    $this->add_bump_items_from_post($posted['ppcart-orderbump']);
}

if ($this->coupon) {
    do_action('ppcart_order_before_apply_cart_coupon', $this, $posted);
    $args = $this->apply_cart_coupon_to_items();
    do_action('ppcart_order_after_apply_cart_coupon', $this, $posted);
}

do_action('ppcart_order_pre_calculate_tax', $this);

$this->calculate_pre_tax_amount_from_items();
$this->calculate_tax_amount_from_items();
$this->calculate_total_amount_from_items();

$this->invoice_subtotal = $this->pre_tax_amount;

$this->amount = apply_filters('ppcart_charge_amount', $this->amount, $ppcart_product);
$this->non_formatted_amount = $this->amount;
$this->amount = $this->amount;
$this->invoice_total = $this->amount;

// setup order_summary_items for checkout form
$this->calculate_final_amounts();

// end order setup
do_action('ppcart_after_load_from_post', $this, $posted);
