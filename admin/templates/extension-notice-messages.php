<?php

if (! defined('ABSPATH')) {
    exit;
}


return [
    'affiliate' => [
        'activated'           => __('Affiliate Addon activated successfully.', 'publishpress-cart'),
        'deactivated'         => __('Affiliate Addon deactivated successfully.', 'publishpress-cart'),
        'activation-failed'   => __('Affiliate Addon activation failed.', 'publishpress-cart'),
        'deactivation-failed' => __('Affiliate Addon deactivation failed.', 'publishpress-cart'),
    ],
    'mollie' => [
        'activated'           => __('Mollie Addon activated successfully.', 'publishpress-cart'),
        'deactivated'         => __('Mollie Addon deactivated successfully.', 'publishpress-cart'),
        'activation-failed'   => __('Mollie Addon activation failed.', 'publishpress-cart'),
        'deactivation-failed' => __('Mollie Addon deactivation failed.', 'publishpress-cart'),
    ],
    'razorpay' => [
        'activated'           => __('Razorpay Addon activated successfully.', 'publishpress-cart'),
        'deactivated'         => __('Razorpay Addon deactivated successfully.', 'publishpress-cart'),
        'activation-failed'   => __('Razorpay Addon activation failed.', 'publishpress-cart'),
        'deactivation-failed' => __('Razorpay Addon deactivation failed.', 'publishpress-cart'),
    ],
    'square' => [
        'activated'           => __('Square Addon activated successfully.', 'publishpress-cart'),
        'deactivated'         => __('Square Addon deactivated successfully.', 'publishpress-cart'),
        'activation-failed'   => __('Square Addon activation failed.', 'publishpress-cart'),
        'deactivation-failed' => __('Square Addon deactivation failed.', 'publishpress-cart'),
    ],
];
