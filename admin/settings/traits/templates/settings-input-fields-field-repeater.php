<?php

if (! defined('ABSPATH')) {
    exit;
}


$defaults['class']        = 'ppcart-repeater';
$defaults['fields']       = [];
$defaults['id']           = '';
$defaults['label-add']    = 'Add Item';
$defaults['label-edit']   = 'Edit Item';
$defaults['label-header'] = 'Item Name';
$defaults['label-remove'] = 'Remove Item';
$defaults['title-field']  = '';
apply_filters($this->plugin_name . '-field-repeater-options-defaults', $defaults);
$setatts = wp_parse_args($args, $defaults);

$count    = 1;
$repeater = get_option($setatts['id']);
if (! empty($repeater)) {
    $repeater = maybe_unserialize($repeater);
}

if (! empty($repeater)) {
    $fields = [];
    foreach ($setatts['fields'] as $field) :
        foreach ($field as $atts) :
            $fields[] = $atts['key'];
        endforeach;
    endforeach;
    $count = count($repeater[$fields[0]]);
    for ($i = 0; $i < $count; $i++) {
        $inner_val = [];
        foreach ($fields as $field) :
            $inner_val[$field] = $repeater[$field][$i] ?? '';
        endforeach;
        $new_val[$i] = $inner_val;
    }
    $repeater = $new_val;
    $repeater = array_map([$this, 'remove_repeater_blank'], $repeater);
    $repeater = array_filter($repeater);
    $count    = count($repeater);
}

$setting_field = true;
include((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR . 'admin/partials/' : dirname(__DIR__, 3) . '/partials/') . 'ppcart-admin-field-repeater.php');
