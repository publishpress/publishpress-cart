<?php

if (! defined('ABSPATH')) {
    exit;
}


echo '<div class="ppcart-debug-log-footer">';
/* translators: 1: first item number, 2: last item number, 3: total items, 4: item label. */
echo '<span>' . esc_html(sprintf(__('Showing %1$s to %2$s of %3$s %4$s', 'publishpress-cart'), number_format_i18n($showing_from), number_format_i18n($showing_to), number_format_i18n($total), $item_label)) . '</span>';
echo '<nav class="ppcart-debug-log-pagination" aria-label="' . esc_attr__('Pagination', 'publishpress-cart') . '">';
echo '<a class="ppcart-debug-log-page-link' . esc_attr($page <= 1 ? ' ppcart-debug-log-page-link--disabled' : '') . '" href="' . esc_url(call_user_func($build_page_url, max(1, $page - 1))) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-page-prev')) . '">' . esc_html__('Prev', 'publishpress-cart') . '</a>';
for ($i = 1; $i <= $total_pages; $i++) {
    if ($total_pages > 7 && $i > 2 && $i < $total_pages - 1 && abs($i - $page) > 1) {
        if (3 === $i || $total_pages - 2 === $i) {
            echo '<span class="ppcart-debug-log-page-link ppcart-debug-log-page-link--disabled">...</span>';
        }
        continue;
    }
    echo '<a class="ppcart-debug-log-page-link' . esc_attr($i === $page ? ' ppcart-debug-log-page-link--current' : '') . '" href="' . esc_url(call_user_func($build_page_url, $i)) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-page-' . $i)) . '">' . esc_html($i) . '</a>';
}
echo '<a class="ppcart-debug-log-page-link' . esc_attr($page >= $total_pages ? ' ppcart-debug-log-page-link--disabled' : '') . '" href="' . esc_url(call_user_func($build_page_url, min($total_pages, $page + 1))) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-page-next')) . '">' . esc_html__('Next', 'publishpress-cart') . '</a>';
echo '</nav>';
echo '</div>';
