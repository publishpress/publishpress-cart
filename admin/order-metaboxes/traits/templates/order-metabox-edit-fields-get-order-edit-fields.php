<?php

if (! defined('ABSPATH')) {
    exit;
}


$ordered_ids = [
    '_ppcart_firstname',
    '_ppcart_lastname',
    '_ppcart_email',
    '_ppcart_user_account',
    '_ppcart_phone',
    '_ppcart_company',
    '_ppcart_vat_number',
    '_ppcart_address1',
    '_ppcart_address2',
    '_ppcart_city',
    '_ppcart_state',
    '_ppcart_zip',
    '_ppcart_country',
    '_ppcart_status',
];

if (! $this->is_existing_order_edit($post)) {
    $ordered_ids[] = '_ppcart_product_id';
    $ordered_ids[] = '_ppcart_item_name';
}

$fields_by_id = [];
foreach ($this->general as $field) {
    if (! empty($field['id'])) {
        $fields_by_id[ $field['id'] ] = $field;
    }
}

if ($this->is_existing_order_edit($post)) {
    unset($fields_by_id['_ppcart_product_id'], $fields_by_id['_ppcart_item_name']);
}

$ordered = [];
foreach ($ordered_ids as $field_id) {
    if (isset($fields_by_id[ $field_id ])) {
        $ordered[] = $fields_by_id[ $field_id ];
        unset($fields_by_id[ $field_id ]);
    }
}

return array_merge($ordered, array_values($fields_by_id));
