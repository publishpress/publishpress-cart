<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product, $ppcart_currency;
$posted = $this->get_posted_data();

$this->product_id = isset($posted['ppcart_product_id']) ? intval($posted['ppcart_product_id']) : 0;

if (! is_object($ppcart_product) || ! isset($ppcart_product->ID)) {
    $ppcart_product = ppcart_setup_product($this->product_id);
}

$this->apply_customer_fields($posted);
$this->on_sale           = (isset($posted['on-sale']) && ppcart_is_prod_on_sale()) ? 1 : 0;
$this->option_id         = sanitize_text_field($posted['ppcart_product_option'] ?? '');
$this->plan              = apply_filters('ppcart_plan_at_checkout', ppcart_plan($this->option_id, $this->on_sale), $this->product_id);
if (is_object($this->plan)) {
    $this->plan_id       = $this->plan->stripe_id;
    $this->item_name     = $this->plan->name;
}
$this->product_name      = ppcart_get_public_product_name($this->product_id);

$this->purchase_note = apply_filters('ppcart_order_setup_purchase_note', null, $ppcart_product, $this);

$this->quantity          = intval($posted['ppcart_qty'] ?? 1);
$this->main_offer_amt    = 0;

if (isset($posted['ppcart_page_id'])) {
    $this->page_id = intval($posted['ppcart_page_id']);
    $this->page_url = sanitize_text_field($posted['ppcart_page_url'] ?? get_permalink($posted['ppcart_page_id']));
}

if (is_object($ppcart_product) && ! empty($ppcart_product->show_optin_cb)) {
    $this->consent = (isset($posted['ppcart_consent'])) ? 'Yes' : null;
}

$this->apply_address_fields($posted);

$this->load_coupon_from_post();
