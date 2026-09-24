<?php

if (! defined('ABSPATH')) {
    exit;
}


$panel_entries = [];

echo '<div class="ppcart-debug-log-split-wrap">';
echo '<div class="ppcart-debug-log-split">';
echo '<section class="ppcart-debug-log-master" aria-label="' . esc_attr__('Grouped debug events', 'publishpress-cart') . '">';
echo '<div class="ppcart-debug-log-master-head">';
echo '<span>' . esc_html__('Grouped events', 'publishpress-cart') . '</span>';
echo '<span>' . esc_html__('Latest', 'publishpress-cart') . '</span>';
echo '</div>';

if (empty($groups)) {
    echo '<div class="ppcart-debug-log-empty">' . esc_html__('No debug log entries found.', 'publishpress-cart') . '</div>';
}

foreach ($groups as $group_index => $group) {
    $group_id  = 'ppcart-debug-log-group-list-' . absint($group_index);
    $expanded  = 0 === $group_index;
    $count     = absint(self::get($group, 'event_count', 0));
    $count_text = sprintf(
        /* translators: %s: event count. */
        _n('%s event', '%s events', $count, 'publishpress-cart'),
        number_format_i18n($count)
    );

    echo '<article class="ppcart-debug-log-group-card' . esc_attr($expanded ? ' is-expanded' : '') . '">';
    echo '<button type="button" class="ppcart-debug-log-group-toggle" aria-expanded="' . esc_attr($expanded ? 'true' : 'false') . '" aria-controls="' . esc_attr($group_id) . '" data-ppcart-debug-group-toggle="' . esc_attr($group_id) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-group-' . $group_index . '-toggle')) . '">';
    echo self::render_workflow_icon(self::get($group, 'workflow', 'General')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static icon markup is escaped inside render_workflow_icon().
    echo '<span class="ppcart-debug-log-group-main">';
    echo '<span class="ppcart-debug-log-group-title">';
    echo '<strong>' . esc_html(self::get($group, 'title', '')) . '</strong>';
    echo self::render_level_badge(self::get($group, 'level', 'UNKNOWN')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_level_badge().
    echo '<span class="ppcart-debug-log-workflow-label">' . esc_html(self::get($group, 'workflow', 'General')) . '</span>';
    echo '</span>';
    echo '<span class="ppcart-debug-log-group-meta">' . esc_html($count_text . ' - ' . self::get($group, 'summary', '')) . '</span>';
    echo '</span>';
    echo '<span class="ppcart-debug-log-group-record">';
    echo '<span>' . esc_html(self::get($group, 'record', '-')) . '</span>';
    echo '<time>' . esc_html(self::format_group_time($group)) . '</time>';
    echo '</span>';
    echo '<span class="ppcart-debug-log-group-chevron" aria-hidden="true"></span>';
    echo '</button>';

    echo '<ol id="' . esc_attr($group_id) . '" class="ppcart-debug-log-inline-timeline">';
    foreach (self::get($group, 'entries', []) as $entry_index => $entry) {
        $event_id      = 'ppcart-debug-log-event-' . absint($group_index) . '-' . absint($entry_index);
        $is_first_item = empty($panel_entries);
        $panel_entries[] = [
            'id'       => $event_id,
            'group_id' => $group_id,
            'entry'    => $entry,
            'group'    => $group,
        ];

        echo '<li>';
        echo '<button type="button" class="ppcart-debug-log-event-card' . esc_attr($is_first_item ? ' is-active' : '') . '" data-ppcart-debug-event="' . esc_attr($event_id) . '" data-ppcart-debug-group="' . esc_attr($group_id) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-event-' . $group_index . '-' . $entry_index)) . '">';
        echo '<span class="ppcart-debug-log-event-marker" aria-hidden="true"></span>';
        echo '<span class="ppcart-debug-log-event-time">' . esc_html(self::format_event_clock_time($entry)) . '</span>';
        echo self::render_level_badge(self::get($entry, 'level', 'UNKNOWN')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_level_badge().
        echo '<span class="ppcart-debug-log-event-body">';
        echo '<span class="ppcart-debug-log-event-workflow">' . esc_html(self::get($entry, 'workflow', 'General')) . '</span>';
        echo '<strong>' . esc_html(self::get($entry, 'message', '')) . '</strong>';
        echo '<small>' . esc_html(self::get($entry, 'record', '-')) . '</small>';
        echo '</span>';
        echo '<span class="ppcart-debug-log-event-arrow" aria-hidden="true">&rsaquo;</span>';
        echo '</button>';
        echo '</li>';
    }
    echo '</ol>';
    echo '</article>';
}

echo '</section>';
echo '<section class="ppcart-debug-log-inspector" aria-live="polite">';
if (empty($panel_entries)) {
    echo '<div class="ppcart-debug-log-empty">' . esc_html__('Select a debug event to view its details.', 'publishpress-cart') . '</div>';
}

foreach ($panel_entries as $panel_index => $panel) {
    $previous_id = isset($panel_entries[ $panel_index - 1 ]) ? $panel_entries[ $panel_index - 1 ]['id'] : '';
    $next_id     = isset($panel_entries[ $panel_index + 1 ]) ? $panel_entries[ $panel_index + 1 ]['id'] : '';
    echo self::render_inspector_panel($panel, 0 === $panel_index, $previous_id, $next_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside render_inspector_panel().
}

echo '</section>';
echo '</div>';
self::render_pagination_footer($showing_from, $showing_to, $total, __('groups', 'publishpress-cart'), $page, $total_pages, $build_page_url);
echo '</div>';
