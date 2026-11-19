<?php

if (! defined('ABSPATH')) {
    exit;
}


return [
    [
        'id'          => 25,
        'slug'        => 'affiliate',
        'name'        => 'Affiliate Addon',
        'icon'        => $assets_url . 'affiliate-150x150.png',
        'description' => esc_html__('Run referral, influencer, and affiliate programs effortlessly. Recruit partners, customize commission plans, and automate payouts. The trusted plugin to expand reach, boost sales, grow your brand, and maximize ROI.', 'publishpress-cart'),
        'downloads'   => 22,
        'url'         => 'https://publishpress.com/links/cart-extensions',
    ],
    [
        'id'          => 74,
        'slug'        => 'mollie',
        'name'        => 'Mollie Addon',
        'icon'        => $assets_url . 'm-150x150.png',
        'description' => esc_html__('Offer global and local payment methods and get onboarded in minutes, with support in your language.', 'publishpress-cart'),
        'downloads'   => 7,
        'url'         => 'https://publishpress.com/links/cart-extensions',
    ],
    [
        'id'          => 78,
        'slug'        => 'razorpay',
        'name'        => 'Razorpay Addon',
        'icon'        => $assets_url . 'razorpay-150x150.png',
        'description' => esc_html__('Accept payments in your PublishPress Cart store with Razorpay. Integration and online onboarding included.', 'publishpress-cart'),
        'downloads'   => 10,
        'url'         => 'https://publishpress.com/links/cart-extensions',
    ],
    [
        'id'          => 84,
        'slug'        => 'square',
        'name'        => 'Square Addon',
        'icon'        => $assets_url . 'square-150x150.png',
        'description' => esc_html__('Accepting payments is easy with Square. Clear rates, fast deposits (1-2 business days). Sell online and in person, and sync all payments, items and inventory.', 'publishpress-cart'),
        'downloads'   => 6,
        'url'         => 'https://publishpress.com/links/cart-extensions',
    ],
];
