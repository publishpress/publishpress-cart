<?php

if (! defined('ABSPATH')) {
    exit;
}


return $this->has_unexpected_hook_callbacks(
    'ppcart_show_upsell',
    [
        [
            'function' => 'ppcart_maybe_show_upsell',
        ],
    ]
);
