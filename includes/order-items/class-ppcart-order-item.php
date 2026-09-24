<?php

if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class PPCart_Order_Item
{
    /**
     * The order items table name.
     *
     * @since 1.0.0
     * @access public
     * @var string    $table_name    The order items table name.
     */
    public $table_name = 'ppcart_order_items';

    /**
     * The order items meta table name.
     *
     * @since 1.0.0
     * @access public
     * @var string    $table_name    The order items meta table name.
     */
    public $meta_table_name = 'ppcart_order_itemmeta';

    public $id;
    public $order_id;
    public $product_id;
    public $price_id;
    public $item_type;
    public $product_name;
    public $price_name;
    public $total_amount;
    public $tax_amount;
    public $unit_price;
    public $quantity;
    public $subtotal;
    public $discount_amount;
    public $shipping_amount;
    public $sign_up_fee;
    public $trial_days;
    public $tax_rate;
    public $tax_desc;
    public $purchase_note;

    protected $attrs;
    protected $cols;
    protected $meta;
    protected $defaults;

    public function __construct($obj = null)
    {
        if (function_exists('ppcart_live_table_suffix')) {
            $this->table_name = ppcart_live_table_suffix('order_items');
            $this->meta_table_name = ppcart_live_table_suffix('order_itemmeta');
        }
        include __DIR__ . '/templates/ppcart-order-item---construct.php';
    }

    public function initialize($defaults, $meta, $obj = null)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-order-item-initialize.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function store()
    {
        if ($this->id) {
            $this->update();
        } else {
            $this->create();
        }
        return $this->id;
    }

    public function create()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/ppcart-order-item-create.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function update()
    {
        global $wpdb;

        $args = [];
        foreach ($this->cols as $col) {
            $args[$col] = $this->$col;
        }

        $updated = $wpdb->update(ppcart_live_table('order_items'), $args, [ 'order_item_id' => $this->id ]); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Update plugin custom table.

        if (false !== $updated) {
            foreach ($this->meta as $key) {
                if (isset($this->$key) && $this->$key) {
                    $this->update_meta($key, $this->$key);
                }
            }
        }
    }

    public function get_meta($meta_key = '', $single = true)
    {

        if (!$this->id) {
            return false;
        }

        return get_metadata(ppcart_live_metadata_type(), $this->id, $meta_key, $single);
    }

    public function update_meta($meta_key, $value = '')
    {

        if (!$this->id) {
            return false;
        }

        return update_metadata(ppcart_live_metadata_type(), $this->id, $meta_key, $value);
    }

    public function add_meta($meta_key, $value = '')
    {

        if (!$this->id) {
            return false;
        }

        return add_metadata(ppcart_live_metadata_type(), $this->id, $meta_key, $value);
    }

    public function delete_meta($meta_key = '')
    {

        if (!$this->id) {
            return false;
        }

        return delete_metadata(ppcart_live_metadata_type(), $this->id, $meta_key);
    }

    public static function get_order_items($order_id)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-item-get-order-items.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function delete_item()
    {

        global $wpdb;

        if (!$this->id) {
            return false;
        }

        $keys = $this->get_meta();
        foreach ($keys as $key => $val) {
            $this->delete_meta($key);
        }

        return $wpdb->delete(ppcart_live_table('order_items'), [ 'order_item_id' => $this->id ], [ '%d' ]); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Delete from plugin custom table.
    }

    private function get_item($id = 0)
    {

        global $wpdb;

        if (!$id) {
            return false;
        }

        $table_name = ppcart_live_table('order_items');

        return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read from plugin custom table.
            $wpdb->prepare('SELECT * FROM %i WHERE order_item_id = %d', $table_name, $id)
        )[0];
    }

    public function get_data()
    {

        if ($this->id === false) {
            return false;
        }

        $data = ['id' => $this->id];
        foreach ($this->attrs as $key) {
            $data[$key] = $this->$key;
        }

        return $data;
    }
}
