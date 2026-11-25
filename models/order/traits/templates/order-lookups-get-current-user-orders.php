<?php

if (! defined('ABSPATH')) {
    exit;
}


return ppcart_get_customer_product_orders(
    (int) $product_id,
    [
        'user_id' => get_current_user_id(),
        'email'   => (string) filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
    ],
    $limit
);
