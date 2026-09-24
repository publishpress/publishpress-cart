<?php

if (! defined('ABSPATH')) {
    exit;
}


if (empty($options['settings']) || ! is_array($options['settings'])) {
    $options['settings'] = [];
}

$options['settings']['stripe_webhook_log'] = [
    'type'       => 'checkbox',
    'label'      => esc_html__('Enable Stripe webhook log', 'publishpress-cart'),
    'subsection' => 'debug',
    'settings'   => [
        'id'    => self::ENABLE_OPTION,
        'value' => get_option(self::ENABLE_OPTION, 1),
        'note'  => self::get_settings_links_note(),
    ],
];

$options['settings']['stripe_webhook_log_include_ignored'] = [
    'type'       => 'checkbox',
    'label'      => esc_html__('Log ignored Stripe webhook events', 'publishpress-cart'),
    'subsection' => 'debug',
    'settings'   => [
        'id'          => self::INCLUDE_IGNORED_OPTION,
        'value'       => '',
        'description' => esc_html__('Turn on only when you need to inspect all Stripe events sent to this site.', 'publishpress-cart'),
    ],
];

return $options;
