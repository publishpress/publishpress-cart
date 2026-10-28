<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Sorts the product settings tabs into labelled nav groups.
 *
 * Expects $product_setting_tabs (tab id => label). A tab with no group joins
 * the group of the tab before it, so tabs added by Pro or add-ons stay next to
 * the tab they were inserted after.
 */

$default_groups = [
    'sell' => [
        'label' => __('Sell', 'publishpress-cart'),
        'tabs'  => [ 'general', 'pricing', 'payments', 'ppcart_pro_payment_methods', 'access', 'fields', 'files', 'shipping' ],
    ],
    'after' => [
        'label' => __('After purchase', 'publishpress-cart'),
        'tabs'  => [ 'confirmation', 'notifications', 'integrations' ],
    ],
    'grow' => [
        'label' => __('Grow sales', 'publishpress-cart'),
        'tabs'  => [
            'coupons',
            'orderbump',
            'upsellPath',
            'affiliate',
            'ppcart_pro_coupons',
            'ppcart_pro_order_bumps',
            'ppcart_pro_upsell_path',
            'ppcart_pro_affiliates',
        ],
    ],
];

$groups = apply_filters('ppcart_product_setting_tab_groups', $default_groups, $product_setting_tabs);
if (! is_array($groups) || [] === $groups) {
    $groups = $default_groups;
}

$group_of_tab = [];
foreach ($groups as $group_id => $group) {
    foreach ((array) ($group['tabs'] ?? []) as $tab_id) {
        $group_of_tab[ (string) $tab_id ] = (string) $group_id;
    }
}

$first_group = (string) array_key_first($groups);
$grouped     = [];
foreach ($groups as $group_id => $group) {
    $grouped[ (string) $group_id ] = [
        'label' => (string) ($group['label'] ?? ''),
        'tabs'  => [],
    ];
}

$previous_group = $first_group;
foreach ($product_setting_tabs as $tab_id => $label) {
    $group_id = $group_of_tab[ (string) $tab_id ] ?? $previous_group;
    $grouped[ $group_id ]['tabs'][ $tab_id ] = $label;
    $previous_group = $group_id;
}

return array_filter(
    $grouped,
    static function ($group) {
        return [] !== $group['tabs'];
    }
);
