<?php

if (! defined('ABSPATH')) {
    die;
}

/**
     * Maps a settings tab slug to the settings-section suffix that its locked
     * Pro fields are appended to (after the "{plugin_name}-" prefix).
     *
     * Keeps the "which locked fields attach to which card" knowledge here with
     * the field data, so the settings page can inject previews generically
     * instead of hard-coding one branch per tab. Add a tab here (and its fields
     * in ppcart_pro_locked_fields()) to surface locked previews on a new tab.
     *
     * @return array<string,string> Tab slug => section suffix. Empty when Pro is active.
     */
function ppcart_pro_locked_settings_field_sections()
{
    if (! ppcart_pro_is_locked()) {
        return [];
    }

    /**
     * Filters the tab => section-suffix map for locked settings fields.
     *
     * @param array<string,string> $sections Tab slug => section suffix.
     */
    return (array) apply_filters(
        'ppcart_pro_locked_settings_field_sections',
        [
            'advanced' => 'settings',
            'invoice'  => 'invoice-setting',
        ]
    );
}


/**
     * Builds a disabled, display-only control for a locked field.
     *
     * @param array $field Field definition (type, placeholder).
     *
     * @return string
     */
function ppcart_pro_locked_control_html($field)
{
    $type        = $field['type'] ?? 'text';
    $placeholder = isset($field['placeholder']) ? (string) $field['placeholder'] : '';

    switch ($type) {
        case 'toggle':
            return '<span class="ppcart-settings__pro-toggle" aria-hidden="true"></span>';
        case 'textarea':
            return '<div class="textarea-wrap"><textarea class="large-text" rows="4" disabled></textarea></div>';
        case 'select':
            $select_label = '' !== $placeholder ? $placeholder : __('-- select --', 'publishpress-cart');
            return '<select disabled><option>' . esc_html($select_label) . '</option></select>';
        case 'image':
            return '<div class="ppcart-settings__pro-field-image">' .
                    '<input type="text" disabled />' .
                    '<button type="button" class="button" disabled>' . esc_html__('Set Image', 'publishpress-cart') . '</button>' .
                '</div>';
        case 'text':
        default:
            return '<div class="input-group field-text"><input type="text" placeholder="' . esc_attr($placeholder) . '" disabled /></div>';
    }
}


/**
     * Returns the <tr> rows for the locked Pro fields of a tab.
     *
     * @param string $context Tab context.
     *
     * @return string
     */
function ppcart_pro_locked_field_rows_html($context)
{
    $fields = ppcart_pro_locked_fields($context);
    if (empty($fields)) {
        return '';
    }

    $html = '';
    foreach ($fields as $field) {
        $label = (string) ($field['label'] ?? '');
        $note  = isset($field['note']) ? (string) $field['note'] : '';
        $html .= '<tr class="ppcart-settings__pro-field-row">' .
                '<th scope="row">' . esc_html($label) . '</th>' .
                '<td>' .
                    '<div class="ppcart-settings__pro-field-cell">' .
                        '<div class="ppcart-settings__pro-field-control">' . ppcart_pro_locked_control_html($field) . '</div>' .
                        ppcart_pro_feature_lock([
                            'context' => $context . '-' . sanitize_title($label),
                            'class'   => 'ppcart-settings__pro-lock--sm',
                        ]) .
                    '</div>' .
                    ('' !== $note ? '<p class="description">' . esc_html($note) . '</p>' : '') .
                '</td>' .
            '</tr>';
    }

    return $html;
}


/**
     * Premium email notifications surfaced (locked) on the Email tab.
     *
     * @return array[] Each: title, description, recipient. Empty when Pro is active.
     */
