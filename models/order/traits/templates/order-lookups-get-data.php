<?php

if (! defined('ABSPATH')) {
    exit;
}


if ($this->id === false) {
    return false;
}

$data = [];
$data['ID'] = $this->id;
$data['date'] = get_the_date('', $this->id);

foreach ($this->attrs as $key) {
    $data[$key] = $this->$key;
}

$data['status_label'] = $this->get_status();

$data['invoice_link'] = $this->invoice_link();
$data['invoice_link_html'] = $this->invoice_link_html();

if ($data['user_account']) {
    $data['user'] = get_user_by('id', $data['user_account']);
}

if (!isset($data['invoice_subtotal'])) {
    $data['invoice_subtotal'] = $data['main_offer_amt'];

    if (!empty($data['order_bumps']) && is_array($data['order_bumps'])) {
        foreach ($data['order_bumps'] as $order_bump) {
            $data['invoice_subtotal'] += $order_bump['amount'];
        }
    }
    $data['invoice_total'] = $data['invoice_subtotal'];

    if ($data['tax_amount'] > 0) {
        if ($data['tax_data'] && (!isset($data['tax_data']->type) || $data['tax_data']->type != 'inclusive')) {
            $data['invoice_total'] += $data['tax_amount'];
        }
    }
}

if ($data['tax_data']) {
    $data['tax_rate'] .= '%';
    if ($data['tax_amount'] > 0 && $data['tax_data'] && $data['tax_data']->type == 'inclusive') {
        $data['tax_rate'] .= ' ' . __('incl.', 'publishpress-cart');
    }
}

if ($data['coupon']) {
    if (!isset($data['coupon_id']) || !$data['coupon_id']) {
        $data['coupon_id'] = $data['coupon'];
    }
}

if (is_countable($data['custom_prices'])) {
    $fields = $data['custom_fields'];
    foreach ($data['custom_prices'] as $k => $v) {
        $custom[$k]['label'] = $fields[$k]['label'];
        $custom[$k]['qty'] = $fields[$k]['value'];
        $custom[$k]['price'] = $v;
    }
    $data['custom_prices'] = $custom;
}

$data['renewal_order'] = ppcart_get_post_meta($this->id, 'renewal_order', true);

if ($data['tax_amount']) {
    $data['invoice_tax_amount'] = $data['tax_amount'];
}

if ($children = ppcart_get_post_meta($this->id, 'order_child')) {
    $data['order_child'] = [];
    foreach ($children as $child) {
        if (is_numeric($child)) {
            $child = ['id' => $child];
        }

        $child = new PPCart_Order($child['id']);
        $data['order_child'][] = $child->get_data();
        $data['invoice_subtotal'] += $child->main_offer_amt;
        $data['invoice_total'] += $child->main_offer_amt;
        if ($child->tax_data) {
            $data['invoice_tax_amount'] ??= 0;
            $data['invoice_tax_amount'] += $child->tax_amount;
            if (!isset($child->tax_data->type) || $child->tax_data->type != 'inclusive') {
                $data['invoice_total'] += $child->tax_amount;
            }
        }
    }
}

$key = 'order_summary_items';
$data['order_summary_items'] = $this->$key ?? [];

return $data;
