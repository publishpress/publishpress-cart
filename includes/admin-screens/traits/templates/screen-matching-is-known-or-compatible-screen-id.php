<?php

if (! defined('ABSPATH')) {
    exit;
}


if ('' === $screen_id) {
    return false;
}

if (self::is_known_hook_suffix($screen_id)) {
    return true;
}

foreach (self::all_post_types() as $known_post_type) {
    if ($screen_id === $known_post_type || $screen_id === 'edit-' . $known_post_type) {
        return true;
    }
}

foreach (self::all_taxonomies() as $known_taxonomy) {
    if ($screen_id === $known_taxonomy || $screen_id === 'edit-' . $known_taxonomy) {
        return true;
    }
}

foreach (self::COMPATIBLE_HOOK_PREFIXES as $prefix) {
    if (0 === strpos($screen_id, $prefix)) {
        return true;
    }
}

return false;
