<?php

if (! defined('ABSPATH')) {
    exit;
}


$customer_defaults = apply_filters('ppcart_customer_defaults', $customer_defaults);
if (! is_array($defaults)) {
    $defaults = [];
}
if (! is_array($customer_defaults)) {
    $customer_defaults = [];
}
$this->defaults = $defaults;
$this->attrs = array_merge(array_keys($defaults), array_keys($customer_defaults));
$this->customer_attrs = array_keys($customer_defaults);

$keysets = [$defaults,$customer_defaults];

foreach ($keysets as $set) {
    if (is_numeric($obj) && $obj > 0) {
        if (! ppcart_is_order_post_type(get_post_type($obj))) {
            $this->id = false;
            return;
        }
        $meta = get_post_custom($obj);
        foreach ($set as $key => $value) {
            if ($key == 'id') {
                $this->$key = $obj;
            } elseif ($key == 'date') {
                $this->$key = get_the_date('', $this->id);
            } elseif (null !== ($shifted = ppcart_shift_custom_meta(is_array($meta) ? $meta : [], $key))) {
                if ($key != 'order_child') {
                    $this->$key = ppcart_safe_meta_unserialize($shifted);
                }
            } else {
                $this->$key = $value;
            }
        }
    } else {
        foreach ($set as $key => $value) {
            $this->$key = $value;
        }
    }
}

// A double-serialized _ppcart_tax_data rehydrates as a string; normalize it back to an
// object so every consumer (receipt, admin order screen, invoice, email) can read it.
if (is_string($this->tax_data) && '' !== $this->tax_data) {
    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Canonical _ppcart_tax_data can be double-serialized as an object; this branch only rehydrates existing order meta for display.
    $maybe_tax_data = @unserialize($this->tax_data);
    if (is_object($maybe_tax_data)) {
        $this->tax_data = $maybe_tax_data;
    }
}
