<?php

if (! defined('ABSPATH')) {
    exit;
}


$details_id = 'ppcart-debug-log-details-' . absint($row_index);
echo '<tr>';
echo '<td class="ppcart-debug-log-time">' . esc_html(self::format_admin_time($row)) . '</td>';
echo '<td>' . self::render_level_badge($row['level']) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_level_badge().
echo '<td class="ppcart-debug-log-workflow">' . esc_html($row['workflow']) . '</td>';
echo '<td class="ppcart-debug-log-message">' . esc_html($row['message']) . '</td>';
echo '<td class="ppcart-debug-log-record">' . esc_html($row['record']) . '</td>';
echo '<td><button type="button" class="ppcart-debug-log-details-button" aria-expanded="false" aria-controls="' . esc_attr($details_id) . '" data-ppcart-debug-details="' . esc_attr($details_id) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-event-' . $row_index . '-details')) . '">&lt;/&gt;</button></td>';
echo '</tr>';
echo '<tr id="' . esc_attr($details_id) . '" class="ppcart-debug-log-details-row" hidden><td class="ppcart-debug-log-details-cell" colspan="6">';
echo self::render_details_panel($row); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_details_panel().
echo '</td></tr>';
