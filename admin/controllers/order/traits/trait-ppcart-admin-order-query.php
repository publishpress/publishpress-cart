<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Order_Query_Trait
{
    /** @var string Query parameter that selects the "Partially Refunded" view. */
    private static $partially_refunded_param = 'ppcart_refund_status';

    /** @var string Query parameter value for the "Partially Refunded" view. */
    private static $partially_refunded_value = 'partial';

    /** @var array<string, int[]> Partially refunded order IDs by post type, for this request. */
    private $partially_refunded_ids = [];

    public function order_related_orders_filter($query)
    {
        if (! is_admin() || ! $query->is_main_query()) {
            return $query;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter query parameter.
        $subscription_related_orders = isset($_GET['subscription_related_orders']) ? sanitize_text_field(wp_unslash($_GET['subscription_related_orders'])) : '';
        if ('' === $subscription_related_orders) {
            return $query;
        }

        $custom_meta = [
            'key' => ppcart_meta_key('subscription_id'),
            'value' => $subscription_related_orders,
            'compare' => '==',
        ];
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to constrain admin orders list to a subscription.
        $query->set('meta_query', [$custom_meta]);

        return $query;
    }

    public function order_email_filter($query)
    {

        if (! is_admin() || ! $query->is_main_query()) {
            return $query;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter query parameter.
        $order_email = isset($_GET['order_email']) ? sanitize_text_field(wp_unslash($_GET['order_email'])) : '';
        if ('' === $order_email) {
            return $query;
        }

        $custom_meta = [
            'key' => ppcart_meta_key('email'),
            'value' => $order_email,
            'compare' => '==',
        ];
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to constrain admin orders list to customer email.
        $query->set('meta_query', [$custom_meta]);

        return $query;
    }

    /**
     * Add a "Partially Refunded" view after the Refunded view on the Orders list.
     *
     * @param array $views Status view links keyed by status.
     * @return array
     */
    public function order_partially_refunded_view($views)
    {
        global $typenow;

        $post_type = (string) $typenow;
        if (! ppcart_is_order_post_type($post_type) || ! class_exists('PPCart_Order_Refunds')) {
            return $views;
        }

        $count   = count($this->get_partially_refunded_order_ids($post_type));
        $current = $this->is_partially_refunded_view();
        if (0 === $count && ! $current) {
            return $views;
        }

        $url = add_query_arg(
            [
                'post_type'                     => $post_type,
                self::$partially_refunded_param => self::$partially_refunded_value,
            ],
            admin_url('edit.php')
        );

        $label = sprintf(
            /* translators: %s: number of partially refunded orders. */
            _n(
                'Partially Refunded <span class="count">(%s)</span>',
                'Partially Refunded <span class="count">(%s)</span>',
                $count,
                'publishpress-cart'
            ),
            number_format_i18n($count)
        );

        $link = sprintf(
            '<a href="%1$s"%2$s>%3$s</a>',
            esc_url($url),
            $current ? ' class="current" aria-current="page"' : '',
            wp_kses($label, [ 'span' => [ 'class' => true ] ])
        );

        $key      = 'ppcart_partially_refunded';
        $position = array_search('ppcart_refunded', array_keys($views), true);
        if (false === $position) {
            $views[ $key ] = $link;
            return $views;
        }

        return array_slice($views, 0, $position + 1, true)
            + [ $key => $link ]
            + array_slice($views, $position + 1, null, true);
    }

    /**
     * Limit the Orders list to partially refunded orders.
     *
     * @param WP_Query $query Main admin query.
     * @return WP_Query
     */
    public function order_partially_refunded_filter($query)
    {
        if (! is_admin() || ! $query->is_main_query() || ! $this->is_partially_refunded_view()) {
            return $query;
        }

        $post_type = $query->get('post_type');
        if (! is_string($post_type) || ! ppcart_is_order_post_type($post_type)) {
            return $query;
        }

        $ids = $this->get_partially_refunded_order_ids($post_type);
        // post__in with [0] matches nothing, so an empty view lists no orders.
        $query->set('post__in', $ids ? $ids : [ 0 ]);

        return $query;
    }

    /**
     * Whether the request asks for the "Partially Refunded" view.
     *
     * @return bool
     */
    private function is_partially_refunded_view()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list view parameter.
        $value = isset($_GET[ self::$partially_refunded_param ]) ? sanitize_key(wp_unslash($_GET[ self::$partially_refunded_param ])) : '';

        return self::$partially_refunded_value === $value;
    }

    /**
     * Partially refunded order IDs, computed once per controller and post type.
     *
     * @param string $post_type Order post type.
     * @return int[]
     */
    private function get_partially_refunded_order_ids($post_type)
    {
        if (! isset($this->partially_refunded_ids[ $post_type ])) {
            $this->partially_refunded_ids[ $post_type ] = class_exists('PPCart_Order_Refunds')
                ? PPCart_Order_Refunds::get_partially_refunded_order_ids($post_type)
                : [];
        }

        return $this->partially_refunded_ids[ $post_type ];
    }
}
