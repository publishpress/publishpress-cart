<?php

if (! defined('ABSPATH')) {
    exit;
}


$amount = self::format_amount_context($context);
if ('-' === $amount) {
    return '-';
}

if (preg_match('/^(.+) ([A-Z]{3})$/', $amount, $matches)) {
    return esc_html($matches[1]) . '<br><small>' . esc_html($matches[2]) . '</small>';
}

return esc_html($amount);
