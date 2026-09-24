<?php

if (! defined('ABSPATH')) {
    exit;
}


$context = self::get_context($hook_suffix);

if (self::is_excluded_core_admin_screen($context)) {
    return false;
}

if (self::is_known_hook_suffix($context['hook_suffix'])) {
    return true;
}

if (self::is_known_or_compatible_page_slug($context['page'])) {
    return true;
}

if (! empty($context['route_params'])) {
    return true;
}

if (! $context['has_screen']) {
    return self::is_known_post_type($context['post_type']) || self::is_known_taxonomy($context['taxonomy']);
}

if (self::is_known_post_type($context['screen_post_type']) || self::is_known_post_type($context['current_post_type'])) {
    return true;
}

if (self::is_known_taxonomy($context['screen_taxonomy'])) {
    return true;
}

if (self::is_known_or_compatible_screen_id($context['screen_id']) || self::is_known_or_compatible_screen_id($context['screen_base'])) {
    return true;
}

if (! self::is_cart_admin_menu_parent($context['admin_page_parent'])) {
    return false;
}

return '' !== $context['page']
    || '' !== $context['hook_suffix']
    || self::is_known_or_compatible_screen_id($context['screen_id'])
    || self::is_known_or_compatible_screen_id($context['screen_base']);
