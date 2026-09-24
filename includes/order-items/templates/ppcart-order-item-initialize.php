<?php

if (! defined('ABSPATH')) {
    exit;
}


$meta = apply_filters('ppcart_order_item_meta', $meta);
$this->defaults = $defaults;
$this->attrs = array_merge(array_keys($defaults), array_keys($meta));
$this->cols = array_keys($defaults);
$this->meta = array_keys($meta);
$this->id = 0;
if (is_numeric($obj) && $obj > 0) {
    if ($res = $this->get_item($obj)) {
        $this->id = $obj;
    } else {
        $this->id = false;
        return;
    }

    foreach ($defaults as $key => $val) {
        $this->$key = $res->$key ?? $val;
    }

    foreach ($meta as $key => $val) {
        if (!$this->$key = $this->get_meta($key)) {
            $this->$key = $val;
        }
    }
} else {
    foreach ([$defaults, $meta] as $set) {
        foreach ($set as $key => $value) {
            $this->$key = $value;
        }
    }
}
