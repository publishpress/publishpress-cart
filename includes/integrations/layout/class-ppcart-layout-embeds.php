<?php

defined('ABSPATH') || exit;

class PPCart_Layout_Embeds
{
    private const ACCOUNT_COMPONENTS = [
        'account-orders' => 'tab-orders',
        'account-subscriptions' => 'tab-subscriptions',
        'account-payment-plans' => 'tab-plans',
        'account-profile' => 'tab-profile',
        'account-downloads' => 'tab-files',
    ];

    public static function definitions()
    {
        return [
            'checkout-form' => __('Cart Checkout', 'publishpress-cart'),
            'account-page-builder' => __('Cart Account Page', 'publishpress-cart'),
            'account-navigation' => __('Cart Account Navigation', 'publishpress-cart'),
            'account-tab' => __('Cart Account Tab', 'publishpress-cart'),
            'account-orders' => __('Cart Account Orders', 'publishpress-cart'),
            'account-subscriptions' => __('Cart Account Subscriptions', 'publishpress-cart'),
            'account-payment-plans' => __('Cart Account Payment Plans', 'publishpress-cart'),
            'account-profile' => __('Cart Account Profile', 'publishpress-cart'),
            'account-login' => __('Cart Account Login', 'publishpress-cart'),
            'account-downloads' => __('Cart Account Downloads', 'publishpress-cart'),
        ];
    }

    public static function register_shortcodes()
    {
        foreach (array_keys(self::definitions()) as $slug) {
            add_shortcode('ppcart_' . str_replace('-', '_', $slug), static function ($attributes) use ($slug) {
                return self::render($slug, (array) $attributes);
            });
        }
    }

    public static function register_widgets($manager)
    {
        require_once __DIR__ . '/class-ppcart-elementor-widget.php';

        foreach (self::definitions() as $slug => $title) {
            $manager->register(new PPCart_Elementor_Widget([], null, $slug));
        }
    }

    public static function component_options()
    {
        return array_intersect_key(self::definitions(), self::ACCOUNT_COMPONENTS);
    }

    public static function render($slug, array $settings)
    {
        $attributes = [];
        $children = [];

        if ('checkout-form' === $slug) {
            $attributes = [
                'pid' => (string) absint($settings['id'] ?? 0),
                'plan' => sanitize_text_field($settings['plan'] ?? ''),
                'coupon' => sanitize_text_field($settings['coupon'] ?? ''),
            ];
        } elseif ('account-page-builder' === $slug) {
            $children = [
                self::block('account-login'),
                self::block('account-navigation', self::navigation_attributes(), self::account_panels()),
            ];
        } elseif ('account-navigation' === $slug) {
            $attributes = self::navigation_attributes();
            $children = self::account_panels();
        } elseif ('account-tab' === $slug) {
            $component = $settings['component'] ?? 'account-orders';
            if (! isset(self::ACCOUNT_COMPONENTS[$component])) {
                return '';
            }

            $attributes['active'] = true;
            $attributes['tabId'] = self::ACCOUNT_COMPONENTS[$component];
            $children[] = self::block($component);
        } else {
            $attributes['active'] = true;
        }

        $output = render_block(self::block($slug, $attributes, $children));
        if ('' !== $output) {
            do_action('ppcart_enqueue_frontend_assets');
        }

        return $output;
    }

    private static function navigation_attributes()
    {
        return [
            'includedTabs' => array_values(array_intersect(array_column(ppcart_account_tabs(), 'id'), self::ACCOUNT_COMPONENTS)),
            'includedTabsConfigured' => true,
        ];
    }

    private static function account_panels()
    {
        return array_map([self::class, 'block'], array_keys(self::ACCOUNT_COMPONENTS));
    }

    private static function block($slug, $attributes = [], $children = [])
    {
        // Account containers render their children in the renderer's tab context.
        return [
            'blockName' => 'publishpress-cart/' . $slug,
            'attrs' => $attributes,
            'innerBlocks' => $children,
            'innerHTML' => '',
            'innerContent' => [],
        ];
    }
}
