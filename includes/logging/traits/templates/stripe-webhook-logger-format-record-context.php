<?php

if (! defined('ABSPATH')) {
    exit;
}


$parts = [];

if (! empty($context['order_id'])) {
    $parts[] = 'Order #' . absint($context['order_id']);
}

if (! empty($context['subscription_id']) && is_numeric($context['subscription_id'])) {
    $parts[] = 'Subscription #' . absint($context['subscription_id']);
}

if (! empty($parts)) {
    return implode(' / ', $parts);
}

if (! empty($context['record_id'])) {
    return '#' . absint($context['record_id']);
}

return '-';
