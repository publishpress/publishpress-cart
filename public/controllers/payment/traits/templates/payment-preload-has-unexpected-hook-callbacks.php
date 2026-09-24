<?php

if (! defined('ABSPATH')) {
    exit;
}


global $wp_filter;

if (empty($wp_filter[ $hook_name ]) || ! isset($wp_filter[ $hook_name ]->callbacks)) {
    return false;
}

foreach ($wp_filter[ $hook_name ]->callbacks as $callbacks) {
    foreach ($callbacks as $callback) {
        $function = $callback['function'] ?? null;

        if ($this->is_allowed_hook_callback($function, $allowed_callbacks)) {
            continue;
        }

        return true;
    }
}

return false;
