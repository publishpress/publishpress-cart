<?php

if (! defined('ABSPATH')) {
    exit;
}


$context = isset($row['context']) && is_array($row['context']) ? $row['context'] : [];
$groups  = [
    __('Summary', 'publishpress-cart') => [
        [ __('Message', 'publishpress-cart'), self::get($row, 'message', ''), false ],
        [ __('Level', 'publishpress-cart'), self::get($row, 'level', ''), false ],
        [ __('Workflow', 'publishpress-cart'), self::get($row, 'workflow', ''), false ],
        [ __('Time', 'publishpress-cart'), self::format_admin_time($row), false ],
    ],
    __('PublishPress Cart', 'publishpress-cart') => [
        [ __('Record', 'publishpress-cart'), self::get($row, 'record', ''), false ],
        [ __('Order ID', 'publishpress-cart'), self::format_hash_id(self::get($row, 'order_id', '')), false ],
        [ __('Subscription ID', 'publishpress-cart'), self::format_hash_id(self::get($row, 'subscription_id', '')), false ],
        [ __('Product ID', 'publishpress-cart'), self::format_hash_id(self::get($row, 'product_id', '')), false ],
    ],
    __('Stripe', 'publishpress-cart') => [
        [ __('Stripe IDs', 'publishpress-cart'), empty($row['stripe_ids']) ? '-' : implode(', ', $row['stripe_ids']), true ],
    ],
];

$html = '<div class="ppcart-debug-log-details-panel">';
foreach ($groups as $heading => $items) {
    $html .= '<section class="ppcart-debug-log-details-group">';
    $html .= '<h3>' . esc_html($heading) . '</h3>';
    $html .= '<dl class="ppcart-debug-log-detail-list">';
    foreach ($items as $item) {
        $html .= self::render_detail_item($item[0], $item[1], ! empty($item[2]));
    }
    $html .= '</dl>';
    $html .= '</section>';
}

if (! empty($context)) {
    $html .= '<details class="ppcart-debug-log-raw-context" open>';
    $html .= '<summary>' . esc_html__('Parsed context', 'publishpress-cart') . '</summary>';
    $html .= '<pre>' . esc_html(self::json_encode($context)) . '</pre>';
    $html .= '</details>';
}

$html .= '<details class="ppcart-debug-log-raw-context">';
$html .= '<summary>' . esc_html__('Raw line', 'publishpress-cart') . '</summary>';
$html .= '<pre>' . esc_html(self::get($row, 'raw', '')) . '</pre>';
$html .= '</details>';
$html .= '</div>';

return $html;
