<?php

if (! defined('ABSPATH') && ! defined('WPINC')) {
    exit;
}

/**
 * Refuse to load while leftover Studiocart is still active.
 *
 * Keep basename matching in sync with PPCartComp_Studiocart_Conflict.
 * Detection uses plugin basenames, not leftover Studiocart class symbols — Compatibility Mode
 * shims those names when Studiocart is already gone.
 */
class PPCart_Studiocart_Conflict
{
    /**
     * Option set when activation is deferred until leftover Studiocart is gone.
     */
    public const PENDING_ACTIVATION_OPTION = '_ppcart_pending_activation';

    /**
     * @param string $basename Plugin basename (folder/file.php).
     * @return bool
     */
    public static function is_studiocart_basename($basename): bool
    {
        $basename = str_replace('\\', '/', (string) $basename);
        $basename = ltrim($basename, '/');
        if ('' === $basename) {
            return false;
        }

        $lower = strtolower($basename);
        if (false !== strpos($lower, 'publishpress-cart')) {
            return false;
        }

        $file = strtolower(basename($basename));
        $dir  = strtolower(dirname($basename));

        if ('studiocart.php' === $file || 'ncs-cart.php' === $file) {
            return true;
        }

        if ('.' !== $dir && false !== strpos($dir, 'studiocart')) {
            return true;
        }

        return false;
    }

    /**
     * @param array<int, string>   $active  active_plugins list.
     * @param array<string, mixed> $network active_sitewide_plugins map.
     * @return string Basename or empty.
     */
    public static function find_active_basename(array $active, array $network = []): string
    {
        foreach ($active as $basename) {
            if (is_string($basename) && self::is_studiocart_basename($basename)) {
                return $basename;
            }
        }

        foreach (array_keys($network) as $basename) {
            if (is_string($basename) && self::is_studiocart_basename($basename)) {
                return $basename;
            }
        }

        return '';
    }

    /**
     * @return string
     */
    public static function active_basename(): string
    {
        $active = [];
        if (function_exists('get_option')) {
            $option = get_option('active_plugins', []);
            if (is_array($option)) {
                $active = $option;
            }
        }

        $network = [];
        if (function_exists('is_multisite') && is_multisite() && function_exists('get_site_option')) {
            $option = get_site_option('active_sitewide_plugins', []);
            if (is_array($option)) {
                $network = $option;
            }
        }

        return self::find_active_basename($active, $network);
    }

    /**
     * @return bool
     */
    public static function is_active(): bool
    {
        return '' !== self::active_basename();
    }

    /**
     * @return void
     */
    public static function register_notice(): void
    {
        if (defined('PPCART_STUDIOCART_CONFLICT_NOTICED')) {
            return;
        }

        define('PPCART_STUDIOCART_CONFLICT_NOTICED', true);
        add_action('admin_notices', [ self::class, 'render_notice' ]);
        add_action('network_admin_notices', [ self::class, 'render_notice' ]);
    }

    /**
     * Limit the conflict notice to the Plugins screens where the admin can act.
     *
     * Self-contained: Cart admin screen helpers are not loaded during soft-stop.
     *
     * @return bool
     */
    public static function is_plugins_list_screen(): bool
    {
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if (is_object($screen) && isset($screen->id)) {
                return in_array((string) $screen->id, [ 'plugins', 'plugins-network' ], true);
            }
        }

        global $pagenow;
        if (isset($pagenow) && 'plugins.php' === $pagenow) {
            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    public static function render_notice(): void
    {
        if (function_exists('current_user_can') && ! current_user_can('activate_plugins')) {
            return;
        }

        if (! self::is_plugins_list_screen()) {
            return;
        }

        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'PublishPress Cart and PublishPress Cart Compatibility cannot run while Studiocart is active. Deactivate Studiocart to start those plugins.',
            'publishpress-cart'
        );
        echo '</p></div>';
    }

    /**
     * Soft-stop activation: stay listed as active, skip load, run setup later.
     *
     * @param string $plugin_file Absolute path to this plugin's bootstrap file.
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function on_activate($plugin_file): void
    {
        if (! self::is_active()) {
            return;
        }

        if (function_exists('update_option')) {
            update_option(self::PENDING_ACTIVATION_OPTION, '1');
        }
    }

    /**
     * Consume a deferred activation flag. True once, then false.
     *
     * @return bool
     */
    public static function consume_pending_activation(): bool
    {
        if (! function_exists('get_option')) {
            return false;
        }

        if ('1' !== (string) get_option(self::PENDING_ACTIVATION_OPTION)) {
            return false;
        }

        if (function_exists('delete_option')) {
            delete_option(self::PENDING_ACTIVATION_OPTION);
        }

        return true;
    }
}
