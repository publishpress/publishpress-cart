<?php

if (! defined('ABSPATH')) {
    exit;
}


return $this->has_unexpected_hook_callbacks(
    'ppcart_checkout_complete',
    [
        [
            'class'  => PPCart_Public_Order_Controller::class,
            'method' => 'conditional_order_confirmations',
        ],
    ]
);
