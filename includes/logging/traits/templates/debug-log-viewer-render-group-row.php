<?php

if (! defined('ABSPATH')) {
    exit;
}


$details_id = 'ppcart-debug-log-group-' . absint($row_index);
$count_text = sprintf(
    /* translators: %s: event count. */
    _n('%s event', '%s events', absint($group['event_count']), 'publishpress-cart'),
    number_format_i18n(absint($group['event_count']))
);

echo '<tr class="ppcart-debug-log-group-row">';
echo '<td class="ppcart-debug-log-time">' . esc_html(self::format_group_time($group)) . '</td>';
echo '<td>' . self::render_level_badge($group['level']) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_level_badge().
echo '<td class="ppcart-debug-log-workflow">' . esc_html($group['workflow']) . '</td>';
echo '<td class="ppcart-debug-log-message ppcart-debug-log-group-message">';
echo '<strong>' . esc_html($group['title']) . '</strong>';
echo '<span>' . esc_html($count_text . ' - ' . $group['summary']) . '</span>';
echo '</td>';
echo '<td class="ppcart-debug-log-record">' . esc_html($group['record']) . '</td>';
echo '<td><button type="button" class="ppcart-debug-log-details-button" aria-expanded="false" aria-controls="' . esc_attr($details_id) . '" data-ppcart-debug-details="' . esc_attr($details_id) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-group-' . $row_index . '-details')) . '">' . esc_html__('Timeline', 'publishpress-cart') . '</button></td>';
echo '</tr>';
echo '<tr id="' . esc_attr($details_id) . '" class="ppcart-debug-log-details-row" hidden><td class="ppcart-debug-log-details-cell" colspan="6">';
echo self::render_group_details_panel($group); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_group_details_panel().
echo '</td></tr>';
