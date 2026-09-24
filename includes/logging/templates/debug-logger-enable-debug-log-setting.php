<?php

if (! defined('ABSPATH')) {
    exit;
}


$options['settings']['debugging'] = [
    'type'          => 'checkbox',
    'label'         => esc_html__('Enable debug logging', 'publishpress-cart'),
    'subsection'    => 'debug',
    'settings'      => [
        'id'            => '_ppcart_enable_debug',
        'value'         => '',
        'note'  => sprintf(
            '<a href="%s" rel="noopener noreferrer" target="_blank" data-testid="%s">%s</a> &nbsp; <a href="%s" rel="noopener noreferrer" data-testid="%s">%s</a> &nbsp; <a href="%s" rel="noopener noreferrer" data-testid="%s">%s</a>',
            esc_attr(wp_nonce_url('admin.php?page=ppcart-settings&ppcart_view_log=1', 'ppcart_view_debug_log', 'ppcart_view_debug_log_nonce')),
            esc_attr(ppcart_testid('ppcart-admin-debug-log-note-view')),
            __('view log', 'publishpress-cart'),
            esc_attr(wp_nonce_url('admin.php?page=ppcart-settings&ppcart_download_log=1', 'ppcart_download_debug_log', 'ppcart_download_debug_log_nonce')),
            esc_attr(ppcart_testid('ppcart-admin-debug-log-note-download')),
            __('download log', 'publishpress-cart'),
            esc_attr(wp_nonce_url('admin.php?page=ppcart-settings&ppcart_reset_log=1', 'ppcart_reset_debug_log', 'ppcart_reset_debug_log_nonce')),
            esc_attr(ppcart_testid('ppcart-admin-debug-log-note-delete')),
            __('delete log', 'publishpress-cart')
        ),
    ],
];

return $options;
