<?php

if (! defined('ABSPATH')) {
    exit;
}


$html  = '<div class="ppcart-debug-log-group-panel">';
$html .= '<div class="ppcart-debug-log-group-summary">';
$html .= '<div><span>' . esc_html__('Activity', 'publishpress-cart') . '</span><strong>' . esc_html($group['title']) . '</strong></div>';
$html .= '<div><span>' . esc_html__('Events', 'publishpress-cart') . '</span><strong>' . esc_html(number_format_i18n(absint($group['event_count']))) . '</strong></div>';
$html .= '<div><span>' . esc_html__('Started', 'publishpress-cart') . '</span><strong>' . esc_html(self::format_group_start_time($group)) . '</strong></div>';
$html .= '<div><span>' . esc_html__('Latest', 'publishpress-cart') . '</span><strong>' . esc_html(self::format_group_time($group)) . '</strong></div>';
$html .= '</div>';
$html .= '<ol class="ppcart-debug-log-timeline">';

foreach ($group['entries'] as $entry) {
    $html .= '<li>';
    $html .= '<div class="ppcart-debug-log-timeline-marker" aria-hidden="true"></div>';
    $html .= '<div class="ppcart-debug-log-timeline-body">';
    $html .= '<div class="ppcart-debug-log-timeline-head">';
    $html .= '<time>' . esc_html(self::format_admin_time($entry)) . '</time>';
    $html .= self::render_level_badge($entry['level']);
    $html .= '<span>' . esc_html($entry['workflow']) . '</span>';
    $html .= '</div>';
    $html .= '<p>' . esc_html($entry['message']) . '</p>';
    if ('-' !== $entry['record']) {
        $html .= '<div class="ppcart-debug-log-timeline-record">' . esc_html($entry['record']) . '</div>';
    }
    $html .= '<details class="ppcart-debug-log-event-details">';
    $html .= '<summary>' . esc_html__('Event details', 'publishpress-cart') . '</summary>';
    $html .= self::render_details_panel($entry);
    $html .= '</details>';
    $html .= '</div>';
    $html .= '</li>';
}

$html .= '</ol>';
$html .= '</div>';

return $html;
