<?php

if (! defined('ABSPATH')) {
    exit;
}

$sample = [
    [
        'name' => __('User Guide.pdf', 'publishpress-cart'),
        'meta' => '2.4 MB - PDF',
    ],
    [
        'name' => __('Sample Pack.zip', 'publishpress-cart'),
        'meta' => '18.7 MB - ZIP',
    ],
    [
        'name' => __('Onboarding Video.mp4', 'publishpress-cart'),
        'meta' => '124 MB - MP4',
    ],
    [
        'name' => __('Bonus Workbook.docx', 'publishpress-cart'),
        'meta' => '320 KB - DOCX',
    ],
];

$download_label = esc_html__('Download', 'publishpress-cart');
$items          = '';
$item_index     = 0;

foreach ($sample as $item) {
    $item_index++;
    $items .= '<div class="ppcart-download-item">'
        . '<span class="ppcart-download-icon" aria-hidden="true">&#128196;</span>'
        . '<div class="ppcart-download-meta"><strong>' . esc_html($item['name']) . '</strong><span>' . esc_html($item['meta']) . '</span></div>'
        . '<a class="ppcart-download-action" href="#" data-testid="' . esc_attr(ppcart_testid('ppcart-account-download-preview-' . $item_index)) . '">' . $download_label . '</a>'
        . '</div>';
}

return '<div class="ppcart-downloads-list">' . $items . '</div>';
