<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context value for product title lookup.
$current_post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : null;
if (!$name = get_the_title($current_post_id)) {
    $name = __('this product', 'publishpress-cart');
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
        'value' => '<a href="#" class="button ppcart-renew-lists" style="margin-left: 10px;">' . __('Renew mailing lists', 'publishpress-cart') . '</a> <span class="renew-status"></span>
                        <div id="rid_ppcart_break" class="ppcart-field ppcart-row"><p style="display: block; margin: 5px 0 0;padding: 0 0 5px;border-bottom: 1px solid #d5d5d5;flex-basis: 100%;"></p></div>
                        ',
    ],
    [
        'class'        => 'ppcart-repeater',
        'id'            => '_ppcart_integrations',
        'label-add'    => __('+ Add New', 'publishpress-cart'),
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
