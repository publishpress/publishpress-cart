<?php

if (! defined('ABSPATH')) {
    exit;
}


$opts = [];
$opts['hierarchical']                           = in_array($tax_name, function_exists('ppcart_known_taxonomies') ? ppcart_known_taxonomies('product_tag') : [ 'ppcart_product_tag' ], true) ? false : true;
$opts['labels']['name']                         = esc_html($plural);
$opts['labels']['singular_name']                = esc_html($single);
/* translators: %s: plural label. */
$opts['labels']['search_items']                 = sprintf(esc_html__('Search %s', 'publishpress-cart'), esc_html($plural));
$opts['labels']['all_items']                    = esc_html($plural);
/* translators: %s: taxonomy singular label */
$opts['labels']['parent_item']                  = sprintf(esc_html__('Parent %s', 'publishpress-cart'), esc_html($single));
/* translators: %s: taxonomy singular label */
$opts['labels']['parent_item_colon']            = sprintf(esc_html__('Parent %s:', 'publishpress-cart'), esc_html($single));
/* translators: %s: singular label. */
$opts['labels']['edit_item']                    = sprintf(esc_html__('Edit %s', 'publishpress-cart'), esc_html($single));
/* translators: %s: taxonomy singular label */
$opts['labels']['update_item']                  = sprintf(esc_html__('Update %s', 'publishpress-cart'), esc_html($single));
/* translators: %s: singular label. */
$opts['labels']['add_new_item']                 = sprintf(esc_html__('Add New %s', 'publishpress-cart'), esc_html($single));
/* translators: %s: taxonomy singular label */
$opts['labels']['new_item_name']                = sprintf(esc_html__('New %s Name', 'publishpress-cart'), esc_html($single));
$opts['labels']['menu_name']                    = esc_html($plural);
$opts['show_ui']                                = true;
$opts['show_in_rest']                           = true;
$opts['show_admin_column']                      = true;
$opts['query_var']                              = true;
$is_leftover_taxonomy = false;
if (function_exists('ppcart_cpt_slug_migration_step1_mixed') && ppcart_cpt_slug_migration_step1_mixed() && function_exists('ppcart_cpt_slug_migration_maps')) {
    foreach (ppcart_cpt_slug_migration_maps()['taxonomies'] as $pair) {
        if ($tax_name === $pair['legacy']) {
            $is_leftover_taxonomy = true;
            break;
        }
    }
}
if ($is_leftover_taxonomy) {
    $opts['rewrite'] = false;
} else {
    $opts['rewrite']['slug'] = (function_exists('ppcart_taxonomy_rewrite_slug') && in_array($tax_name, function_exists('ppcart_known_taxonomies') ? ppcart_known_taxonomies('product_cat') : [ 'ppcart_product_cat' ], true))
        ? ppcart_taxonomy_rewrite_slug('product_cat')
        : ((function_exists('ppcart_taxonomy_rewrite_slug') && in_array($tax_name, function_exists('ppcart_known_taxonomies') ? ppcart_known_taxonomies('product_tag') : [ 'ppcart_product_tag' ], true))
            ? ppcart_taxonomy_rewrite_slug('product_tag')
            : strtolower($tax_name));
}
$opts = apply_filters('ppcart_taxonomy_options', $opts);

$product_object = function_exists('ppcart_query_post_types') ? ppcart_query_post_types('product') : [ 'ppcart_product' ];
register_taxonomy($tax_name, $product_object, $opts);
