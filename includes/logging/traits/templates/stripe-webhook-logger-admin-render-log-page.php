<?php

if (! defined('ABSPATH')) {
    exit;
}


// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- View nonce already checked; sanitized immediately after unslash.
$log_status = isset($_GET['status']) ? self::sanitize_key(wp_unslash($_GET['status'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- View nonce already checked; sanitized immediately after unslash.
$event_type = isset($_GET['event_type']) ? self::sanitize_text(wp_unslash($_GET['event_type'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- View nonce already checked; sanitized immediately after unslash.
$log_search = isset($_GET['s']) ? self::sanitize_text(wp_unslash($_GET['s'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- View nonce already checked; sanitized immediately after unslash.
$date_from = isset($_GET['date_from']) ? self::sanitize_text(wp_unslash($_GET['date_from'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- View nonce already checked; sanitized immediately after unslash.
$date_to = isset($_GET['date_to']) ? self::sanitize_text(wp_unslash($_GET['date_to'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View nonce already checked.
$log_page = isset($_GET['log_paged']) ? max(1, absint($_GET['log_paged'])) : 1;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View nonce already checked.
$log_per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 25;
if (! in_array($log_per_page, [ 10, 25, 50, 100 ], true)) {
    $log_per_page = 25;
}

$matched_rows = self::read_entries(
    [
        'status'    => $log_status,
        'type'      => $event_type,
        'search'    => $log_search,
        'date_from' => $date_from,
        'date_to'   => $date_to,
        'limit'     => 1000,
        'bytes'     => 1048576,
    ]
);
$total        = count($matched_rows);
$total_pages  = max(1, (int) ceil($total / $log_per_page));
$log_page     = min($log_page, $total_pages);
$offset       = ($log_page - 1) * $log_per_page;
$rows         = array_slice($matched_rows, $offset, $log_per_page);
$showing_from = $total ? $offset + 1 : 0;
$showing_to   = $total ? min($offset + $log_per_page, $total) : 0;
$reset_url    = self::nonce_url('admin.php?page=ppcart-settings&ppcart_view_stripe_webhook_log=1', 'ppcart_view_stripe_webhook_log', 'ppcart_view_stripe_webhook_log_nonce');

$build_page_url = function ($target_page) use ($log_search, $event_type, $log_status, $date_from, $date_to, $log_per_page) {
    $query = [
        'page'                            => 'ppcart-settings',
        'ppcart_view_stripe_webhook_log' => 1,
        's'                               => $log_search,
        'event_type'                      => $event_type,
        'status'                          => $log_status,
        'date_from'                       => $date_from,
        'date_to'                         => $date_to,
        'per_page'                        => $log_per_page,
        'log_paged'                       => $target_page,
    ];
    $query = array_filter(
        $query,
        function ($value) {
            return '' !== (string) $value;
        }
    );
    $url = function_exists('add_query_arg') ? add_query_arg($query, admin_url('admin.php')) : 'admin.php?' . http_build_query($query);
    return self::nonce_url(str_replace(admin_url(), '', $url), 'ppcart_view_stripe_webhook_log', 'ppcart_view_stripe_webhook_log_nonce');
};

echo '<div class="ppcart-webhook-log-page">';
self::render_styles();
echo '<div class="ppcart-webhook-log-header">';
echo '<div>';
echo '<h1>' . esc_html__('Stripe Webhook Log', 'publishpress-cart') . '</h1>';
echo '<p>' . esc_html__('Recent Stripe webhook outcomes recorded by PublishPress Cart.', 'publishpress-cart') . '</p>';
echo '</div>';
/* translators: %s: number of results. */
echo '<span class="ppcart-webhook-log-count">' . esc_html(sprintf(_n('%s result', '%s results', $total, 'publishpress-cart'), number_format_i18n($total))) . '</span>';
echo '</div>';

echo '<form class="ppcart-webhook-log-filter" method="get">';
echo '<input type="hidden" name="page" value="ppcart-settings" />';
echo '<input type="hidden" name="ppcart_view_stripe_webhook_log" value="1" />';
echo '<input type="hidden" name="log_paged" value="1" />';
wp_nonce_field('ppcart_view_stripe_webhook_log', 'ppcart_view_stripe_webhook_log_nonce');
echo '<div class="ppcart-webhook-log-filter-grid">';
echo '<label class="screen-reader-text" for="ppcart-webhook-search">' . esc_html__('Search Stripe webhook log', 'publishpress-cart') . '</label>';
echo '<input id="ppcart-webhook-search" type="search" name="s" value="' . esc_attr($log_search) . '" placeholder="' . esc_attr__('Search events, objects, records...', 'publishpress-cart') . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-search')) . '" />';
echo '<label class="screen-reader-text" for="ppcart-webhook-event-type">' . esc_html__('Event type', 'publishpress-cart') . '</label>';
echo '<input id="ppcart-webhook-event-type" type="text" name="event_type" value="' . esc_attr($event_type) . '" placeholder="' . esc_attr__('Event type', 'publishpress-cart') . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-event-type')) . '" />';
echo '<label class="screen-reader-text" for="ppcart-webhook-status">' . esc_html__('Status', 'publishpress-cart') . '</label>';
echo '<select id="ppcart-webhook-status" name="status" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-status')) . '">';
echo '<option value="">' . esc_html__('All statuses', 'publishpress-cart') . '</option>';
foreach (self::allowed_statuses() as $allowed_status) {
    echo '<option value="' . esc_attr($allowed_status) . '"' . selected($log_status, $allowed_status, false) . '>' . esc_html(ucwords(str_replace('_', ' ', $allowed_status))) . '</option>';
}
echo '</select>';
echo '<label class="screen-reader-text" for="ppcart-webhook-date-from">' . esc_html__('From date', 'publishpress-cart') . '</label>';
echo '<input id="ppcart-webhook-date-from" type="date" name="date_from" value="' . esc_attr($date_from) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-date-from')) . '" />';
echo '<label class="screen-reader-text" for="ppcart-webhook-date-to">' . esc_html__('To date', 'publishpress-cart') . '</label>';
echo '<input id="ppcart-webhook-date-to" type="date" name="date_to" value="' . esc_attr($date_to) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-date-to')) . '" />';
echo '<label class="screen-reader-text" for="ppcart-webhook-per-page">' . esc_html__('Rows per page', 'publishpress-cart') . '</label>';
echo '<select id="ppcart-webhook-per-page" name="per_page" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-per-page')) . '">';
foreach ([ 10, 25, 50, 100 ] as $option) {
    /* translators: %s: number of rows per page. */
    echo '<option value="' . esc_attr($option) . '"' . selected($log_per_page, $option, false) . '>' . esc_html(sprintf(__('%s per page', 'publishpress-cart'), $option)) . '</option>';
}
echo '</select>';
echo '<a class="ppcart-webhook-log-button ppcart-webhook-log-button--secondary" href="' . esc_url($reset_url) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-reset')) . '">' . esc_html__('Reset', 'publishpress-cart') . '</a>';
echo '<button type="submit" class="ppcart-webhook-log-button" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-filter')) . '">' . esc_html__('Filter', 'publishpress-cart') . '</button>';
echo '</div>';
echo '</form>';

echo '<div class="ppcart-webhook-log-table-wrap">';
echo '<table class="ppcart-webhook-log-table"><thead><tr>';
foreach ([ 'Time', 'Status', 'Event', 'Stripe Object', 'PPC Record', 'Amount', 'Customer', 'Mode', 'Message', 'Details' ] as $heading) {
    echo '<th>' . esc_html($heading) . '</th>';
}
echo '</tr></thead><tbody>';

if (empty($rows)) {
    echo '<tr><td colspan="10">' . esc_html__('No Stripe webhook log entries found.', 'publishpress-cart') . '</td></tr>';
}

$row_index = 0;
foreach ($rows as $row) {
    $context = isset($row['context']) && is_array($row['context']) ? $row['context'] : [];
    $row_index++;
    $details_id = 'ppcart-webhook-log-details-' . absint($row_index);
    echo '<tr>';
    echo '<td class="ppcart-webhook-log-time">' . esc_html(self::format_admin_time($row)) . '</td>';
    echo '<td><span class="ppcart-webhook-log-status ppcart-webhook-log-status--' . esc_attr(self::get($row, 'status', '')) . '">' . esc_html(ucwords(str_replace('_', ' ', self::get($row, 'status', '')))) . '</span></td>';
    echo '<td><code>' . esc_html(self::get($row, 'type', '')) . '</code><br><small>' . esc_html(self::get($row, 'event_id', '')) . '</small></td>';
    echo '<td><code>' . esc_html(self::get($row, 'object_id', '')) . '</code><br><small>' . esc_html(self::get($row, 'object', '')) . '</small></td>';
    echo '<td class="ppcart-webhook-log-record">' . esc_html(self::format_record_context($context)) . '</td>';
    echo '<td class="ppcart-webhook-log-amount">' . wp_kses_post(self::format_amount_context_html($context)) . '</td>';
    echo '<td class="ppcart-webhook-log-customer">' . wp_kses_post(self::format_customer_context_html($context)) . '</td>';
    echo '<td class="ppcart-webhook-log-mode">' . esc_html(! empty($row['livemode']) ? 'live' : 'test') . '</td>';
    echo '<td class="ppcart-webhook-log-message">' . esc_html(self::get($row, 'message', '')) . '</td>';
    echo '<td><button type="button" class="ppcart-webhook-log-details-button" aria-expanded="false" aria-controls="' . esc_attr($details_id) . '" data-ppcart-webhook-details="' . esc_attr($details_id) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-row-' . $row_index . '-details')) . '">&lt;/&gt;</button></td>';
    echo '</tr>';
    echo '<tr id="' . esc_attr($details_id) . '" class="ppcart-webhook-log-details-row" hidden><td class="ppcart-webhook-log-details-cell" colspan="10">';
    echo self::render_details_panel($row, $context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_details_panel().
    echo '</td></tr>';
}

echo '</tbody></table>';
echo '<div class="ppcart-webhook-log-footer">';
/* translators: 1: first item number, 2: last item number, 3: total results. */
echo '<span>' . esc_html(sprintf(__('Showing %1$s to %2$s of %3$s results', 'publishpress-cart'), number_format_i18n($showing_from), number_format_i18n($showing_to), number_format_i18n($total))) . '</span>';
echo '<nav class="ppcart-webhook-log-pagination" aria-label="' . esc_attr__('Pagination', 'publishpress-cart') . '">';
echo '<a class="ppcart-webhook-log-page-link' . esc_attr($log_page <= 1 ? ' ppcart-webhook-log-page-link--disabled' : '') . '" href="' . esc_url($build_page_url(max(1, $log_page - 1))) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-page-prev')) . '">' . esc_html__('Prev', 'publishpress-cart') . '</a>';
for ($i = 1; $i <= $total_pages; $i++) {
    if ($total_pages > 7 && $i > 2 && $i < $total_pages - 1 && abs($i - $log_page) > 1) {
        if (3 === $i || $total_pages - 2 === $i) {
            echo '<span class="ppcart-webhook-log-page-link ppcart-webhook-log-page-link--disabled">...</span>';
        }
        continue;
    }
    echo '<a class="ppcart-webhook-log-page-link' . esc_attr($i === $log_page ? ' ppcart-webhook-log-page-link--current' : '') . '" href="' . esc_url($build_page_url($i)) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-page-' . $i)) . '">' . esc_html($i) . '</a>';
}
echo '<a class="ppcart-webhook-log-page-link' . esc_attr($log_page >= $total_pages ? ' ppcart-webhook-log-page-link--disabled' : '') . '" href="' . esc_url($build_page_url(min($total_pages, $log_page + 1))) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-stripe-webhook-log-page-next')) . '">' . esc_html__('Next', 'publishpress-cart') . '</a>';
echo '</nav>';
echo '</div>';
echo '</div>';
self::render_script();
echo '</div>';