function ppcart_pro_locked_emails()
{
    if (! ppcart_pro_is_locked()) {
        return [];
    }

    // Mirrors the premium email notifications registered by publishpress-cart-pro.
    $emails = [
        [
            'title'       => __('Order Complete Notification', 'publishpress-cart'),
            'description' => __('Sent when an order is marked complete.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
        ],
        [
            'title'       => __('Trial Ending Reminder', 'publishpress-cart'),
            'description' => __('Sent before a subscription trial ends.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
        ],
        [
            'title'       => __('Upcoming Renewal Reminder', 'publishpress-cart'),
            'description' => __('Sent before an upcoming subscription renewal.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
        ],
        [
            'title'       => __('Subscription Paused Confirmation', 'publishpress-cart'),
            'description' => __('Sent when a subscription is paused.', 'publishpress-cart'),
            'recipient'   => __('Customer', 'publishpress-cart'),
        ],
    ];

    /**
     * Filters the locked premium email notifications.
     *
     * @param array[] $emails Email row definitions.
     */
    return (array) apply_filters('ppcart_pro_locked_emails', $emails);
}


/**
     * Returns locked email-notification rows for the Email tab list.
     *
     * @param string[] $exclude_titles Titles already rendered (skip duplicates).
     *
     * @return string
     */
function ppcart_pro_locked_email_rows_html($exclude_titles = [])
{
    $emails = ppcart_pro_locked_emails();
    if (empty($emails)) {
        return '';
    }

    $exclude = array_map(
        static function ($title) {
            return strtolower(trim((string) $title));
        },
        (array) $exclude_titles
    );

    $html = '';
    foreach ($emails as $email) {
        $title       = (string) ($email['title'] ?? '');
        $description = (string) ($email['description'] ?? '');
        $recipient   = (string) ($email['recipient'] ?? '');
        if (in_array(strtolower(trim($title)), $exclude, true)) {
            continue;
        }

        $html .= '<div class="ppcart-settings__email-notifications-row ppcart-settings__email-notifications-row--locked" role="row">' .
                '<div class="ppcart-settings__email-notifications-email" role="cell">' .
                    '<span class="ppcart-settings__email-status is-disabled" aria-hidden="true"></span>' .
                    '<span class="ppcart-settings__email-summary">' .
                        '<span class="ppcart-settings__email-title">' . esc_html($title) . '</span>' .
                        '<span class="ppcart-settings__email-desc">' . esc_html($description) . '</span>' .
                    '</span>' .
                '</div>' .
                '<div class="ppcart-settings__email-recipient" role="cell">' . esc_html($recipient) . '</div>' .
                '<div class="ppcart-settings__email-action" role="cell">' .
                    ppcart_pro_feature_lock([
                        'context' => 'email-' . sanitize_title($title),
                        'class'   => 'ppcart-settings__pro-lock--sm',
                    ]) .
                '</div>' .
            '</div>';
    }

    return $html;
}


/**
     * Pro-only tabs surfaced (locked) in the product settings metabox.
     *
     * Keys are unique tab ids (intentionally distinct from the free field-group
     * property names so they never collide with real tab content); values are
     * the tab labels. Returns an empty array when Pro is active.
     *
     * @return array<string,string>
     */
function ppcart_pro_locked_product_tabs()
{
    if (! ppcart_pro_is_locked()) {
        return [];
    }

    // Tab ids/labels mirror publishpress-cart-pro/includes/class-ppcart-pro-bootstrap.php
    // (register_product_setting_tabs). Ids are prefixed ppcart_pro_ so they never collide with
    // the real Pro field-group property names on $this.

    /**
     * Filters the Pro-only product metabox tabs shown as locked previews.
     *
     * @param array<string,string> $tabs Tab id => label.
     */
    return (array) apply_filters(
        'ppcart_pro_locked_product_tabs',
        [
            'ppcart_pro_coupons'         => __('Coupons', 'publishpress-cart'),
            'ppcart_pro_order_bumps'     => __('Order Bumps', 'publishpress-cart'),
            'ppcart_pro_upsell_path'     => __('Upsell Path', 'publishpress-cart'),
            'ppcart_pro_shipping'        => __('Shipping', 'publishpress-cart'),
            'ppcart_pro_affiliates'      => __('Affiliates', 'publishpress-cart'),
        ]
    );
}
