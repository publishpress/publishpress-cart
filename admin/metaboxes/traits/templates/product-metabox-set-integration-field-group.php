<?php

if (! defined('ABSPATH')) {
    exit;
}


$integration_fields = array_merge(
    require __DIR__ . '/integration-fields/fields-01.php',
    require __DIR__ . '/integration-fields/fields-02.php',
    require __DIR__ . '/integration-fields/fields-03.php',
);

$this->integrations = [
    [
        'type'  => 'html',
        'value' => '<div class="ppcart-integrations-intro"><p>'
            . esc_html__('Use integrations to add customers to mailing lists or run actions in connected services when an order or subscription event occurs. Connect your services in Cart settings, then choose a service and trigger below.', 'publishpress-cart')
            . '</p></div>',
    ],
    [
        'class'        => 'ppcart-repeater',
        'id'            => '_ppcart_integrations',
        'label-add'    => __('+ Add integration', 'publishpress-cart'),
        'label-edit'   => __('Edit integration', 'publishpress-cart'),
        'label-header' => __('Integration', 'publishpress-cart'),
        'label-remove' => __('Remove integration', 'publishpress-cart'),
        'title-field'  => 'name',
        'type'         => 'repeater',
        'value'        => '',
        'class_size'   => '',
        'empty_state'  => [
            'icon'  => 'dashicons-admin-plugins',
            'title' => __('No integrations yet', 'publishpress-cart'),
            'text'  => __('Add one to send customers to a mailing list or another service when they buy.', 'publishpress-cart'),
        ],
        'fields'       => $integration_fields,
    ],
    // After the list: it is only useful once an integration exists. Add-ons edit
    // the repeater as $fields[1], so new items go after it.
    [
        'type'  => 'html',
        'value' => '<div class="ppcart-integrations-refresh"><p><a href="#" class="button ppcart-renew-lists">'
            . esc_html__('Refresh mailing lists', 'publishpress-cart')
            . '</a> <span class="renew-status"></span></p><p class="description">'
            . esc_html__('Get new lists, groups and tags from your connected services.', 'publishpress-cart')
            . '</p></div>',
    ],
];

$this->integrations = apply_filters('ppcart_integration_fields', $this->integrations, $save);
if (! is_array($this->integrations)) {
    $this->integrations = [];
}

$new_fields = require __DIR__ . '/integration-fields/product-options.php';

array_splice($this->integrations, 1, 0, $new_fields);
