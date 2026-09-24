<?php

if (! defined('ABSPATH') && ! defined('WPINC')) {
    exit;
}

/**
 * Load PublishPress Cart Compatibility packages when that plugin is active.
 *
 * StudioCart leftover PHP (shims, merchant Data migration) lives in the sibling
 * plugin publishpress-cart-compat, not in this Free tree. Cart must not restore
 * includes/compat/ here. When the companion is active, require its package entry
 * files from WP_PLUGIN_DIR before Cart's canonical-only live stubs so leftover-
 * aware helpers exist first.
 *
 * Constant bridges run twice from publishpress-cart.php: inbound leftover names
 * before Cart defines PPCART_* defaults, then outbound aliases after.
 */
class PPCart_Companion_Loader
{
    /**
     * Stable basename of the companion plugin. Detection uses this list entry,
     * not class_exists on leftover Studiocart class names — Compatibility Mode shims
     * those when Studiocart is already gone.
     */
    public const PLUGIN_BASENAME = 'publishpress-cart-compat/publishpress-cart-compat.php';

    /**
     * Package entry files inside the companion plugin. All must be readable
     * before any is required so a partial install does not load a half set.
     *
     * @var array<int, string>
     */
    public const PACKAGE_RELATIVE_PATHS = [
        'includes/compat/studiocart-compatibility-mode/index.php',
        'includes/compat/cpt-slug-migration/index.php',
        'includes/compat/meta-key-migration/index.php',
        'includes/compat/shortcode-tag-migration/index.php',
        'includes/compat/custom-table-migration/bootstrap.php',
    ];

    /**
     * Directory of the active PublishPress Cart Compatibility plugin, or empty.
     *
     * Reads active_plugins and network active_sitewide_plugins at plugin-load so
     * an installed-but-inactive companion is ignored. Path is always
     * WP_PLUGIN_DIR/publishpress-cart-compat/ when the basename matches.
     *
     * @return string Trailing-slash path, or '' when the companion is not active.
     */
    public static function plugin_dir()
    {
        $basename = self::PLUGIN_BASENAME;

        $active = get_option('active_plugins', []);
        if (is_array($active) && in_array($basename, $active, true)) {
            return WP_PLUGIN_DIR . '/publishpress-cart-compat/';
        }

        $network = get_site_option('active_sitewide_plugins', []);
        if (is_array($network) && isset($network[$basename])) {
            return WP_PLUGIN_DIR . '/publishpress-cart-compat/';
        }

        return '';
    }

    /**
     * Require companion compat packages when that plugin is active and readable.
     *
     * No-op when the companion is inactive or any entry file is missing. Cart
     * continues with canonical-only stubs in that case.
     *
     * @return bool True when companion packages were loaded this request.
     */
    public static function load_packages()
    {
        $dir = self::plugin_dir();
        if ($dir === '') {
            return false;
        }

        foreach (self::PACKAGE_RELATIVE_PATHS as $relative) {
            if (! is_readable($dir . $relative)) {
                return false;
            }
        }

        foreach (self::PACKAGE_RELATIVE_PATHS as $relative) {
            require_once $dir . $relative;
        }

        return true;
    }

    /**
     * Two-phase leftover/canonical constant bridges.
     *
     * Call once before Cart defines PPCART_* (inbound: leftover names already in
     * the environment feed canonical defaults) and once after (outbound: leftover
     * aliases for third-party PHP). Safe no-op when the companion did not load
     * or Compatibility Mode is off.
     *
     * @param bool $from_companion Whether companion packages were loaded this request.
     * @return void
     */
    public static function maybe_register_constants($from_companion)
    {
        if ($from_companion && class_exists('PPCartComp_Studiocart_Compatibility_Mode', false)) {
            PPCartComp_Studiocart_Compatibility_Mode::maybe_register_constants();
        }
    }
}

if (! function_exists('ppcart_companion_plugin_dir')) {
    /**
     * Directory of the active PublishPress Cart Compatibility plugin, or empty.
     *
     * @return string Trailing-slash path, or '' when the companion is not active.
     */
    function ppcart_companion_plugin_dir()
    {
        return PPCart_Companion_Loader::plugin_dir();
    }
}

if (! function_exists('ppcart_load_companion_compat_packages')) {
    /**
     * Require companion compat packages when that plugin is active and readable.
     *
     * @return bool True when companion packages were loaded this request.
     */
    function ppcart_load_companion_compat_packages()
    {
        return PPCart_Companion_Loader::load_packages();
    }
}

if (! function_exists('ppcart_maybe_register_studiocart_constants')) {
    /**
     * Two-phase leftover/canonical constant bridges.
     *
     * @param bool $from_companion Whether companion packages were loaded this request.
     * @return void
     */
    function ppcart_maybe_register_studiocart_constants($from_companion)
    {
        PPCart_Companion_Loader::maybe_register_constants($from_companion);
    }
}
