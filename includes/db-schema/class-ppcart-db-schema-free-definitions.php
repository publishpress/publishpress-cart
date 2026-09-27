<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Canonical Free table schemas plus filtered extensions.
 */
final class PPCart_DB_Schema_Free_Definitions
{
    /**
     * @return PPCart_DB_Table_Schema[]
     */
    public function get_schemas()
    {
        $object_id_column = ppcart_live_order_item_id_column();

        $tax_table = ppcart_live_table('tax_rate');
        $items     = ppcart_live_table('order_items');
        $itemmeta  = ppcart_live_table('order_itemmeta');
        $downloads = ppcart_live_table('downloads');

        return [
            new PPCart_DB_Table_Schema(
                $tax_table,
                [
                    'id'                 => 'mediumint(9) NOT NULL AUTO_INCREMENT',
                    'tax_rate_country'   => 'tinytext NOT NULL',
                    'tax_rate_state'     => 'tinytext NOT NULL',
                    'tax_rate_postcode'  => 'tinytext NOT NULL',
                    'tax_rate_city'      => 'tinytext NOT NULL',
                    'tax_rate'           => 'tinytext NOT NULL',
                    'tax_rate_title'     => 'tinytext NULL',
                    'tax_rate_priority'  => 'mediumint(9) NULL',
                    'tax_rate_meta'      => 'longtext NULL',
                ],
                [
                    'PRIMARY' => [
                        'columns' => [ 'id' ],
                        'unique'  => true,
                    ],
                ],
                __('Tax rates', 'publishpress-cart')
            ),
            new PPCart_DB_Table_Schema(
                $items,
                [
                    'order_item_id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
                    'order_id'      => 'bigint(20) NOT NULL',
                    'product_id'    => 'bigint(20) NOT NULL',
                    'price_id'      => 'varchar(255) NOT NULL',
                    'item_type'     => 'text',
                    'product_name'  => 'text',
                    'price_name'    => 'text',
                    'total_amount'  => 'text',
                    'tax_amount'    => 'text',
                ],
                [
                    'PRIMARY' => [
                        'columns' => [ 'order_item_id' ],
                        'unique'  => true,
                    ],
                ],
                __('Order items', 'publishpress-cart')
            ),
            new PPCart_DB_Table_Schema(
                $itemmeta,
                [
                    'meta_id'            => 'bigint(20) NOT NULL AUTO_INCREMENT',
                    $object_id_column    => 'bigint(20) NOT NULL',
                    'meta_key'           => 'varchar(255)',
                    'meta_value'         => 'longtext',
                ],
                [
                    'PRIMARY' => [
                        'columns' => [ 'meta_id' ],
                        'unique'  => true,
                    ],
                ],
                __('Order item meta', 'publishpress-cart')
            ),
            new PPCart_DB_Table_Schema(
                $downloads,
                [
                    'download_id'         => 'bigint(20) NOT NULL AUTO_INCREMENT',
                    'file_id'             => 'varchar(20) NOT NULL',
                    'order_id'            => 'bigint(20) NOT NULL',
                    'order_key'           => 'varchar(64) NOT NULL',
                    'product_id'          => 'bigint(20) NOT NULL',
                    'access_granted'      => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                    'download_expires'    => 'datetime',
                    'downloads_remaining' => 'varchar(20)',
                    'downloads'           => 'varchar(2000)',
                ],
                [
                    'PRIMARY'   => [
                        'columns' => [ 'download_id' ],
                        'unique'  => true,
                    ],
                    'order_key' => [
                        'columns' => [ 'order_key' ],
                        'unique'  => true,
                    ],
                ],
                __('Downloads', 'publishpress-cart')
            ),
        ];
    }
}
