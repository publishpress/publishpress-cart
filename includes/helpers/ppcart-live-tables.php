<?php

/**
 * Canonical-only custom-table helpers.
 *
 * Companion leftover-aware implementations load first via the early include.
 *
 * @package PublishPress_Cart
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('ppcart_canonical_live_tables')) {
    /**
     * Family key => unprefixed canonical table suffix.
     *
     * @return array<string, string>
     */
    function ppcart_canonical_live_tables()
    {
        return [
            'tax_rate'       => 'ppcart_tax_rate',
            'order_items'    => 'ppcart_order_items',
            'order_itemmeta' => 'ppcart_order_itemmeta',
            'downloads'      => 'ppcart_downloads',
        ];
    }
}

if (! function_exists('ppcart_live_table')) {
    /**
     * @param string $family Family key.
     * @return string
     */
    function ppcart_live_table($family)
    {
        $map = ppcart_canonical_live_tables();
        if (! isset($map[ $family ])) {
            return '';
        }

        global $wpdb;
        $prefix = (isset($wpdb) && is_object($wpdb) && isset($wpdb->prefix)) ? $wpdb->prefix : 'wp_';

        return $prefix . $map[ $family ];
    }
}

if (! function_exists('ppcart_live_table_suffix')) {
    /**
     * @param string $family Family key.
     * @return string
     */
    function ppcart_live_table_suffix($family)
    {
        $map = ppcart_canonical_live_tables();

        return $map[ $family ] ?? '';
    }
}

if (! function_exists('ppcart_live_metadata_type')) {
    /**
     * @return string
     */
    function ppcart_live_metadata_type()
    {
        return 'ppcart_order_item';
    }
}

if (! function_exists('ppcart_live_order_item_id_column')) {
    /**
     * @return string
     */
    function ppcart_live_order_item_id_column()
    {
        return 'ppcart_order_item_id';
    }
}

if (! function_exists('ppcart_register_live_meta_table')) {
    /**
     * @return void
     */
    function ppcart_register_live_meta_table()
    {
        global $wpdb;

        if (! isset($wpdb) || ! is_object($wpdb)) {
            return;
        }

        $wpdb->ppcart_order_itemmeta = ppcart_live_table('order_itemmeta');
    }
}

if (! function_exists('ppcart_flush_live_table_cache')) {
    /**
     * @return void
     */
    function ppcart_flush_live_table_cache()
    {
        unset($GLOBALS['ppcart_live_table_cache']);
    }
}
