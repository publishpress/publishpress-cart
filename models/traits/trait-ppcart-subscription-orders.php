<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Subscription_Orders
{
    public function get_data()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-orders-get-data.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function first_order()
    {
        if (!$this->id) {
            return false;
        }

        $first_order = false;

        if (!$this->first_order) {
            $first_order = $this->orders(1);
            if (is_array($first_order) && isset($first_order[0]) && is_object($first_order[0])) {
                $first_order = $first_order[0];
                $this->first_order = $first_order->id;
            }
        } else {
            $first_order = new PPCart_Order($this->first_order);
            if (!$first_order) {
                $first_order = $this->orders(1);
                if (is_array($first_order) && isset($first_order[0]) && is_object($first_order[0])) {
                    $first_order = $first_order[0];
                    $this->first_order = $first_order->id;
                }
            }
        }
        return $first_order;
    }

    public function last_order($status = 'any')
    {
        if (!$this->id) {
            return false;
        }
        $orders = $this->orders(1, $status, $order = 'DESC');

        return (is_array($orders) && isset($orders[0])) ? $orders[0] : false;
    }

    public function new_order()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/subscription-orders-new-order.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function orders($limit = -1, $status = 'any', $order = 'ASC')
    {
        $orders = [];
        $args = [
            'post_type'  => ppcart_query_post_types('order'),
            'post_status' => $status,
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- This admin/report selector intentionally loads all matching records for aggregate calculations or option lists.
            'posts_per_page' => -1,
            'order' => $order,
          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Querying orders linked to this subscription.
            'meta_query' => [
                [
                    'key' => ppcart_meta_key('subscription_id'),
                    'value' => $this->id,
                ],
            ],
        ];
        $posts = get_posts($args);
        if ($posts) {
            foreach ($posts as $post) {
                $orders[] = new PPCart_Order($post->ID);
            }
        }
        return $orders;
    }

    public function order_count($status = false)
    {
        if (!$this->count_orders) {
            $this->count_orders = count($this->orders());
        }

        if ($status) {
            return count($this->orders($limit = -1, $status = 'paid'));
        } else {
            return (int) $this->count_orders;
        }
    }

    public function get_status()
    {
        return PPCart_Status_Labels::get($this->status);
    }
}
