<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Files_Order_Trait
{
    public function process_refund($status, $order_data, $order_type = 'main')
    {
        global $wpdb;
        $args['downloads_remaining'] = '0';
        $args['download_expires'] = ppcart_localize_dt()->format('Y-m-d H:i:s');
        $downloads = $this->get_order_downloads($order_data['id'], ['status' => 'all']);
        if (is_countable($downloads)) {
            foreach ($downloads as $download) {
                if ($download->product_id == $order_data['product_id']) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Marks existing downloads as revoked for refunded item.
                    $wpdb->update(self::live_table(), $args, [ 'download_id' => $download->download_id ], [ '%s', '%s' ], [ '%d' ]);
                }
            }
        }
    }

    public function attach_downloads_to_order($order)
    {

        global $wpdb;

        $items = $order->get_items();

        foreach ($items as $item) {
            $product_id = $item->product_id;
            $order_ids = [$item->price_id,$item->item_type,$order->order_type];
            $files = $this->get_product_files($product_id);

            if (is_countable($files)) {
                foreach ($files as $file) {
                    //payment plan
                    $file_plan_ids = (isset($file['file_plan'])) ? (array) $file['file_plan'] : [];

                    if (ppcart_match_plan($file_plan_ids, $order_ids)) {
                        try {
                            $order_key = bin2hex(random_bytes(32));
                        } catch (Exception $e) {
                            // Fallback to WP CSPRNG-backed helper when random_bytes is unavailable.
                            $order_key = wp_generate_password(64, false, false);
                        }

                        $args = [
                            'file_id' => $file['file_id'],
                            'order_id' => $order->id,
                            'order_key' => $order_key,
                            'product_id' => $product_id,
                            'download_expires' => null,
                            'downloads_remaining' => $file['file_limit'] ?? 'unlimited',
                        ];

                        $args = apply_filters('ppcart_file_download_db_args', $args, $file, $order);

                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Inserts download access row for purchased file.
                        $wpdb->insert(self::live_table(), $args);
                        $file_id = $wpdb->insert_id;
                        do_action('ppcart_after_downloads_attached_to_order', $file_id, $args);
                    }
                }
            }
        }
    }
}
