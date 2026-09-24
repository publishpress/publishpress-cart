<?php

if (! defined('ABSPATH')) {
    exit;
}

if (function_exists('ppcart_cpt_slug_migration_maybe_seed_state')) {
    ppcart_cpt_slug_migration_maybe_seed_state();
}

$labels = [
    'product'      => [ 'plural' => __('Products', 'publishpress-cart'), 'single' => __('Product', 'publishpress-cart'), 'supports' => [ 'title', 'editor', 'thumbnail' ], 'public' => true ],
    'order'        => [ 'plural' => __('Orders', 'publishpress-cart'), 'single' => __('Order', 'publishpress-cart'), 'supports' => false, 'public' => false ],
    'subscription' => [ 'plural' => __('Subscriptions', 'publishpress-cart'), 'single' => __('Subscription', 'publishpress-cart'), 'supports' => false, 'public' => false ],
];

$default_post_types_args = [];
if (function_exists('ppcart_cpt_slug_migration_registration_post_types')) {
    foreach (ppcart_cpt_slug_migration_registration_post_types() as $row) {
        $family = $row['family'];
        if (! isset($labels[ $family ])) {
            continue;
        }
        $default_post_types_args[] = [
            'cap_type'  => $row['cap_type'],
            'plural'    => $labels[ $family ]['plural'],
            'single'    => $labels[ $family ]['single'],
            'cpt_name'  => $row['cpt_name'],
            'supports'  => $labels[ $family ]['supports'],
            'public'    => $labels[ $family ]['public'],
            'show_ui'   => $row['show_ui'],
        ];
    }
} else {
    $default_post_types_args = [
        [
            'cap_type'  => 'ppcart_product',
            'plural'    => $labels['product']['plural'],
            'single'    => $labels['product']['single'],
            'cpt_name'  => 'ppcart_product',
            'supports'  => $labels['product']['supports'],
            'public'    => true,
            'show_ui'   => true,
        ],
        [
            'cap_type'  => 'ppcart_order',
            'plural'    => $labels['order']['plural'],
            'single'    => $labels['order']['single'],
            'cpt_name'  => 'ppcart_order',
            'supports'  => false,
            'public'    => false,
            'show_ui'   => true,
        ],
        [
            'cap_type'  => 'ppcart_subscription',
            'plural'    => $labels['subscription']['plural'],
            'single'    => $labels['subscription']['single'],
            'cpt_name'  => 'ppcart_subscription',
            'supports'  => false,
            'public'    => false,
            'show_ui'   => true,
        ],
    ];
}

$post_types_args = apply_filters('ppcart_post_types', $default_post_types_args);
if (! is_array($post_types_args) || $post_types_args === []) {
    $post_types_args = $default_post_types_args;
}

foreach ($post_types_args as $post_type_args) {
    if (
        ! is_array($post_type_args)
        || ! isset(
            $post_type_args['cap_type'],
            $post_type_args['plural'],
            $post_type_args['single'],
            $post_type_args['cpt_name']
        )
    ) {
        continue;
    }
    $show_ui = isset($post_type_args['show_ui']) ? (bool) $post_type_args['show_ui'] : true;
    $this::register_single_post_type(
        $post_type_args['cap_type'],
        $post_type_args['plural'],
        $post_type_args['single'],
        $post_type_args['cpt_name'],
        $post_type_args['supports'],
        $post_type_args['public'],
        $show_ui
    );
}

$cat_name = function_exists('ppcart_live_taxonomy') ? ppcart_live_taxonomy('product_cat') : 'ppcart_product_cat';
$tag_name = function_exists('ppcart_live_taxonomy') ? ppcart_live_taxonomy('product_tag') : 'ppcart_product_tag';

$default_taxonomies = [
    [
        'plural'    => __('Categories', 'publishpress-cart'),
        'single'    => __('Category', 'publishpress-cart'),
        'tax_name'  => $cat_name,
        'family'    => 'product_cat',
    ],
    [
        'plural'    => __('Tags', 'publishpress-cart'),
        'single'    => __('Tag', 'publishpress-cart'),
        'tax_name'  => $tag_name,
        'family'    => 'product_tag',
    ],
];

if (function_exists('ppcart_cpt_slug_migration_step1_mixed') && ppcart_cpt_slug_migration_step1_mixed()) {
    $maps = ppcart_cpt_slug_migration_maps();
    $default_taxonomies = [];
    foreach ($maps['taxonomies'] as $family => $pair) {
        $label = 'product_cat' === $family
            ? [ 'plural' => __('Categories', 'publishpress-cart'), 'single' => __('Category', 'publishpress-cart') ]
            : [ 'plural' => __('Tags', 'publishpress-cart'), 'single' => __('Tag', 'publishpress-cart') ];
        foreach ([ $pair['legacy'], $pair['canonical'] ] as $tax_name) {
            $default_taxonomies[] = [
                'plural'   => $label['plural'],
                'single'   => $label['single'],
                'tax_name' => $tax_name,
                'family'   => $family,
            ];
        }
    }
}

$taxonomies = apply_filters('ppcart_taxonomies', $default_taxonomies);
if (! is_array($taxonomies) || $taxonomies === []) {
    $taxonomies = $default_taxonomies;
}

foreach ($taxonomies as $taxonomy_args) {
    if (! is_array($taxonomy_args) || ! isset($taxonomy_args['plural'], $taxonomy_args['single'], $taxonomy_args['tax_name'])) {
        continue;
    }
    $this->register_single_post_type_taxonomy($taxonomy_args['plural'], $taxonomy_args['single'], $taxonomy_args['tax_name']);
}
