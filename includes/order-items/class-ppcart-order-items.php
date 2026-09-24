<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The file download specific functionality of the plugin.
 *
 * @link https://publishpress.com/
 * @since 1.0.0
 *
 * @package PPCart
 * @subpackage PPCart/files
 */

class PPCart_Order_Items
{
    /**
     * The order items table name.
     *
     * @since 1.0.0
     * @access private
     * @var string    $table_name    The order items table name.
     */
    private static $table_name = 'ppcart_order_items';

    /**
     * The order items meta table name.
     *
     * @since 1.0.0
     * @access private
     * @var string    $table_name    The order items meta table name.
     */
    private static $meta_table_name = 'ppcart_order_itemmeta';

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string    $plugin_name       The name of this plugin.
     * @param string    $version    The version of this plugin.
     */
    public function __construct()
    {

        $this->initialize();
    }

    public function init()
    {
    }

    public function initialize()
    {

        ppcart_register_live_meta_table();
        add_action('ppcart_activate', [$this, 'setup_items_table']);
        add_action('ppcart_upgrade', [$this, 'setup_items_table']);
        $this->maybe_setup_items_table();

        require_once plugin_dir_path(__FILE__) . 'class-ppcart-order-item.php';
    }

    /**
     * Create order-item tables when they are missing (git deploys skip WP's upgrader).
     */
    private function maybe_setup_items_table()
    {
        global $wpdb;

        if (! isset($wpdb) || ! is_object($wpdb)) {
            return;
        }

        $table = ppcart_live_table('order_items');
        if ('' === $table) {
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema probe for a plugin-owned table.
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
        if ($found === $table) {
            return;
        }

        $this->setup_items_table();
    }

    public function setup_items_table()
    {
        global $wpdb;

        $ppcart_order_items_table = ppcart_live_table('order_items');
        $ppcart_order_itemmeta_table = ppcart_live_table('order_itemmeta');
        $object_id_column = ppcart_live_order_item_id_column();
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE IF NOT EXISTS $ppcart_order_items_table (
			order_item_id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
			product_id bigint(20) NOT NULL,
            price_id varchar(255) NOT NULL,
            item_type text,
            product_name text,
            price_name text,
			total_amount text,
			tax_amount text,
			PRIMARY KEY (order_item_id)
		  ) $charset_collate;";
        dbDelta($sql);

        $sql = "CREATE TABLE IF NOT EXISTS $ppcart_order_itemmeta_table (
			meta_id	bigint(20) NOT NULL AUTO_INCREMENT,
			{$object_id_column} bigint(20) NOT NULL,
			meta_key varchar(255),
			meta_value longtext,
			PRIMARY KEY (meta_id)
		  ) $charset_collate;";
        dbDelta($sql);
        ppcart_flush_live_table_cache();
        ppcart_register_live_meta_table();
    }
}
