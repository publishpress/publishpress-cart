<?php

defined('ABSPATH') || exit;

function ppcart_language_pack_notice_screens($screens, $domain)
{
    if ('publishpress-cart' !== $domain || ! class_exists('PPCart_Admin_Screens') || ! function_exists('get_current_screen')) {
        return $screens;
    }

    $screen = get_current_screen();
    if (! $screen || ! PPCart_Admin_Screens::is_plugin_screen()) {
        return $screens;
    }

    $screens = is_array($screens) ? $screens : [];
    $screens[] = $screen->id;

    return array_values(array_unique($screens));
}

add_filter('publishpress_bundled_translations_hub_notice_screens', 'ppcart_language_pack_notice_screens', 10, 2);
