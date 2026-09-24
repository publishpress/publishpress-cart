<?php

if (! defined('ABSPATH')) {
    exit;
}


$normalized_hook_suffix = self::normalize_key($hook_suffix);

$screen = null;
if (function_exists('get_current_screen')) {
    $screen = get_current_screen();
}

$has_screen = is_object($screen);
$context    = [
    'hook_suffix'       => $normalized_hook_suffix,
    'page'              => self::query_key('page'),
    'post_type'         => self::query_key('post_type') ?: self::edited_post_type(),
    'taxonomy'          => self::query_key('taxonomy'),
    'route_params'      => self::route_params(),
    'has_screen'        => $has_screen,
    'screen_id'         => $has_screen && isset($screen->id) ? self::normalize_key($screen->id) : '',
    'screen_base'       => $has_screen && isset($screen->base) ? self::normalize_key($screen->base) : '',
    'screen_post_type'  => $has_screen && isset($screen->post_type) ? self::normalize_key($screen->post_type) : '',
    'screen_taxonomy'   => $has_screen && isset($screen->taxonomy) ? self::normalize_key($screen->taxonomy) : '',
    'admin_page_parent' => $has_screen && function_exists('get_admin_page_parent') ? self::normalize_key(get_admin_page_parent()) : '',
    'current_post_type' => $has_screen && function_exists('get_post_type') ? self::normalize_key(get_post_type()) : '',
];

return $context;
