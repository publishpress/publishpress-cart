<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin activation
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
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 1.0.0
 * @package PPCart
 * @subpackage PPCart/includes
 * @author PublishPress <help@publishpress.com>
 */
class PPCart_Activator
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since 1.0.0
     */
    public static function activate()
    {

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/helpers/ppcart-scheduling.php';
        ppcart_maybe_schedule_reminders('reminder');
        ppcart_maybe_schedule_reminders('trial_ending');

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ppcart-post-types.php';
        $ppcart_post_types = new PPCart_Post_Types();

        if (function_exists('ppcart_cpt_slug_migration_maybe_seed_state')) {
            ppcart_cpt_slug_migration_maybe_seed_state();
        }

        $ppcart_post_types->create_custom_post_type();

        if (!ppcart_get_sensitive_option('_ppcart_api_key')) {
            $apikey = bin2hex(random_bytes(32));
            update_option('_ppcart_api_key', $apikey);
        }

        if (false === get_option('_ppcart_cashondelivery_enable', false)) {
            update_option('_ppcart_cashondelivery_enable', '1');
        }

        self::setup_tax_table();
        self::add_cap();

        do_action('ppcart_activate');

        flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- Required once on activation after registering CPTs.
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
        $editor_role = get_role('editor');
        $manager_role_slug = ppcart_live_role('cart_manager');
        $admin_role_slug   = ppcart_live_role('cart_administrator');
        $manager_option    = ppcart_live_cap('manager_option');
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.custom_role_add_role -- Plugin must define custom role during activation in non-VIP environments.
        $ppcart_manager_role = add_role($manager_role_slug, 'Cart Manager', $editor_role->capabilities);
        if (!$ppcart_manager_role) {
            $ppcart_manager_role = get_role($manager_role_slug);
        }
        // Get administrator role
        $role = get_role('administrator');
        $super_role = get_role('super');

        $post_type = [];
        foreach ([ 'product', 'order', 'subscription' ] as $family) {
            $slug = function_exists('ppcart_live_post_type') ? ppcart_live_post_type($family) : ('subscription' === $family ? 'ppcart_subscription' : 'ppcart_' . $family);
            $post_type[ $slug ] = $slug . 's';
        }
        foreach (function_exists('ppcart_query_pro_post_types') ? ppcart_query_pro_post_types('us_path') : [ 'ppcart_us_path' ] as $us_path_slug) {
            $post_type[ $us_path_slug ] = $us_path_slug . 's';
        }
        foreach ($post_type as $key => $post_data) {
            $ppcart_product_capabilities = self::compile_post_type_capabilities($key, $post_data);
            foreach ($ppcart_product_capabilities as $cap) {
                $role->add_cap($cap);
                if ($super_role) {
                    $super_role->add_cap($cap);
                }
                $ppcart_manager_role->add_cap($cap);
            }
        }
        $role->add_cap($manager_option);
        $ppcart_manager_role->add_cap($manager_option);
        if ($super_role) {
            $super_role->add_cap($manager_option);
        }
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.custom_role_add_role -- Plugin must define custom role during activation in non-VIP environments.
        add_role($admin_role_slug, 'Cart Administrator', $role->capabilities);
    }

    public static function setup_tax_table()
    {
        global $wpdb;

        $ppcart_tax_table = ppcart_live_table('tax_rate');
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $ppcart_tax_table (
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
}
