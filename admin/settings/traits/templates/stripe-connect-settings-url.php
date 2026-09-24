<?php

if (!defined('ABSPATH')) {
    exit;
}

$url = add_query_arg(
    [
        'page' => PPCart_Admin_Screens::PAGE_SETTINGS,
    ],
    admin_url('admin.php')
);

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin navigation state.
$payment_subtab = isset($_GET['ppcart_payment_subtab']) ? sanitize_text_field((string) wp_unslash($_GET['ppcart_payment_subtab'])) : '';
if ('' !== $payment_subtab && preg_match('/^(enable|method:[a-z0-9_\-]+)$/i', $payment_subtab)) {
    $url = add_query_arg('ppcart_payment_subtab', $payment_subtab, $url);
}

return $url . '#payment_methods';
