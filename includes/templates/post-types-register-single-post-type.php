<?php

if (! defined('ABSPATH')) {
    exit;
}


$opts = [];
$opts['can_export']                             = true;
$opts['capability_type']                        = $cap_type;
$opts['description']                            = '';
$opts['exclude_from_search']                    = true;
$opts['has_archive']                            = false;
$opts['hierarchical']                           = false;
$opts['map_meta_cap']                           = true;
$opts['menu_icon']                              = 'dashicons-products';
$opts['menu_position']                          = 25;
$opts['public']                                 = $public;
$opts['publicly_querable']                      = true;
$opts['query_var']                              = true;
$opts['register_meta_box_cb']                   = '';
$opts['show_in_admin_bar']                      = true;
if ($cpt_name != 'ppcart_offer') {
    $opts['show_in_menu']                       = PPCart_Admin_Screens::menu_slug();
}
$opts['show_in_nav_menu']                       = false;
$opts['show_ui']                                = isset($show_ui) ? (bool) $show_ui : true;
$opts['supports']                               = $supports;
$opts['taxonomies']                             = [];
$opts['capabilities']['delete_others_posts']    = "delete_others_{$cap_type}s";
$opts['capabilities']['delete_post']            = "delete_{$cap_type}";
$opts['capabilities']['delete_posts']           = "delete_{$cap_type}s";
$opts['capabilities']['delete_private_posts']   = "delete_private_{$cap_type}s";
$opts['capabilities']['delete_published_posts'] = "delete_published_{$cap_type}s";
$opts['capabilities']['edit_others_posts']      = "edit_others_{$cap_type}s";
$opts['capabilities']['edit_post']              = "edit_{$cap_type}";
$opts['capabilities']['edit_posts']             = "edit_{$cap_type}s";
$opts['capabilities']['edit_private_posts']     = "edit_private_{$cap_type}s";
$opts['capabilities']['edit_published_posts']   = "edit_published_{$cap_type}s";
$opts['capabilities']['publish_posts']          = "publish_{$cap_type}s";
$opts['capabilities']['read_post']              = "read_{$cap_type}";
$opts['capabilities']['read_private_posts']     = "read_private_{$cap_type}s";
/* translators: %s: singular label. */
$opts['labels']['add_new']                      = sprintf(esc_html__('Add New %s', 'publishpress-cart'), esc_html($single));
/* translators: %s: singular label. */
$opts['labels']['add_new_item']                 = sprintf(esc_html__('Add New %s', 'publishpress-cart'), esc_html($single));
$opts['labels']['all_items']                    = esc_html($plural);
/* translators: %s: singular label. */
$opts['labels']['edit_item']                    = sprintf(esc_html__('Edit %s', 'publishpress-cart'), esc_html($single));
$opts['labels']['menu_name']                    = esc_html($plural);
$opts['labels']['name']                         = esc_html($plural);
$opts['labels']['name_admin_bar']               = esc_html($single);
/* translators: %s: singular label. */
$opts['labels']['new_item']                     = sprintf(esc_html__('New %s', 'publishpress-cart'), esc_html($single));
/* translators: %s: post type plural label */
$opts['labels']['not_found']                    = sprintf(esc_html__('No %s Found', 'publishpress-cart'), esc_html($plural));
/* translators: %s: post type plural label */
$opts['labels']['not_found_in_trash']           = sprintf(esc_html__('No %s Found in Trash', 'publishpress-cart'), esc_html($plural));
/* translators: %s: post type plural label */
$opts['labels']['parent_item_colon']            = sprintf(esc_html__('Parent %s :', 'publishpress-cart'), esc_html($plural));
/* translators: %s: plural label. */
$opts['labels']['search_items']                 = sprintf(esc_html__('Search %s', 'publishpress-cart'), esc_html($plural));
$opts['labels']['singular_name']                = esc_html($single);
/* translators: %s: singular label. */
$opts['labels']['view_item']                    = sprintf(esc_html__('View %s', 'publishpress-cart'), esc_html($single));
$opts['rewrite']['feeds']                       = false;
$opts['rewrite']['pages']                       = true;
$opts['rewrite']['slug']                        = strtolower($plural);
$opts['rewrite']['with_front']                  = false;
$opts = apply_filters('ppcart_cpt_options', $opts, $cpt_name);

if (function_exists('ppcart_is_subscription_post_type') && ppcart_is_subscription_post_type($cpt_name)) {
    $opts['capabilities']['create_posts'] = false;
}

if (function_exists('ppcart_is_product_post_type') && ppcart_is_product_post_type($cpt_name)) {
    $opts['hierarchical'] = true;
    $opts['show_in_rest'] = true;
}

register_post_type(strtolower($cpt_name), $opts);
