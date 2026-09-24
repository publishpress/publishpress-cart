<?php

if (! defined('ABSPATH')) {
    die;
}

/**
     * Locked field definitions shown inside a product metabox tab.
     *
     * @param string $tab_id Tab id ('general' for the extra General-tab fields,
     *                        or one of the ppcart_pro_locked_product_tabs ids).
     *
     * @return array[] Field defs (label, type). Empty when Pro is active.
     */
function ppcart_pro_locked_product_fields($tab_id)
{
    if (! ppcart_pro_is_locked()) {
        return [];
    }

    // These labels/types mirror the real Pro field definitions. Keep in sync with:
    //   publishpress-cart-pro/admin/class-ppcart-pro-metaboxes.php  (payment / coupon / orderbump / upsellpath fields)
    //   publishpress-cart-pro/modules/shipping/class-ppcart-shipping.php  (shipping fields)
    //   publishpress-cart-pro/modules/extensions/affiliate/admin/class-ppcart-affiliate-admin.php  (affiliate fields)
    //   + the General-tab Pro fields bundled in the Pro build's class-ppcart-metaboxes.php
    // Mirror the real Pro "Payment Methods" intro notice.
    $enabled_methods = function_exists('ppcart_enabled_processors') ? ppcart_enabled_processors() : __('Stripe, PayPal', 'publishpress-cart');
    $payment_settings_url = admin_url('admin.php?page=ppcart-settings#payment_methods');
    $payments_notice = '<b>' . esc_html__('Globally Enabled Methods:', 'publishpress-cart') . '</b> ' . esc_html($enabled_methods) . '<br />' .
        '<a href="' . esc_url($payment_settings_url) . '">' . esc_html__('Change settings', 'publishpress-cart') . ' &rarr;</a>' .
        '<h4 class="ppcart-pro-locked-heading">' . esc_html__('Allow customers to pay for this product via:', 'publishpress-cart') . '</h4>';

    $fields = [
        // Extra Pro fields appended to the (free) General tab.
        'general'                => [
            [ 'label' => __('Disable single product page', 'publishpress-cart'), 'type' => 'toggle', 'note' => __('Hide the built-in checkout page for this product', 'publishpress-cart') ],
            [ 'label' => __('Single product page template', 'publishpress-cart'), 'type' => 'select', 'placeholder' => __('Default', 'publishpress-cart') ],
            [ 'label' => __('Tax Status', 'publishpress-cart'), 'type' => 'select', 'placeholder' => __('Taxable', 'publishpress-cart') ],
            [ 'label' => __('Purchase Note', 'publishpress-cart'), 'type' => 'textarea' ],
        ],
        'ppcart_pro_payment_methods' => [
            [ 'type' => 'notice', 'html' => $payments_notice ],
            [ 'label' => __('Cash on Delivery', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('Credit card (Stripe)', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('PayPal', 'publishpress-cart'), 'type' => 'toggle' ],
        ],
        'ppcart_pro_coupons'         => [
            [ 'label' => __('Show Coupon Field', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('Coupon Code', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Discount Type', 'publishpress-cart'), 'type' => 'select' ],
            [ 'label' => __('Amount Off', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Redemption Limit', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Coupon CSV File', 'publishpress-cart'), 'type' => 'image' ],
        ],
        'ppcart_pro_order_bumps'     => [
            [ 'label' => __('Enable Order Bump', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('Background Color', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Select Product', 'publishpress-cart'), 'type' => 'select' ],
            [ 'label' => __('Price', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Product Image', 'publishpress-cart'), 'type' => 'image' ],
            [ 'label' => __('Headline', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Product Description', 'publishpress-cart'), 'type' => 'textarea' ],
        ],
        'ppcart_pro_upsell_path'     => [
            [ 'label' => __('Select Path', 'publishpress-cart'), 'type' => 'select' ],
        ],
        'ppcart_pro_shipping'        => [
            [ 'label' => __('Enable shipping', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('Single item', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Single Item (Intl.)', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Each additional item', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Each additional item (Intl.)', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Taxable', 'publishpress-cart'), 'type' => 'toggle' ],
        ],
        'ppcart_pro_affiliates'      => [
            [ 'label' => __('Enable affiliate sales', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('Description', 'publishpress-cart'), 'type' => 'textarea' ],
            [ 'label' => __('Name (internal)', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Price', 'publishpress-cart'), 'type' => 'select' ],
            [ 'label' => __('Type', 'publishpress-cart'), 'type' => 'select' ],
            [ 'label' => __('Amount', 'publishpress-cart'), 'type' => 'text' ],
        ],
    ];

    $list = $fields[ $tab_id ] ?? [];

    /**
     * Filters the locked product metabox fields for a tab.
     *
     * @param array[] $list   Field definitions.
     * @param string  $tab_id Tab id.
     */
    return (array) apply_filters('ppcart_pro_locked_product_fields', $list, $tab_id);
}


/**
     * Renders the locked field rows for a product metabox tab. Each row reuses
     * the native .ppcart-field wrapper, shows a disabled control and a lock beside.
     *
     * @param string $tab_id Tab id.
     *
     * @return string
     */
function ppcart_pro_locked_product_field_rows_html($tab_id, $with_lock = true)
{
    $fields = ppcart_pro_locked_product_fields($tab_id);
    if (empty($fields)) {
        return '';
    }

    $html = '';
    foreach ($fields as $field) {
        // Informational notice / section heading rows (no control, no lock).
        if (isset($field['type']) && 'notice' === $field['type']) {
            $html .= '<div class="ppcart-field ppcart-pro-locked-notice">' . wp_kses_post((string) ($field['html'] ?? '')) . '</div>';
            continue;
        }

        $label = (string) ($field['label'] ?? '');
        $note  = isset($field['note']) ? (string) $field['note'] : '';
        $lock  = $with_lock
            ? ppcart_pro_feature_lock([
                'context' => 'product-' . $tab_id . '-' . sanitize_title($label),
                'class'   => 'ppcart-settings__pro-lock--sm',
            ])
            : '';

        $html .= '<div class="ppcart-field ppcart-pro-locked-field">' .
                '<label class="ppcart-pro-locked-field__label">' . esc_html($label) . '</label>' .
                '<div class="ppcart-pro-locked-field__control">' . ppcart_pro_locked_control_html($field) . '</div>' .
                $lock .
            '</div>';

        if ('' !== $note) {
            $html .= '<p class="ppcart-pro-locked-note">' . esc_html($note) . '</p>';
        }
    }

    return $html;
}


/**
     * Full locked content for a Pro-only product metabox tab: a "Pro feature"
     * banner followed by the tab's disabled fields.
     *
     * @param string $tab_id Tab id.
     *
     * @return string
     */
function ppcart_pro_render_locked_product_tab($tab_id)
{
    // Show the (disabled) fields blurred behind a single centred "Pro" overlay,
    // mirroring the locked Tax tab on the settings screen.
    return '<div class="ppcart-settings__pro-tab-lock">' .
            ppcart_pro_panel_lock_overlay([ 'context' => 'product-' . $tab_id ]) .
            '<div class="ppcart-settings__pro-tab-lock-content">' .
                ppcart_pro_locked_product_field_rows_html($tab_id, false) .
            '</div>' .
        '</div>';
}
