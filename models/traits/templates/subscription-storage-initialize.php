<?php

if (! defined('ABSPATH')) {
    exit;
}


$this->defaults = $defaults;
$this->attrs = array_merge(array_keys($defaults), array_keys($order_defaults));
$this->order_attrs = array_keys($order_defaults);

// set all defaults initially
foreach ($defaults as $key => $value) {
    $this->$key = $value;
}
foreach ($order_defaults as $key => $value) {
    $this->$key = $value;
}

if (is_numeric($obj) && $obj > 0) {
    if (ppcart_is_subscription_post_type(get_post_type($obj))) {
        $meta = get_post_custom($obj);
        foreach ($defaults as $key => $value) {
            if ($key == 'id') {
                $this->$key = $obj;
            } elseif (null !== ($shifted = ppcart_shift_custom_meta(is_array($meta) ? $meta : [], $key))) {
                $this->$key = ppcart_safe_meta_unserialize($shifted);
            }
        }
        foreach ($order_defaults as $key => $value) {
            if ($key == 'id') {
                $this->$key = $obj;
            } elseif (null !== ($shifted = ppcart_shift_custom_meta(is_array($meta) ? $meta : [], $key))) {
                $this->$key = ppcart_safe_meta_unserialize($shifted);
            }
        }
    } else {
        $this->id = false;
        return;
    }
} elseif (!is_null($obj)) {
    $this->id = false;
}
