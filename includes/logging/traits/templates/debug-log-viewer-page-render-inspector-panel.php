<?php

if (! defined('ABSPATH')) {
    exit;
}


$entry = isset($panel['entry']) && is_array($panel['entry']) ? $panel['entry'] : [];
$panel_id = isset($panel['id']) ? (string) $panel['id'] : '';
$group_id = isset($panel['group_id']) ? (string) $panel['group_id'] : '';

$html  = '<div class="ppcart-debug-log-inspector-panel' . esc_attr($active ? ' is-active' : '') . '" data-ppcart-debug-panel="' . esc_attr($panel_id) . '" data-ppcart-debug-group="' . esc_attr($group_id) . '"' . ($active ? '' : ' hidden') . '>';
$html .= '<div class="ppcart-debug-log-inspector-toolbar">';
$html .= '<button type="button" class="ppcart-debug-log-link-button" data-ppcart-debug-back data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-inspector-' . $panel_id . '-back')) . '">&larr; ' . esc_html__('Back to group', 'publishpress-cart') . '</button>';
$html .= '<div class="ppcart-debug-log-inspector-nav">';
$html .= '<button type="button" class="ppcart-debug-log-icon-button" data-ppcart-debug-event="' . esc_attr($previous_id) . '"' . ($previous_id ? '' : ' disabled') . ' aria-label="' . esc_attr__('Previous event', 'publishpress-cart') . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-inspector-' . $panel_id . '-previous')) . '">&uarr;</button>';
$html .= '<button type="button" class="ppcart-debug-log-icon-button" data-ppcart-debug-event="' . esc_attr($next_id) . '"' . ($next_id ? '' : ' disabled') . ' aria-label="' . esc_attr__('Next event', 'publishpress-cart') . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-debug-log-inspector-' . $panel_id . '-next')) . '">&darr;</button>';
$html .= '</div>';
$html .= '</div>';
$html .= '<div class="ppcart-debug-log-inspector-head">';
$html .= '<span class="ppcart-debug-log-inspector-marker" aria-hidden="true"></span>';
$html .= '<time>' . esc_html(self::format_admin_time($entry)) . '</time>';
$html .= self::render_level_badge(self::get($entry, 'level', 'UNKNOWN'));
$html .= '<span>' . esc_html(self::get($entry, 'workflow', 'General')) . '</span>';
$html .= '</div>';
$html .= '<h2>' . esc_html(self::get($entry, 'message', '')) . '</h2>';
$html .= '<p class="ppcart-debug-log-inspector-record">' . esc_html(self::get($entry, 'record', '-')) . '</p>';
$html .= self::render_details_panel($entry);
$html .= '</div>';

return $html;
