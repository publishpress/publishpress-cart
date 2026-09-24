<?php

if (! defined('ABSPATH')) {
    exit;
}


class PPCart_Admin_Filters
{
    private $meta_filters;

    public function __construct($meta_filters)
    {

        add_action('restrict_manage_posts', [$this, 'subscriptions_type_filter']);
        add_action('pre_get_posts', [$this, 'apply_subscription_type_filter']);

        add_action('restrict_manage_posts', [$this, 'order_type_filter']);
        add_action('pre_get_posts', [$this, 'apply_order_type_filter']);

        $this->meta_filters = $meta_filters;
        add_action('restrict_manage_posts', [$this, 'custom_posts_filter']);
        add_action('pre_get_posts', [$this, 'apply_custom_posts_filter_with_meta']);
        add_filter('posts_search', [$this, 'custom_posts_search'], 10, 2); // Add filter for custom search
    }

    // Display custom posts filter dropdown
    public function subscriptions_type_filter()
    {
        global $typenow, $wpdb;
        // Check if we're on the subscriptions page
        if (ppcart_is_subscription_post_type($typenow)) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter parameter.
            $selected = isset($_GET['subscription_type']) ? sanitize_key(wp_unslash($_GET['subscription_type'])) : '';

            // Output the dropdown
            ?>
            <select name="subscription_type" id="subscription_type" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-subscription-type-filter')); ?>">
                <option value="">All types</option>
                <option value="ongoing" <?php selected($selected, 'ongoing'); ?>>Ongoing Subscriptions</option>
                <option value="installments" <?php selected($selected, 'installments'); ?>>Payment Plans</option>
                <option value="pending_cancellation" <?php selected($selected, 'pending_cancellation'); ?>>Pending Cancellation</option>
            </select>
            <?php
        }
    }

    // Apply sub filter when the dropdown value is set
    public function apply_subscription_type_filter($query)
    {
        global $pagenow, $typenow;

        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter parameter.
        $subscription_type = isset($_GET['subscription_type']) ? sanitize_key(wp_unslash($_GET['subscription_type'])) : '';

        // Check if we're on the subscriptions page and the filter value is set
        if ($pagenow == 'edit.php' && ppcart_is_subscription_post_type($typenow) && '' !== $subscription_type) {
            // Add meta query based on the selected subscription type
            if ($subscription_type == 'ongoing') {
                $query->set('meta_key', ppcart_meta_key('sub_installments'));
                $query->set('meta_value', '-1');
            } elseif ($subscription_type == 'installments') {
                $query->set('meta_key', ppcart_meta_key('sub_installments'));
                $query->set('meta_value', 1);
                $query->set('meta_compare', '>');
            } elseif ($subscription_type == 'pending_cancellation') {
                $query->set('meta_query', [
                    [
                        'key' => ppcart_meta_key('cancel_date'),
                        'compare' => 'EXISTS',
                    ],
                    [
                        'key' => ppcart_meta_key('status'),
                        'value' => 'canceled',
                        'compare' => '!=',
                    ],
                ]);
            }
        }
    }

    // Display custom posts filter dropdown
    public function order_type_filter()
    {
        global $typenow, $wpdb;
        // Check if we're on the subscriptions page
        if (ppcart_is_order_post_type($typenow)) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter parameter.
            $selected = isset($_GET['order_type']) ? sanitize_key(wp_unslash($_GET['order_type'])) : '';

            // Output the dropdown
            ?>
            <select name="order_type" id="order_type" data-testid="<?php echo esc_attr(ppcart_testid('ppcart-admin-order-type-filter')); ?>">
                <option value="">All types</option>
                <option value="orders" <?php selected($selected, 'orders'); ?>>Initial orders</option>
                <option value="renewals" <?php selected($selected, 'renewals'); ?>>Renewals</option>
            </select>
            <?php
        }
    }

    // Apply sub filter when the dropdown value is set
    public function apply_order_type_filter($query)
    {
        global $pagenow, $typenow;

        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter parameter.
        $order_type = isset($_GET['order_type']) ? sanitize_key(wp_unslash($_GET['order_type'])) : '';

        // Check if we're on the subscriptions page and the filter value is set
        if ($pagenow == 'edit.php' && ppcart_is_order_post_type($typenow) && '' !== $order_type) {
            // Add meta query based on the selected subscription type
            if ($order_type == 'renewals') {
                $query->set('meta_key', ppcart_meta_key('renewal_order'));
                $query->set('meta_value', '1');
            } else {
                $query->set('meta_query', [
                    [
                        'key' => ppcart_meta_key('renewal_order'),
                        'compare' => 'NOT EXISTS',
                    ],
                ]);
            }
        }
    }

    // Display custom posts filter dropdown
    public function custom_posts_filter()
    {
        global $typenow, $wpdb;
        foreach ($this->meta_filters as $meta_filter) {
            if (in_array($typenow, $meta_filter['post_types'])) {
                $meta_key = $meta_filter['key'];
                $default_option = $meta_filter['default_option'] ?? 'All ' . $meta_key;

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin filter options are built from distinct meta values at runtime.
                $meta_values = $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY meta_value ASC",
                    $meta_key
                ));

                if ($meta_values) {
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter parameter.
                    $current_meta_value = isset($_GET[$meta_key]) ? sanitize_text_field(wp_unslash($_GET[$meta_key])) : '';

                    echo '<select name="' . esc_attr($meta_key) . '" data-testid="' . esc_attr(ppcart_testid('ppcart-admin-meta-filter-' . $meta_key)) . '">';
                    echo '<option value="">' . esc_html($default_option) . '</option>'; // Default option
                    foreach ($meta_values as $meta_value) {
                        echo '<option value="' . esc_attr($meta_value) . '"';
                        if ($current_meta_value == $meta_value) {
                            echo ' selected="selected"';
                        }
                        if ($meta_key == ppcart_meta_key('pay_method')) {
                            $meta_value = ucwords(str_replace('_', ' ', $meta_value));
                        }
                        echo '>' . esc_html($meta_value) . '</option>';
                    }
                    echo '</select>';
                }
            }
        }
    }

    // Apply custom posts filter with meta
    public function apply_custom_posts_filter_with_meta($query)
    {
        global $pagenow, $typenow;

        if (! is_admin() || ! $query->is_main_query()) {
            return;
        }

        foreach ($this->meta_filters as $meta_filter) {
            if (in_array($typenow, $meta_filter['post_types'])) {
                $meta_key = $meta_filter['key'];

                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin list filter parameter.
                $filter_value = isset($_GET[$meta_key]) ? sanitize_text_field(wp_unslash($_GET[$meta_key])) : '';

                if ('' !== $filter_value) {
                    $query->set('meta_key', $meta_key);
                    $query->set('meta_value', $filter_value);
                }
            }
        }
    }

    // Custom posts search
    public function custom_posts_search($search, $wp_query)
    {
        global $wpdb;

        // Check if it's the admin and if we are searching
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin search query parameter.
        if (is_admin() && $wp_query->is_search && isset($_GET['s'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin search query parameter.
            $search_term = sanitize_text_field(wp_unslash($_GET['s']));
            $search = '';

            // Add the custom meta field to the search query
            if (!empty($search_term)) {
                $like_term = '%' . $wpdb->esc_like($search_term) . '%';
                // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- ppcart_sql_in_meta_keys() returns a placeholder list prepared from canonical meta keys.
                $search .= $wpdb->prepare(
                    " AND (
                        {$wpdb->posts}.post_title LIKE %s
                        OR {$wpdb->posts}.post_content LIKE %s
                        OR EXISTS (
                            SELECT * FROM {$wpdb->postmeta}
                            WHERE post_id = {$wpdb->posts}.ID
                            AND (meta_key IN (" . ppcart_sql_in_meta_keys('email') . ") AND meta_value LIKE %s)
                        )
                    ) ",
                    $like_term,
                    $like_term,
                    $like_term
                );
                // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
            }
        }

        return $search;
    }
}

// Usage:
$post_types = array_merge(ppcart_query_post_types('subscription'), ppcart_query_post_types('order'), ppcart_query_pro_post_types('collection'));
$meta_filters = [
    [
        'key' => ppcart_meta_key('pay_method'),
        'default_option' => 'All Payment Methods',
        'post_types' => $post_types,
    ],
    [
        'key' => ppcart_meta_key('product_name'),
        'default_option' => 'All Products',
        'post_types' => $post_types,
    ],
];

new PPCart_Admin_Filters($meta_filters, $post_types);
