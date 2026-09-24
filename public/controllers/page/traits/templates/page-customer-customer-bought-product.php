<?php

if (! defined('ABSPATH')) {
    exit;
}


$atts = shortcode_atts([
    'email' => '',
    'user_id' => '',
    'ip_address' => false,
    'product_id' => '',
    'plan_id' => false,
    'has_subscription' => false,
], $atts);

$args = ['posts_per_page' => 1, 'post_status' => ['any']];

// set post type
if ($atts['has_subscription']) {
    $args['post_type'] = ppcart_query_post_types('subscription');
    $post_status = ['active','trialing','completed'];
} else {
    $args['post_type'] = ppcart_query_post_types('order');
    $post_status = ['paid','completed'];
}

// set post status
// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Order/subscription lookup must filter by stored order metadata.
$args['meta_query'] = [
    'relation' => 'AND',
    [
        'relation' => 'OR',
        [
            'key' => ppcart_meta_key('status'),
            'value' => $post_status,
            'compare' => 'IN',
        ],
        [
            'relation' => 'AND',
            [
                'key' => ppcart_meta_key('status'),
                'value' => 'pending-payment',
            ],
            [
                'key' => ppcart_meta_key('pay_method'),
                'value' => 'cod',
            ],
        ],
    ],
];

// set user
if (!$atts['email'] && $atts['user_id']) {
    $user = get_userdata($atts['user_id']);
    if ($user) {
        $atts['email'] = $user->user_email;
    }
} elseif ($atts['email'] && !$atts['user_id']) {
    $user = get_user_by('email', $atts['email']);
    if ($user) {
        $atts['user_id'] = $user->ID;
    }
}

$user_args = [
    'relation' => 'OR',
    [
        'key' => ppcart_meta_key('user_account'),
        'value' => $atts['user_id'],
    ],
    [
        'key' => ppcart_meta_key('email'),
        'value' => $atts['email'],
    ],
];

if ($atts['ip_address']) {
    $user_args[] = [
        'key' => ppcart_meta_key('ip_address'),
        'value' => $atts['ip_address'],
    ];
}

$args['meta_query'][] = $user_args;

// set product
if ($atts['product_id']) {
    $args['meta_query'][] = [
        'relation'      => 'OR',
        [
            'key' => ppcart_meta_key('product_id'),
            'value' => intval($atts['product_id']),
        ],
        [
            'key' => ppcart_meta_key('order_bumps'),
            'value'     => intval($atts['product_id']),
            'compare'   => 'LIKE',
        ],
    ];
}

// set plan ID
if ($atts['plan_id']) {
    $p_args = ['key' => ppcart_meta_key('option_id')];
    if (is_array($atts['plan_id'])) {
        $p_args['compare'] = 'IN';
        for ($i = 0; $i < count($atts['plan_id']); $i++) {
            $atts['plan_id'][$i] = sanitize_text_field($atts['plan_id'][$i]);
        }
        $p_args['value'] = $atts['plan_id'];
    } else {
        $p_args['value'] = sanitize_text_field($atts['plan_id']);
    }

    $args['meta_query'][] = $p_args;
}

$order_posts = get_posts($args);

if (!empty($order_posts)) {
    return $order_posts[0]->ID;
}

return false;
