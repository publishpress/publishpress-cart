<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_product, $ppcart_currency;
$posted = $this->get_posted_data();

$this->product_id = isset($posted['ppcart_product_id']) ? intval($posted['ppcart_product_id']) : 0;
$this->email = (isset($posted['email'])) ? strtolower(sanitize_email($posted['email'])) : null;

if (! is_object($ppcart_product) || ! isset($ppcart_product->ID)) {
    $ppcart_product = ppcart_setup_product($this->product_id);
}
$curr_user_id = get_current_user_id();

// If user is not logged in, try to get user it by email
if (!$curr_user_id) {
    $user = get_user_by('email', $this->email);
    if ($user) {
        $curr_user_id = $user->ID;
    }
}

$this->first_name        = sanitize_text_field($posted['first_name'] ?? '');
$this->last_name         = sanitize_text_field($posted['last_name'] ?? '');
$this->firstname         = $this->first_name; // backwards compatibility
$this->lastname          = $this->last_name; // backwards compatibility
$this->customer_name     = $this->first_name . ' ' . $this->last_name;
$this->customer_id       = sanitize_text_field($posted['customerId'] ?? '');
$this->phone             = sanitize_text_field($posted['phone'] ?? '');
$this->company           = sanitize_text_field($posted['company'] ?? '');
$this->pay_method        = sanitize_text_field($posted['pay-method'] ?? '');
$this->currency          = $ppcart_currency;
$this->accept_terms      = (isset($posted['ppcart_accept_terms'])) ? sanitize_text_field(__('accepted', "publishpress-cart")) : null;
$this->accept_privacy    = (isset($posted['ppcart_accept_privacy'])) ? sanitize_text_field(__('accepted', "publishpress-cart")) : null;
$this->ip_address        = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : ''; // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders,WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__ -- IP is stored for fraud/audit metadata only.
$this->user_account      = $curr_user_id;
$this->on_sale           = (isset($posted['on-sale']) && ppcart_is_prod_on_sale()) ? 1 : 0;
$this->option_id         = sanitize_text_field($posted['ppcart_product_option'] ?? '');
$this->plan              = apply_filters('ppcart_plan_at_checkout', ppcart_plan($this->option_id, $this->on_sale), $this->product_id);
$this->plan_id           = $this->plan->stripe_id;
$this->item_name         = $this->plan->name;
$this->product_name      = ppcart_get_public_product_name($this->product_id);
$this->vat_number        = sanitize_text_field($posted['vat-number'] ?? "");

$this->purchase_note = apply_filters('ppcart_order_setup_purchase_note', null, $ppcart_product, $this);

$this->quantity          = intval($posted['ppcart_qty'] ?? 1);
$this->main_offer_amt    = 0;

if (isset($posted['ppcart_page_id'])) {
    $this->page_id = intval($posted['ppcart_page_id']);
    $this->page_url = sanitize_text_field($posted['ppcart_page_url'] ?? get_permalink($posted['ppcart_page_id']));
}

if ($ppcart_product->show_optin_cb) {
    $this->consent = (isset($posted['ppcart_consent'])) ? 'Yes' : null;
}

$address_info = ['address1', 'address2', 'city', 'state', 'zip', 'country'];
foreach ($address_info as $info) {
    if (isset($posted[$info])) {
        $this->$info = sanitize_text_field($posted[$info]);
    }
}

$this->load_coupon_from_post();
