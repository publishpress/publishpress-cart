<?php

if (! defined('ABSPATH')) {
    exit;
}


$value = '' === (string) $value ? '-' : (string) $value;
$html  = '<div>';
$html .= '<dt>' . esc_html($label) . '</dt>';
$html .= '<dd>';
$html .= $code ? '<code>' . esc_html($value) . '</code>' : esc_html($value);
$html .= '</dd>';
$html .= '</div>';

return $html;
