<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Order_Lookups
{
    public function get_items()
    {
        if ($items = PPCart_Order_Item::get_order_items($this->id)) {
            return $items;
        } else {
            return false;
        }
    }

    public static function get_by_trans_id($transaction_id)
    {
        $args = [
            'post_type'  => ppcart_query_post_types('order'),
            'post_status' => 'any',
            'posts_per_page' => 1,
          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Querying by transaction meta key.
            'meta_query' => [
                [
                    'key' => ppcart_meta_key('transaction_id'),
                    'value' => $transaction_id,
                ],
            ],
        ];
        $posts = get_posts($args);
        if (empty($posts)) {
            return false;
        } else {
            $post_id = $posts[0]->ID;
            return new self($post_id);
        }
    }

    public static function child_of($id, $type = 'upsell')
    {
        return apply_filters('ppcart_order_child_of', false, $id, $type);
    }

    public function trigger_integrations()
    {
        $order = $this->get_data();
        ppcart_trigger_integrations($this->status, $order);
    }

    public function find_user_id()
    {

        if ($this->user_account) {
            return $this->user_account;
        } elseif ($user_id = email_exists($this->email)) {
            return $user_id;
        } else {
            return false;
        }
    }

    public static function get_current_user_orders($limit = 1, $product_id = 0)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-lookups-get-current-user-orders.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public static function get_meta_value($order_id, $key)
    {
        return ppcart_get_post_meta($order_id, $key, true);
    }

    public function get_children($return_obj = false)
    {
        $data = false;
        if ($children = ppcart_get_post_meta($this->id, 'order_child')) {
            $data = [];
            foreach ($children as $child) {
                if (is_numeric($child)) {
                    $child = ['id' => $child];
                }

                $child = new PPCart_Order($child['id']);
                $child = apply_filters('ppcart_order', $child);

                if ($return_obj) {
                    $data[] = $child;
                } else {
                    $data[] = $child->get_data();
                }
            }
        }
        return $data;
    }

    public function get_upsell($offer = '')
    {
        return apply_filters('ppcart_order_get_upsell', false, $this, $offer);
    }

    public function get_downsell()
    {
        return apply_filters('ppcart_order_get_downsell', false, $this);
    }

    public function get_subscription()
    {
        if ($this->subscription_id) {
            $sub = new PPCart_Subscription($this->subscription_id);
            if ($sub->id) {
                return $sub;
            }
        }
        return false;
    }

    public function get_status()
    {
        return PPCart_Status_Labels::get($this->status);
    }

    public function get_data()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-lookups-get-data.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
