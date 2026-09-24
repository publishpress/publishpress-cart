<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin upgrade
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's update.
 *
 * @since 1.0.0
 * @package PPCart
 * @subpackage PPCart/includes
 * @author PublishPress <help@publishpress.com>
 */
class PPCart_Upgrade
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since 1.0.0
     */
    public static function upgrade()
    {

        do_action('ppcart_upgrade');

        if (class_exists('PPCart_Post_Status_Sync')) {
            PPCart_Post_Status_Sync::migrate_stored_statuses();
        }

        if (function_exists('ppcart_cpt_slug_migration_maybe_seed_state')) {
            ppcart_cpt_slug_migration_maybe_seed_state();
        }

        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- Required once during plugin upgrade to refresh routes.
        flush_rewrite_rules();
        self::setup_tax_table();
        self::add_cap();
    }

    public static function setup_tax_table()
    {
        global $wpdb;

        $ppcart_tax_table = ppcart_live_table('tax_rate');
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $ppcart_tax_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			tax_rate_country tinytext NOT NULL,
			tax_rate_state tinytext NOT NULL,
			tax_rate_postcode tinytext NOT NULL,
			tax_rate_city tinytext NOT NULL,
			tax_rate tinytext NOT NULL,
			tax_rate_title tinytext NULL,
			tax_rate_priority mediumint(9) NULL,
			tax_rate_meta longtext NULL,
			PRIMARY KEY (id)
		  ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        ppcart_flush_live_table_cache();
        ppcart_register_live_meta_table();
    }

    public static function compile_post_type_capabilities($singular = 'post', $plural = 'posts')
    {
        return [
            'edit_post'      => "edit_$singular",
            'read_post'      => "read_$singular",
            'delete_post'        => "delete_$singular",
            'edit_posts'         => "edit_$plural",
            'edit_others_posts'  => "edit_others_$plural",
            'publish_posts'      => "publish_$plural",
            'read_private_posts'     => "read_private_$plural",
            'read'                   => "read",
            'delete_posts'           => "delete_$plural",
            'delete_private_posts'   => "delete_private_$plural",
            'delete_published_posts' => "delete_published_$plural",
            'delete_others_posts'    => "delete_others_$plural",
            'edit_private_posts'     => "edit_private_$plural",
            'edit_published_posts'   => "edit_published_$plural",
            'create_posts'           => "edit_$plural",
        ];
    }

    /**
     * Adding new capability in the plugin
     */
    public static function add_cap()
    {
        if (! class_exists('PPCart_Activator', false)) {
            require_once __DIR__ . '/class-ppcart-activator.php';
        }

        PPCart_Activator::add_cap();
    }
}
