<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- View nonce is checked by caller; sanitized immediately after unslash.
$level = isset($_GET['level']) ? self::normalize_level(wp_unslash($_GET['level'])) : '';
if ('UNKNOWN' === $level && empty($_GET['level'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View nonce is checked by caller.
    $level = '';
}
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- View nonce is checked by caller; sanitized immediately after unslash.
$log_search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- View nonce is checked by caller; sanitized immediately after unslash.
$date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- View nonce is checked by caller; sanitized immediately after unslash.
$date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View nonce is checked by caller.
$log_page = isset($_GET['log_paged']) ? max(1, absint($_GET['log_paged'])) : 1;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View nonce is checked by caller.
$log_per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 25;
if (! in_array($log_per_page, [ 10, 25, 50, 100 ], true)) {
    $log_per_page = 25;
}
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View nonce is checked by caller.
$view = isset($_GET['view']) && 'events' === sanitize_key(wp_unslash($_GET['view'])) ? 'events' : 'groups';

$matched_rows = self::read_matching_entries(
    [
        'level'     => $level,
        'search'    => $log_search,
        'date_from' => $date_from,
        'date_to'   => $date_to,
        'limit'     => 1000,
        'bytes'     => self::DEFAULT_TAIL_BYTES,
        'file_name' => $file_name,
    ]
);
$matched_items = 'events' === $view ? $matched_rows : self::group_entries($matched_rows);
$total        = count($matched_items);
$total_pages  = max(1, (int) ceil($total / $log_per_page));
$log_page     = min($log_page, $total_pages);
$offset       = ($log_page - 1) * $log_per_page;
$rows         = array_slice($matched_items, $offset, $log_per_page);
$showing_from = $total ? $offset + 1 : 0;
$showing_to   = $total ? min($offset + $log_per_page, $total) : 0;
$reset_url    = self::nonce_url('admin.php?page=ppcart-settings&ppcart_view_log=1', 'ppcart_view_debug_log', 'ppcart_view_debug_log_nonce');
$download_url = self::nonce_url('admin.php?page=ppcart-settings&ppcart_download_log=1', 'ppcart_download_debug_log', 'ppcart_download_debug_log_nonce');
$clear_url    = self::nonce_url('admin.php?page=ppcart-settings&ppcart_reset_log=1', 'ppcart_reset_debug_log', 'ppcart_reset_debug_log_nonce');

$build_page_url = function ($target_page) use ($log_search, $level, $date_from, $date_to, $log_per_page, $view) {
    $query = [
        'page'        => 'ppcart-settings',
        'ppcart_view_log' => 1,
        's'           => $log_search,
        'level'       => $level,
        'date_from'   => $date_from,
        'date_to'     => $date_to,
        'per_page'    => $log_per_page,
        'view'        => $view,
        'log_paged'   => $target_page,
    ];
    $query = array_filter(
        $query,
        function ($value) {
            return '' !== (string) $value;
        }
    );
    $url = function_exists('add_query_arg') ? add_query_arg($query, admin_url('admin.php')) : 'admin.php?' . http_build_query($query);
    return self::nonce_url(str_replace(admin_url(), '', $url), 'ppcart_view_debug_log', 'ppcart_view_debug_log_nonce');
};
$build_view_url = function ($target_view) use ($log_search, $level, $date_from, $date_to, $log_per_page) {
    $query = [
        'page'        => 'ppcart-settings',
        'ppcart_view_log' => 1,
        's'           => $log_search,
        'level'       => $level,
        'date_from'   => $date_from,
        'date_to'     => $date_to,
        'per_page'    => $log_per_page,
        'view'        => 'events' === $target_view ? 'events' : 'groups',
        'log_paged'   => 1,
    ];
    $query = array_filter(
        $query,
        function ($value) {
            return '' !== (string) $value;
        }
    );
    $url = function_exists('add_query_arg') ? add_query_arg($query, admin_url('admin.php')) : 'admin.php?' . http_build_query($query);
    return self::nonce_url(str_replace(admin_url(), '', $url), 'ppcart_view_debug_log', 'ppcart_view_debug_log_nonce');
};

echo '<div class="ppcart-debug-log-page">';
self::render_styles();
echo '<div class="ppcart-debug-log-header">';
echo '<div>';
echo '<h1>' . esc_html__('Debug Log', 'publishpress-cart') . '</h1>';
echo '<p>' . esc_html__('Recent PublishPress Cart diagnostic events, parsed from the raw debug log.', 'publishpress-cart') . '</p>';
echo '</div>';
echo '<div class="ppcart-debug-log-header-actions">';
$count_label = 'events' === $view
    ? sprintf(
        /* translators: %s: event count. */
        _n('%s event', '%s events', $total, 'publishpress-cart'),
        number_format_i18n($total)
    )
    : sprintf(
        /* translators: %s: group count. */
        _n('%s group', '%s groups', $total, 'publishpress-cart'),
        number_format_i18n($total)
    );
echo '<span class="ppcart-debug-log-count">' . esc_html($count_label) . '</span>';
echo '<div class="ppcart-debug-log-view-switch" aria-label="' . esc_attr__('Debug log view', 'publishpress-cart') . '">';
echo '<a class="' . esc_attr('groups' === $view ? 'is-active' : '') . '" href="' . esc_url($build_view_url('groups')) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-view-groups')) . '">' . esc_html__('Grouped', 'publishpress-cart') . '</a>';
echo '<a class="' . esc_attr('events' === $view ? 'is-active' : '') . '" href="' . esc_url($build_view_url('events')) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-view-events')) . '">' . esc_html__('Raw events', 'publishpress-cart') . '</a>';
echo '</div>';
echo '<a class="ppcart-debug-log-button ppcart-debug-log-button--secondary" href="' . esc_url($download_url) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-download-raw')) . '">' . esc_html__('Download raw', 'publishpress-cart') . '</a>';
echo '<a class="ppcart-debug-log-button ppcart-debug-log-button--danger" href="' . esc_url($clear_url) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-clear')) . '">' . esc_html__('Clear log', 'publishpress-cart') . '</a>';
echo '</div>';
echo '</div>';

echo '<form class="ppcart-debug-log-filter" method="get">';
echo '<input type="hidden" name="page" value="ppcart-settings" />';
echo '<input type="hidden" name="ppcart_view_log" value="1" />';
echo '<input type="hidden" name="log_paged" value="1" />';
echo '<input type="hidden" name="view" value="' . esc_attr($view) . '" />';
wp_nonce_field('ppcart_view_debug_log', 'ppcart_view_debug_log_nonce');
echo '<div class="ppcart-debug-log-filter-grid">';
echo '<label class="screen-reader-text" for="ppcart-debug-search">' . esc_html__('Search debug log', 'publishpress-cart') . '</label>';
echo '<input id="ppcart-debug-search" type="search" name="s" value="' . esc_attr($log_search) . '" placeholder="' . esc_attr__('Search messages, records, Stripe IDs...', 'publishpress-cart') . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-search')) . '" />';
echo '<label class="screen-reader-text" for="ppcart-debug-level">' . esc_html__('Level', 'publishpress-cart') . '</label>';
echo '<select id="ppcart-debug-level" name="level" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-level')) . '">';
echo '<option value="">' . esc_html__('All levels', 'publishpress-cart') . '</option>';
foreach (self::allowed_levels() as $allowed_level) {
    echo '<option value="' . esc_attr($allowed_level) . '"' . selected($level, $allowed_level, false) . '>' . esc_html(ucwords(strtolower($allowed_level))) . '</option>';
}
echo '</select>';
echo '<label class="screen-reader-text" for="ppcart-debug-date-from">' . esc_html__('From date', 'publishpress-cart') . '</label>';
echo '<input id="ppcart-debug-date-from" type="date" name="date_from" value="' . esc_attr($date_from) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-date-from')) . '" />';
echo '<label class="screen-reader-text" for="ppcart-debug-date-to">' . esc_html__('To date', 'publishpress-cart') . '</label>';
echo '<input id="ppcart-debug-date-to" type="date" name="date_to" value="' . esc_attr($date_to) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-date-to')) . '" />';
echo '<label class="screen-reader-text" for="ppcart-debug-per-page">' . esc_html__('Rows per page', 'publishpress-cart') . '</label>';
echo '<select id="ppcart-debug-per-page" name="per_page" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-per-page')) . '">';
foreach ([ 10, 25, 50, 100 ] as $option) {
    /* translators: %s: number of rows per page. */
    echo '<option value="' . esc_attr($option) . '"' . selected($log_per_page, $option, false) . '>' . esc_html(sprintf(__('%s per page', 'publishpress-cart'), $option)) . '</option>';
}
echo '</select>';
echo '<a class="ppcart-debug-log-button ppcart-debug-log-button--secondary" href="' . esc_url($reset_url) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-reset')) . '">' . esc_html__('Reset', 'publishpress-cart') . '</a>';
echo '<button type="submit" class="ppcart-debug-log-button" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-filter')) . '">' . esc_html__('Filter', 'publishpress-cart') . '</button>';
echo '</div>';
echo '</form>';

if ('events' === $view) {
    echo '<div class="ppcart-debug-log-table-wrap">';
    echo '<table class="ppcart-debug-log-table"><thead><tr>';
    foreach ([ 'Time', 'Level', 'Workflow', 'Message', 'Record', 'Details' ] as $heading) {
        echo '<th>' . esc_html($heading) . '</th>';
    }
    echo '</tr></thead><tbody>';

    if (empty($rows)) {
        echo '<tr><td colspan="6">' . esc_html__('No debug log entries found.', 'publishpress-cart') . '</td></tr>';
    }

    $row_index = 0;
    foreach ($rows as $row) {
        $row_index++;
        self::render_event_row($row, $row_index);
    }

    echo '</tbody></table>';
    self::render_pagination_footer($showing_from, $showing_to, $total, __('events', 'publishpress-cart'), $log_page, $total_pages, $build_page_url);
    echo '</div>';
} else {
    self::render_grouped_split_view($rows, $showing_from, $showing_to, $total, $log_page, $total_pages, $build_page_url);
}

self::render_script();
echo '</div>';
