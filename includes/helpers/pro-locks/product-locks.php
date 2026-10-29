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
            [ 'label' => __('Tax status', 'publishpress-cart'), 'type' => 'select', 'placeholder' => __('Taxable', 'publishpress-cart') ],
            [ 'label' => __('Purchase note', 'publishpress-cart'), 'type' => 'textarea' ],
        ],
        'ppcart_pro_payment_methods' => [
            [ 'type' => 'notice', 'html' => $payments_notice ],
            [ 'label' => __('Cash on delivery', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('Credit card (Stripe)', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('PayPal', 'publishpress-cart'), 'type' => 'toggle' ],
        ],
        'ppcart_pro_coupons'         => [
            [ 'label' => __('Show coupon field', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('Coupon code', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Discount type', 'publishpress-cart'), 'type' => 'select' ],
            [ 'label' => __('Amount off', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Redemption limit', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Coupon CSV file', 'publishpress-cart'), 'type' => 'image' ],
        ],
        'ppcart_pro_order_bumps'     => [
            [ 'label' => __('Enable order bump', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('Background color', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Select product', 'publishpress-cart'), 'type' => 'select' ],
            [ 'label' => __('Price', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Product image', 'publishpress-cart'), 'type' => 'image' ],
            [ 'label' => __('Headline', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Product description', 'publishpress-cart'), 'type' => 'textarea' ],
        ],
        'ppcart_pro_upsell_path'     => [
            [ 'label' => __('Select path', 'publishpress-cart'), 'type' => 'select' ],
        ],
        'ppcart_pro_affiliates'      => [
            [ 'label' => __('Enable affiliate sales', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('Description', 'publishpress-cart'), 'type' => 'textarea' ],
            [ 'label' => __('Name (internal)', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Payment plans', 'publishpress-cart'), 'type' => 'select' ],
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
 * Explain a product feature in both its locked preview and its active tab.
 *
 * @since 1.1.0
 * @param string $tab_id Product tab ID.
 * @return string
 */
function ppcart_product_tab_description($tab_id)
{
    $aliases = [
        'ppcart_pro_coupons' => 'coupons',
        'ppcart_pro_order_bumps' => 'orderbump',
        'ppcart_pro_upsell_path' => 'upsellPath',
        'ppcart_pro_affiliates' => 'affiliate',
    ];
    $tab_id = $aliases[$tab_id] ?? $tab_id;
    $descriptions = [
        'coupons' => __('Offer customers discounts on this product using coupon codes. Set the discount amount or percentage and choose when each coupon can be used.', 'publishpress-cart'),
        'orderbump' => __('Offer an extra product on the checkout form. Customers can add it to their order before completing payment.', 'publishpress-cart'),
        'upsellPath' => __('Offer additional products after checkout. Select an upsell path to choose which offers customers see and what happens when they accept or decline.', 'publishpress-cart'),
        'affiliate' => __('Let affiliates earn commissions for referring customers to this product. Choose whether affiliate sales are enabled and set the commission rules.', 'publishpress-cart'),
    ];

    return $descriptions[$tab_id] ?? '';
}

/**
     * Labels of the locked options for a product tab, without notice rows.
     *
     * @param string $tab_id Tab id.
     *
     * @return string[]
     */
function ppcart_pro_locked_product_field_labels($tab_id)
{
    $labels = [];
    foreach (ppcart_pro_locked_product_fields($tab_id) as $field) {
        if ((isset($field['type']) && 'notice' === $field['type']) || empty($field['label'])) {
            continue;
        }
        $labels[] = (string) $field['label'];
    }

    return $labels;
}

/**
     * One display-only card that previews a Pro feature: what it does, the
     * options it adds, and an upgrade link. Nothing in it is a form control.
     *
     * @param array $args title, text, labels (string[]), context, class.
     *
     * @return string
     */
function ppcart_pro_product_preview_card_html($args)
{
    $args = wp_parse_args(
        $args,
        [
            'title'   => __('This is a Pro feature', 'publishpress-cart'),
            'text'    => __('This feature is available in the separate PublishPress Cart Pro plugin.', 'publishpress-cart'),
            'labels'  => [],
            'context' => '',
            'class'   => '',
        ]
    );

    $list = '';
    if (! empty($args['labels'])) {
        $list .= '<p class="ppcart-pro-preview__list-label">' . esc_html__('Options you get with Pro:', 'publishpress-cart') . '</p><ul class="ppcart-pro-preview__list">';
        foreach ((array) $args['labels'] as $label) {
            $list .= '<li>' . esc_html($label) . '</li>';
        }
        $list .= '</ul>';
    }

    return '<div class="' . esc_attr(trim('ppcart-pro-preview ' . $args['class'])) . '">' .
            '<div class="ppcart-pro-preview__header">' .
                '<span class="ppcart-pro-preview__icon dashicons dashicons-lock" aria-hidden="true"></span>' .
                '<div>' .
                    '<p class="ppcart-pro-preview__title">' . esc_html($args['title']) . ' ' . ppcart_pro_nav_badge() . '</p>' .
                    '<p class="ppcart-pro-preview__text">' . esc_html($args['text']) . '</p>' .
                '</div>' .
            '</div>' .
            $list .
            '<a class="button ppcart-pro-preview__cta" href="' . esc_url(ppcart_pro_upgrade_url($args['context'])) . '" target="_blank" rel="noopener noreferrer">' .
                esc_html__('Upgrade to Pro', 'publishpress-cart') .
                '<span class="screen-reader-text"> ' . esc_html__('(opens in a new tab)', 'publishpress-cart') . '</span>' .
            '</a>' .
        '</div>';
}

/**
     * "More with Pro" card for the extra Pro options of a free product tab.
     *
     * @param string $tab_id Tab id, for example 'general'.
     *
     * @return string Empty when Pro is active.
     */
function ppcart_pro_locked_product_more_card_html($tab_id)
{
    $labels = ppcart_pro_locked_product_field_labels($tab_id);
    if ([] === $labels) {
        return '';
    }

    return ppcart_pro_product_preview_card_html([
        'title'   => __('More product options', 'publishpress-cart'),
        'text'    => __('Pro adds more ways to set up this product.', 'publishpress-cart'),
        'labels'  => $labels,
        'context' => 'product-' . $tab_id,
        'class'   => 'ppcart-pro-preview--compact',
    ]);
}

/**
     * Full locked content for a Pro-only product metabox tab: one preview card
     * with the feature description and the options it adds.
     *
     * @param string $tab_id Tab id.
     *
     * @return string
     */
function ppcart_pro_render_locked_product_tab($tab_id)
{
    $args = [
        'labels'  => ppcart_pro_locked_product_field_labels($tab_id),
        'context' => 'product-' . $tab_id,
    ];
    $description = ppcart_product_tab_description($tab_id);
    if ('' !== $description) {
        $args['text'] = $description;
    }

    return ppcart_pro_product_preview_card_html($args);
}
