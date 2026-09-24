<?php

if (! defined('ABSPATH')) {
    exit;
}


$context = isset($entry['context']) && is_array($entry['context']) ? $entry['context'] : [];

if (empty($context['order_id']) && empty($context['subscription_id']) && ! empty($context['record_id']) && function_exists('get_post_type')) {
    $record_post_type = get_post_type(absint($context['record_id']));
    if (function_exists('ppcart_is_order_post_type') && ppcart_is_order_post_type($record_post_type)) {
        $context['order_id'] = absint($context['record_id']);
    } elseif (function_exists('ppcart_is_subscription_post_type') && ppcart_is_subscription_post_type($record_post_type)) {
        $context['subscription_id'] = absint($context['record_id']);
    }
}

if (! empty($context['order_id'])) {
    $context = self::hydrate_order_context(absint($context['order_id']), $context);
}

if (! empty($context['subscription_id']) && is_numeric($context['subscription_id'])) {
    $context = self::hydrate_subscription_context(absint($context['subscription_id']), $context);
}

$entry['context'] = $context;
return $entry;
