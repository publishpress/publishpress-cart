<?php

if (! defined('ABSPATH')) {
    die;
}

/**
     * Returns the URL the Pro upsell links point to.
     *
     * @param string $context Optional context string (e.g. "payment-square") so
     *                        callers can vary the URL per placement via the filter.
     *
     * @return string
     */
function ppcart_pro_upgrade_url($context = '')
{
    $url = 'https://publishpress.com/publishpress-cart/';

    /**
     * Filters the Pro upsell/upgrade URL.
     *
     * @param string $url     Upgrade URL.
     * @param string $context Placement context.
     */
    return (string) apply_filters('ppcart_pro_upgrade_url', $url, $context);
}


/**
     * Whether premium features should be shown locked.
     *
     * @return bool True when the Pro plugin is NOT active.
     */
function ppcart_pro_is_locked()
{
    return ! ppcart_is_pro();
}


/**
     * Returns a "Pro feature" lock button (padlock icon + label).
     *
     * Use in the control area of a card/row that is locked behind Pro.
     *
     * @param array $args {
     *     @type string $label   Button text. Default "Pro feature".
     *     @type string $url     Link target. Defaults to ppcart_pro_upgrade_url().
     *     @type string $context Placement context for the URL filter.
     *     @type string $class   Extra CSS class(es) appended to the button.
     * }
     *
     * @return string Escaped HTML.
     */
function ppcart_pro_feature_lock($args = [])
{
    $args = wp_parse_args(
        $args,
        [
            'label'   => __('Pro', 'publishpress-cart'),
            'url'     => '',
            'context' => '',
            'class'   => '',
        ]
    );

    $url     = '' !== $args['url'] ? $args['url'] : ppcart_pro_upgrade_url($args['context']);
    $classes = trim('ppcart-settings__pro-lock ' . $args['class']);

    return sprintf(
        '<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer">' .
            '<span class="dashicons dashicons-lock" aria-hidden="true"></span>' .
            '<span class="ppcart-settings__pro-lock-text">%3$s</span>' .
        '</a>',
        esc_attr($classes),
        esc_url($url),
        esc_html($args['label'])
    );
}


/**
     * Premium payment gateways shown as locked previews on the Payment Methods tab.
     *
     * Returns an empty array when Pro is active (nothing to lock).
     *
     * @return array[] Keyed by gateway slug: title, description, logo_file, logo_label.
     */
function ppcart_pro_locked_payment_methods()
{
    if (! ppcart_pro_is_locked()) {
        return [];
    }

    // Mirrors the premium payment-gateway add-ons sold for Pro; keep labels/logos in
    // sync with the publishpress-cart-pro Extensions list (Square/Mollie/Razorpay).
    $methods = [
        'square'   => [
            'title'       => __('Square', 'publishpress-cart'),
            'description' => __('Accept payments online and in person with Square, syncing items and inventory.', 'publishpress-cart'),
            'logo_file'   => 'extensions/square-150x150.png',
            'logo_label'  => 'SQ',
        ],
        'mollie'   => [
            'title'       => __('Mollie', 'publishpress-cart'),
            'description' => __('Offer global and local payment methods with fast, multilingual onboarding.', 'publishpress-cart'),
            'logo_file'   => 'extensions/m-150x150.png',
            'logo_label'  => 'MO',
        ],
        'razorpay' => [
            'title'       => __('Razorpay', 'publishpress-cart'),
            'description' => __('Accept payments in India and beyond with Razorpay\'s simple integration.', 'publishpress-cart'),
            'logo_file'   => 'extensions/razorpay-150x150.png',
            'logo_label'  => 'RP',
        ],
    ];

    /**
     * Filters the premium payment gateways shown as locked previews.
     *
     * @param array[] $methods Locked payment gateway definitions.
     */
    return (array) apply_filters('ppcart_pro_locked_payment_methods', $methods);
}


/**
     * Integration keys shown as locked previews on the Integrations tab.
     *
     * Keys match entries in the settings page $integration_meta table so the
     * existing title/description/logo metadata can be reused. Any key that is
     * actually registered (free or via an active add-on) is skipped at render
     * time, so this list is safe to keep broad.
     *
     * Returns an empty array when Pro is active.
     *
     * @return string[]
     */
function ppcart_pro_locked_integration_keys()
{
    if (! ppcart_pro_is_locked()) {
        return [];
    }

    $keys = [
        // Email marketing.
        'convertkit',
        'drip',
        'mailerlite',
        'mailpoet',
        'encharge',
        'fluentcrm',
        // Courses.
        'academylms',
        'gurucan',
        'heartbeat',
        'kajabi',
        'teachable',
        'upcoach',
        'tutor',
        'learndash',
        'wpcourseware',
        'mslms',
        // Membership.
        'groups',
        'memberpress',
        'armember',
        'wishlist',
        'rcp',
        'um',
    ];

    /**
     * Filters the integration keys shown as locked previews.
     *
     * @param string[] $keys Integration keys (must exist in $integration_meta).
     */
    return (array) apply_filters('ppcart_pro_locked_integration_keys', $keys);
}


