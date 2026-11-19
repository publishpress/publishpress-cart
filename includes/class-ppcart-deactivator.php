<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin deactivation
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since 1.0.0
 * @package PPCart
 * @subpackage PPCart/includes
 * @author PublishPress <help@publishpress.com>
 */
class PPCart_Deactivator
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since 1.0.0
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook('ppcart_daily_events', ['reminder']);
        wp_clear_scheduled_hook('ppcart_daily_events', ['trial_ending']);
        wp_clear_scheduled_hook('ppcart_cleanup_preloaded_intents');
    }
}
