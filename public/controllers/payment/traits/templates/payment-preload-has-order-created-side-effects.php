<?php

if (! defined('ABSPATH')) {
    exit;
}


return $this->has_unexpected_hook_callbacks(
    'ppcart_order_created',
    [
        [
            'class'  => 'PPCart_Files',
            'method' => 'attach_downloads_to_order',
        ],
    ]
);