/**
     * Settings tabs that are entirely Pro-only, mapped to how each one renders:
     *   'blur'   - the whole tab is blurred behind one centered "Pro" overlay
     *              (used for tabs with complex UI, e.g. the tax rates table)
     *   'fields' - every field is shown disabled with a lock beside it
     *
     * @return array<string,string> Tab slug => render mode. Empty when Pro is active.
     */
function ppcart_pro_locked_tabs()
{
    if (! ppcart_pro_is_locked()) {
        return [];
    }

    /**
     * Filters the map of fully Pro-locked settings tabs to render modes.
     *
     * @param array<string,string> $tabs Tab slug => 'blur' | 'fields'.
     */
    return (array) apply_filters(
        'ppcart_pro_locked_tabs',
        [
            'tax'         => 'blur',
            'white_label' => 'fields',
        ]
    );
}


/**
     * Small "PRO" badge shown next to a Pro-locked tab in the settings nav.
     *
     * @return string
     */
function ppcart_pro_nav_badge()
{
    return '<span class="ppcart-settings__nav-pro-badge">' . esc_html__('PRO', 'publishpress-cart') . '</span>';
}


/**
     * Centered "Pro feature" overlay card placed over a blurred, locked tab.
     *
     * @param array $args {
     *     @type string $context Placement context for the upgrade URL.
     *     @type string $title   Heading text.
     *     @type string $text    Body text.
     * }
     *
     * @return string Escaped HTML.
     */
function ppcart_pro_panel_lock_overlay($args = [])
{
    $args = wp_parse_args(
        $args,
        [
            'context' => '',
            'title'   => __('This is a Pro feature', 'publishpress-cart'),
            'text'    => __('This feature is available in the separate PublishPress Cart Pro plugin.', 'publishpress-cart'),
        ]
    );

    return '<div class="ppcart-settings__pro-tab-overlay">' .
            '<div class="ppcart-settings__pro-tab-overlay-card">' .
                '<span class="ppcart-settings__pro-tab-overlay-icon dashicons dashicons-lock" aria-hidden="true"></span>' .
                '<h3 class="ppcart-settings__pro-tab-overlay-title">' . esc_html($args['title']) . '</h3>' .
                '<p class="ppcart-settings__pro-tab-overlay-text">' . esc_html($args['text']) . '</p>' .
                ppcart_pro_feature_lock([
                    'context' => $args['context'],
                    'label'   => __('Upgrade to Pro', 'publishpress-cart'),
                ]) .
            '</div>' .
        '</div>';
}


/**
     * Display-only Pro fields surfaced (locked) on otherwise-free tabs.
     *
     * @param string $context Tab context: 'advanced', 'invoice' or 'white_label'.
     *
     * @return array[] Field definitions. Empty when Pro is active.
     */
function ppcart_pro_locked_fields($context)
{
    if (! ppcart_pro_is_locked()) {
        return [];
    }

    $fields = [
        'advanced'    => [
            [
                'label' => __('URL Coupon Parameter Name', 'publishpress-cart'),
                'type'  => 'text',
                'note'  => __('Use something else in place of "ppcart-coupon" when creating coupon URLs (e.g., https://example.com/product/?ppcart-coupon=20off).', 'publishpress-cart'),
            ],
            [
                'label' => __('Disable product template', 'publishpress-cart'),
                'type'  => 'toggle',
            ],
            [
                'label'      => __('Auto-expire download links', 'publishpress-cart'),
                'type'       => 'text',
                'note'       => __('The number of hours a download link will be valid or leave blank to never expire.', 'publishpress-cart'),
            ],
            [
                'label'       => __('Your API Key', 'publishpress-cart'),
                'type'        => 'text',
                'placeholder' => __('Configured (value hidden)', 'publishpress-cart'),
            ],
        ],
        'invoice'     => [
            [
                'label' => __('Invoice Notes/Terms', 'publishpress-cart'),
                'type'  => 'textarea',
            ],
            [
                'label' => __('Invoice Footer', 'publishpress-cart'),
                'type'  => 'text',
            ],
        ],
        'white_label' => [
            [ 'label' => __('Enable White Label', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('Hide from Settings', 'publishpress-cart'), 'type' => 'toggle' ],
            [ 'label' => __('Plugin Name', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Shortcode Slug', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Menu Icon (Dashicons)', 'publishpress-cart'), 'type' => 'select' ],
            [ 'label' => __('Menu Icon (Image)', 'publishpress-cart'), 'type' => 'image' ],
            [ 'label' => __('Author Name', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Author URL', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Plugin Description', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('API Documentation', 'publishpress-cart'), 'type' => 'text' ],
            [ 'label' => __('Invoice Date Documentation', 'publishpress-cart'), 'type' => 'text' ],
        ],
    ];

    $context = is_string($context) ? $context : '';
    $list    = $fields[ $context ] ?? [];

    /**
     * Filters the locked Pro fields for a tab.
     *
     * @param array[] $list    Field definitions.
     * @param string  $context Tab context.
     */
    return (array) apply_filters('ppcart_pro_locked_fields', $list, $context);
}
