<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Renders one product settings tab, split into titled sections.
 *
 * Expects $tab_id and $fields. Sections are a render-time map keyed by field
 * ID, so the field arrays (which other code splices by position) keep their
 * order. A field missing from the map joins the section of the field before it.
 * Tabs without a map render as before.
 */

$default_sections = [
    'general' => [
        'details' => [
            'title'  => __('Product details', 'publishpress-cart'),
            'fields' => [ '_ppcart_product_name' ],
        ],
        'header' => [
            'title'       => __('Page header', 'publishpress-cart'),
            'description' => __('Change how the top of the product page looks.', 'publishpress-cart'),
            'fields'      => [ '_ppcart_hide_title', '_ppcart_header_color', '_ppcart_header_image' ],
        ],
    ],
    // Payment plans come first: the price is the first thing to set on a product.
    'pricing' => [
        'plans' => [
            'title'       => __('Payment plans', 'publishpress-cart'),
            'description' => __('Customers choose one of these plans at checkout.', 'publishpress-cart'),
            'fields'      => [ '_ppcart_pay_options', '_ppcart_hide_plans', '_ppcart_plan_heading' ],
        ],
        'sale' => [
            'title'       => __('Sale', 'publishpress-cart'),
            'description' => __('Set the sale price in each plan above.', 'publishpress-cart'),
            'fields'      => [ '_ppcart_on_sale', '_ppcart_schedule_sale', '_ppcart_sale_start', '_ppcart_sale_end', '_ppcart_show_full_price' ],
        ],
    ],
    'access' => [
        'stock' => [
            'title'  => __('Stock', 'publishpress-cart'),
            'fields' => [ '_ppcart_manage_stock', '_ppcart_limit' ],
        ],
        'customer' => [
            'title'  => __('Limit per customer', 'publishpress-cart'),
            'fields' => [ '_ppcart_customer_purchase_limit', '_ppcart_customer_limit', '_ppcart_customer_limit_message' ],
        ],
        'window' => [
            'title'       => __('Checkout window', 'publishpress-cart'),
            'description' => __('Open and close the checkout form on set dates.', 'publishpress-cart'),
            'fields'      => [ '_ppcart_checkout_starts', '_ppcart_checkout_ends', '_ppcart_checkout_ended_action', '_ppcart_checkout_ended_redirect', '_ppcart_checkout_ended_message' ],
        ],
    ],
    'fields' => [
        'form' => [
            'title'  => __('Form fields', 'publishpress-cart'),
            'fields' => [ '_ppcart_show_address_fields' ],
        ],
        'button' => [
            'title'  => __('Button and checkboxes', 'publishpress-cart'),
            'fields' => [ '_ppcart_button_color', '_ppcart_button_text', '_ppcart_terms_setting', '_ppcart_privacy_setting' ],
        ],
    ],
    'confirmation' => [
        // No title: the tab name already says what these fields do. The card lines them up with the other tabs.
        'confirmation' => [
            'fields' => [ '_ppcart_confirmation', '_ppcart_confirmation_message', '_ppcart_confirmation_page', '_ppcart_redirect' ],
        ],
    ],
    'notifications' => [
        'defaults' => [
            'title'       => __('Default emails', 'publishpress-cart'),
            'description' => __('Turn off an email for this product only.', 'publishpress-cart'),
            'fields'      => [ '_ppcart_disable_pending_email', '_ppcart_disable_welcome_email', '_ppcart_disable_purchase_email' ],
        ],
        'custom' => [
            'title'       => __('Custom notifications', 'publishpress-cart'),
            'description' => __('Send more emails when someone buys this product.', 'publishpress-cart'),
            'fields'      => [ '_ppcart_notification_heading', '_ppcart_notifications' ],
        ],
    ],
];

$all_sections = apply_filters('ppcart_product_tab_sections', $default_sections);
$sections     = (is_array($all_sections) && ! empty($all_sections[ $tab_id ]) && is_array($all_sections[ $tab_id ])) ? $all_sections[ $tab_id ] : [];

if ([] === $sections) {
    $this->metabox_fields($fields);
    return;
}

$field_ids = [];
foreach ($fields as $key => $field) {
    $field_ids[ $key ] = (is_array($field) && isset($field['id'])) ? (string) $field['id'] : '';
}

foreach (ppcart_admin_group_fields_by_section($field_ids, $sections) as $section_id => $keys) {
    $section       = $sections[ $section_id ];
    $section_items = [];
    foreach ($keys as $key) {
        $section_items[ $key ] = $fields[ $key ];
    }

    // A title groups fields, so a section with one visible field shows no card and no title.
    $visible_fields = array_filter(
        $section_items,
        static function ($field) {
            return ! in_array($field['type'] ?? '', [ 'hidden', 'html' ], true);
        }
    );
    if (count($visible_fields) < 2) {
        echo '<div class="ppcart-section ppcart-section--plain ppcart-section--' . esc_attr(sanitize_html_class((string) $section_id)) . '">';
        $this->metabox_fields($section_items);
        echo '</div>';
        continue;
    }

    $heading_id          = 'ppcart-section-' . sanitize_html_class($tab_id . '-' . $section_id);
    $section_title       = (string) ($section['title'] ?? '');
    $section_description = (string) ($section['description'] ?? '');

    echo '<section class="ppcart-section ppcart-section--' . esc_attr(sanitize_html_class((string) $section_id)) . '"' . ('' !== $section_title ? ' aria-labelledby="' . esc_attr($heading_id) . '"' : '') . '>';
    if ('' !== $section_title) {
        echo '<div class="ppcart-section__header">';
        echo '<h4 class="ppcart-section__title" id="' . esc_attr($heading_id) . '">' . esc_html($section_title) . '</h4>';
        echo '</div>';
    }
    echo '<div class="ppcart-section__body">';
    if ('' !== $section_description) {
        echo '<p class="ppcart-section__description">' . esc_html($section_description) . '</p>';
    }
    $this->metabox_fields($section_items);
    echo '</div></section>';
}
