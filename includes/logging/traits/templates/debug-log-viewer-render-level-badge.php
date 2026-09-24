<?php

if (! defined('ABSPATH')) {
    exit;
}


$level = self::normalize_level($level);
return '<span class="ppcart-debug-log-level ppcart-debug-log-level--' . esc_attr(strtolower($level)) . '">' . esc_html(ucwords(strtolower($level))) . '</span>';
