<?php

if (! defined('ABSPATH')) {
    exit;
}


$currency = ! empty($context['currency']) ? strtoupper((string) $context['currency']) : '';
$charge_id = self::get($context, 'charge_id', '');
if (! $charge_id && 'charge' === self::get($row, 'object', '')) {
    $charge_id = self::get($row, 'object_id', '');
}

$groups   = [
    __('Stripe', 'publishpress-cart')            => [
        [ __('Event type', 'publishpress-cart'), self::get($row, 'type', ''), true ],
        [ __('Event ID', 'publishpress-cart'), self::get($row, 'event_id', ''), true ],
        [ __('Object type', 'publishpress-cart'), self::get($row, 'object', ''), false ],
        [ __('Object ID', 'publishpress-cart'), self::get($row, 'object_id', ''), true ],
        [ __('Charge ID', 'publishpress-cart'), $charge_id, true ],
    ],
    __('PublishPress Cart', 'publishpress-cart') => [
        [ __('Record', 'publishpress-cart'), self::format_record_context($context), false ],
        [ __('Order ID', 'publishpress-cart'), self::format_hash_id(self::get($context, 'order_id', '')), false ],
        [ __('Subscription ID', 'publishpress-cart'), self::format_hash_id(self::get($context, 'subscription_id', '')), false ],
        [ __('Order status', 'publishpress-cart'), self::get($context, 'order_status', ''), false ],
    ],
    __('Money', 'publishpress-cart')             => [
        [ __('Original amount', 'publishpress-cart'), self::format_original_amount_context($context), false ],
        [ __('Refunded amount', 'publishpress-cart'), self::format_refunded_amount_context($context), false ],
        [ __('Currency', 'publishpress-cart'), $currency, false ],
    ],
    __('Customer', 'publishpress-cart')          => [
        [ __('Name', 'publishpress-cart'), self::get($context, 'customer_name', ''), false ],
        [ __('Email', 'publishpress-cart'), self::get($context, 'customer_email', ''), false ],
        [ __('Mode', 'publishpress-cart'), ! empty($row['livemode']) ? 'live' : 'test', false ],
        [ __('Message', 'publishpress-cart'), self::get($row, 'message', ''), false ],
    ],
];

$html = '<div class="ppcart-webhook-log-details-panel">';
foreach ($groups as $heading => $items) {
    $html .= '<section class="ppcart-webhook-log-details-group">';
    $html .= '<h3>' . esc_html($heading) . '</h3>';
    $html .= '<dl class="ppcart-webhook-log-detail-list">';
    foreach ($items as $item) {
        $html .= self::render_detail_item($item[0], $item[1], ! empty($item[2]));
    }
    $html .= '</dl>';
    $html .= '</section>';
}

$html .= '<details class="ppcart-webhook-log-raw-context">';
$html .= '<summary>' . esc_html__('Raw context', 'publishpress-cart') . '</summary>';
$html .= '<pre>' . esc_html(self::json_encode($context)) . '</pre>';
$html .= '</details>';
$html .= '</div>';

return $html;
