<?php

if (! defined('ABSPATH')) {
    exit;
}


$posted = $this->get_posted_data();

$product_name   = $this->product_name;
$product_id     = $this->product_id;
$price_id       = $this->option_id;
$price_name     = $this->plan->name;
$quantity       = $this->quantity;

if (($this->plan->type == 'pwyw' || ($this->plan->type == 'recurring' && isset($this->plan->recurring_pwyw) && $this->plan->recurring_pwyw == '1' && isset($this->plan->name_your_own_price_text_recurring))) && isset($posted['pwyw_amount'][$this->option_id]) && $posted['pwyw_amount'][$this->option_id] >= $this->plan->initial_payment) {
    $this->plan->initial_payment = (float) $posted['pwyw_amount'][$this->option_id];
}

$unit_price     = $this->plan->initial_payment;
$total          = $this->plan->initial_payment;

if ($total > 0 && $this->quantity > 1) {
    $total *= $this->quantity;
}

$args = [
    'product_id'     => $product_id,
    'price_id'       => $price_id,
    'product_name'   => $product_name,
    'price_name'     => $price_name,
    'unit_price'     => $unit_price,
    'item_type'      => 'main',
    'quantity'       => $quantity,
    'subtotal'       => $total,
    'total_amount'   => $total,
];

if (!empty($this->plan->trial_days)) {
    $args['trial_days'] = $this->plan->trial_days;
}

if (!empty($this->plan->fee)) {
    $args['sign_up_fee'] = $this->plan->fee;
}

if ($this->purchase_note) {
    $args['purchase_note'] = $this->purchase_note;
}

$this->main_offer_amt += $total;
$this->add_item($args);
