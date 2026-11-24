<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product, $ppcart_currency;
$posted = $this->get_posted_data();

$this->items = [];
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

$this->finalize_amounts($ppcart_product);

// end order setup
do_action('ppcart_after_load_from_post', $this, $posted);
