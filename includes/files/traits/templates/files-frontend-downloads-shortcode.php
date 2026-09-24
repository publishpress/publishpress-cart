<?php

if (! defined('ABSPATH')) {
    exit;
}

$defaults = [ 'id' => 0, 'full' => true, 'show-title' => true ];
$attr     = shortcode_atts($defaults, $attr);

if (! $attr['id']) {
    return;
}

if ($attr['full']) {
    $items = ppcart_get_item_list(intval($attr['id']));
    $ids   = [];
    foreach ($items['items'] as $item) {
        if (isset($item['order_id']) && ! in_array($item['order_id'], $ids, true)) {
            $ids[] = $item['order_id'];
        }
    }
} else {
    $ids = (array) $attr['id'];
}

$return = '';

foreach ($ids as $order_id) {
    $order_id = absint($order_id);
    if (! $order_id) {
        continue;
    }

    if ($files = $this->get_order_downloads($order_id)) {
        foreach ($files as $download) {
            $return .= '<li><a href="' . esc_url($download->url) . '" target="_blank">' . esc_html($download->name) . '</a></li>';
        }
    }
}

if ($return) {
    if ($attr['show-title']) {
        return sprintf('<div class="ppcart-download-list"><h3>%s</h3><ul>%s<ul></div>', esc_html__('Downloads', 'publishpress-cart'), $return);
    }

    return sprintf('<div class="ppcart-download-list"><ul>%s<ul></div>', $return);
}
