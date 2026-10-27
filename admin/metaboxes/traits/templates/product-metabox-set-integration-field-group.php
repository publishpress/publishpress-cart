<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context value for product title lookup.
$current_post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : null;
$name = get_the_title($current_post_id);
if (! $name) {
    $name = esc_html__('this product', 'publishpress-cart');
} else {
    $name = esc_html($name);
}
$name = '<strong>' . $name . '</strong>';
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
            . '</p><p><a href="#" class="button ppcart-renew-lists">'
            . esc_html__('Refresh mailing lists', 'publishpress-cart')
            . '</a> <span class="renew-status"></span></p><p class="description">'
            . esc_html__('Refresh the available mailing lists, groups, and tags from your connected services after creating or changing them in those services.', 'publishpress-cart')
            . '</p></div>',
    ],
    [
        'class'        => 'ppcart-repeater',
        'id'            => '_ppcart_integrations',
        'label-add'    => __('+ Add Integration', 'publishpress-cart'),
        'label-edit'   => __('Edit Integration', 'publishpress-cart'),
        'label-header' => __('Integration', 'publishpress-cart'),
        'label-remove' => __('Remove Integration', 'publishpress-cart'),
        'title-field'  => 'name',
        'type'         => 'repeater',
        'value'        => '',
        'class_size'   => '',
        'fields'       => $integration_fields,
    ],
];

$this->integrations = apply_filters('ppcart_integration_fields', $this->integrations, $save);
if (! is_array($this->integrations)) {
    $this->integrations = [];
}

$new_fields = require __DIR__ . '/integration-fields/product-options.php';

array_splice($this->integrations, 1, 0, $new_fields);
