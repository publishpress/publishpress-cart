<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read a single report-filter value, ignoring arrays and objects.
 *
 * Query strings like `customer[]=` or `product_id[]=` must not be cast.
 * Casting an array to string warns, and `absint()` turns a non-empty array
 * into `1`, which would filter to product ID 1 instead of All.
 *
 * @param mixed $value Raw request value.
 * @return string
 */
function ppcart_report_filter_scalar($value)
{
    if (! is_scalar($value)) {
        return '';
    }

    return (string) $value;
}

/**
 * Read and sanitize the Reports page customer/product/date filters.
 *
 * Missing or invalid values fall back to the unfiltered defaults: today's
 * date is left to the caller, an empty customer means all customers, and
 * a missing/deleted product ID means all products.
 *
 * @param array|null $source Request values. Defaults to GET.
 * @return array{date: string|null, customer: string, product_id: int}
 */
function ppcart_parse_report_filters($source = null)
{
    if (! is_array($source)) {
        $source = wp_unslash($_GET); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filters.
    }

    $date = null;
    if (array_key_exists('date', $source)) {
        $date = sanitize_text_field(ppcart_report_filter_scalar($source['date']));
    }

    $customer_raw = '';
    if (isset($source['customer'])) {
        $customer_raw = ppcart_report_filter_scalar($source['customer']);
    } elseif (isset($source['emailid'])) {
        $customer_raw = ppcart_report_filter_scalar($source['emailid']);
    }

    $customer = strtolower(sanitize_email($customer_raw));

    $product_id = isset($source['product_id']) ? absint(ppcart_report_filter_scalar($source['product_id'])) : 0;
    if ($product_id && ! ppcart_is_product_post_type(get_post_type($product_id))) {
        $product_id = 0;
    }

    return [
        'date'       => $date,
        'customer'   => $customer,
        'product_id' => $product_id,
    ];
}

/**
 * Build a meta_query fragment for customer email and primary product.
 *
 * Product matching uses `_ppcart_product_id` only. Order-bump/upsell line items
 * are not included unless they are stored as the order's primary product.
 *
 * @param array $filters Parsed report filters.
 * @return array
 */
function ppcart_get_report_meta_query($filters)
{
    $clauses = [];

    $customer = isset($filters['customer']) ? strtolower(sanitize_email((string) $filters['customer'])) : '';
    if ('' !== $customer) {
        $clauses[] = [
            'key' => ppcart_meta_key('email'),
            'value' => $customer,
        ];
    }

    $product_id = isset($filters['product_id']) ? absint($filters['product_id']) : 0;
    if ($product_id > 0) {
        $clauses[] = [
            'key' => ppcart_meta_key('product_id'),
            'value' => $product_id,
        ];
    }

    if (empty($clauses)) {
        return [];
    }

    if (count($clauses) === 1) {
        return $clauses;
    }

    return array_merge(['relation' => 'AND'], $clauses);
}

/**
 * Query args that apply the customer/product identity filters.
 *
 * @param array $filters Parsed report filters.
 * @return array
 */
function ppcart_get_report_identity_query_args($filters)
{
    $meta_query = ppcart_get_report_meta_query($filters);

    if (empty($meta_query)) {
        return [];
    }

    return [
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Report identity filters must constrain orders and subscriptions.
        'meta_query' => $meta_query,
    ];
}

/**
 * Merge identity filters onto a WP_Query argument list.
 *
 * @param array $args    Existing query args.
 * @param array $filters Parsed report filters.
 * @return array
 */
function ppcart_apply_report_filters_to_query_args($args, $filters)
{
    $identity = ppcart_get_report_identity_query_args($filters);

    if (empty($identity)) {
        return $args;
    }

    if (empty($args['meta_query']) || ! is_array($args['meta_query'])) {
        return array_merge($args, $identity);
    }

    $existing = $args['meta_query'];
    $has_relation = isset($existing['relation']);
    $existing_clauses = $has_relation ? $existing : array_merge(['relation' => 'AND'], $existing);
    if (! isset($existing_clauses['relation'])) {
        $existing_clauses['relation'] = 'AND';
    }

    foreach (ppcart_get_report_meta_query($filters) as $key => $clause) {
        if ('relation' === $key) {
            continue;
        }
        $existing_clauses[] = $clause;
    }

    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Report identity filters must constrain orders and subscriptions.
    $args['meta_query'] = $existing_clauses;

    return $args;
}

/**
 * Label for a selected Reports product filter value.
 *
 * @param int $product_id Product post ID.
 * @return string
 */
function ppcart_get_report_product_label($product_id)
{
    $product_id = absint($product_id);
    if (! $product_id || ! ppcart_is_product_post_type(get_post_type($product_id))) {
        return '';
    }

    $title = get_the_title($product_id);

    if ('' !== $title) {
        return $title;
    }

    return sprintf(
        /* translators: %d: product ID. */
        __('Product #%d', 'publishpress-cart'),
        $product_id
    );
}

/**
 * AJAX: search products for the Reports filter.
 *
 * @return void
 */
function ppcart_ajax_search_report_products()
{
    ppcart_check_ajax_referer('ppcart_search_report_products', 'nonce');

    if (! ppcart_user_can('manager_option') && ! current_user_can('manage_options')) {
        wp_send_json_error(
            [
                'message' => __('Unauthorized.', 'publishpress-cart'),
            ],
            403
        );
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Search term is read after ppcart_check_ajax_referer(); that wrapper is invisible to this sniff.
    $term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
    wp_send_json_success(ppcart_search_report_products($term, 10));
}

/**
 * Find products by title for the Reports filter.
 *
 * Results are capped so typing never loads the full product list. An empty
 * term still returns the first 10 products so the field can open like a
 * dropdown.
 *
 * @param string $term  Search fragment.
 * @param int    $limit Maximum products to return.
 * @return array<int, array{id: int, text: string}>
 */
function ppcart_search_report_products($term, $limit = 10)
{
    $limit = max(1, min(10, (int) $limit));
    $term  = trim((string) $term);

    $query_args = [
        'post_type'              => ppcart_query_post_types('product'),
        'post_status'            => ['publish', 'draft', 'private'],
        'posts_per_page'         => $limit,
        'orderby'                => 'title',
        'order'                  => 'ASC',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ];

    if ('' !== $term) {
        $query_args['s'] = $term;
    }

    $query   = new WP_Query($query_args);
    $seen    = [];
    $results = [];

    foreach ((array) $query->posts as $product) {
        $product_id = (int) $product->ID;
        $label      = ppcart_get_report_product_label($product_id);
        if ('' === $label) {
            continue;
        }

        $seen[$product_id] = true;
        $results[]         = [
            'id'   => $product_id,
            'text' => $label,
        ];
    }

    if ('' !== $term && ctype_digit($term)) {
        $by_id = absint($term);
        if ($by_id && empty($seen[$by_id])) {
            $label = ppcart_get_report_product_label($by_id);
            if ('' !== $label) {
                array_unshift(
                    $results,
                    [
                        'id'   => $by_id,
                        'text' => $label,
                    ]
                );
                $results = array_slice($results, 0, $limit);
            }
        }
    }

    return $results;
}

/**
 * Whether a customer or primary-product filter is active.
 *
 * @param array $filters Parsed report filters.
 * @return bool
 */
function ppcart_report_identity_filters_are_active($filters)
{
    if (! empty($filters['customer'])) {
        return true;
    }

    return ! empty($filters['product_id']);
}

/**
 * AJAX: search customer emails used on orders for the Reports filter.
 *
 * @return void
 */
function ppcart_ajax_search_report_customers()
{
    ppcart_check_ajax_referer('ppcart_search_report_customers', 'nonce');

    if (! ppcart_user_can('manager_option') && ! current_user_can('manage_options')) {
        wp_send_json_error(
            [
                'message' => __('Unauthorized.', 'publishpress-cart'),
            ],
            403
        );
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Search term is read after ppcart_check_ajax_referer(); that wrapper is invisible to this sniff.
    $term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
    wp_send_json_success(ppcart_search_report_customers($term, 10));
}

/**
 * Build a customer label from name and email.
 *
 * @param string $email     Customer email.
 * @param string $first_name First name.
 * @param string $last_name  Last name.
 * @return string
 */
function ppcart_format_report_customer_label($email, $first_name = '', $last_name = '')
{
    $email = strtolower(sanitize_email((string) $email));
    $name  = trim((string) $first_name . ' ' . (string) $last_name);

    if ('' === $email) {
        return $name;
    }

    if ('' === $name) {
        return $email;
    }

    return $name . ' (' . $email . ')';
}

/**
 * Label for a selected customer email, using the most recent order name.
 *
 * @param string $email Customer email.
 * @return string
 */
function ppcart_get_report_customer_label($email)
{
    $email = strtolower(sanitize_email((string) $email));

    if ('' === $email) {
        return '';
    }

    $orders = get_posts(
        [
            'post_type'      => ppcart_query_post_types('order'),
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Look up one customer name for the selected Reports filter.
            'meta_query'     => [
                [
                    'key' => ppcart_meta_key('email'),
                    'value' => $email,
                ],
            ],
        ]
    );

    if (empty($orders)) {
        return $email;
    }

    $order_id = (int) $orders[0];

    return ppcart_format_report_customer_label(
        $email,
        (string) ppcart_get_post_meta($order_id, 'firstname', true),
        (string) ppcart_get_post_meta($order_id, 'lastname', true)
    );
}

/**
 * Find customers by name or email from orders.
 *
 * Results are capped so typing never loads the full customer list.
 *
 * @param string $term  Search fragment.
 * @param int    $limit Maximum customers to return.
 * @return array<int, array{email: string, text: string}>
 */
function ppcart_search_report_customers($term, $limit = 10)
{
    global $wpdb;

    $limit = max(1, min(10, (int) $limit));
    $term  = trim((string) $term);

    if ('' === $term) {
        return [];
    }

    $like       = '%' . $wpdb->esc_like($term) . '%';
    $prefixLike = $wpdb->esc_like($term) . '%';

    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Bounded admin search of distinct order customers.
    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- ppcart_sql_in_post_types() returns a placeholder list prepared from canonical post types.
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT email.meta_value AS email,
                MAX(firstname.meta_value) AS first_name,
                MAX(lastname.meta_value) AS last_name
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} email
                ON email.post_id = p.ID AND email.meta_key = %s
            LEFT JOIN {$wpdb->postmeta} firstname
                ON firstname.post_id = p.ID AND firstname.meta_key = %s
            LEFT JOIN {$wpdb->postmeta} lastname
                ON lastname.post_id = p.ID AND lastname.meta_key = %s
            WHERE p.post_type IN (" . ppcart_sql_in_post_types('order') . ")
                AND p.post_status NOT IN (%s, %s)
                AND email.meta_value <> ''
                AND (
                    email.meta_value LIKE %s
                    OR firstname.meta_value LIKE %s
                    OR lastname.meta_value LIKE %s
                    OR CONCAT_WS(' ', firstname.meta_value, lastname.meta_value) LIKE %s
                )
            GROUP BY email.meta_value
            ORDER BY
                CASE
                    WHEN MAX(firstname.meta_value) LIKE %s THEN 0
                    WHEN MAX(lastname.meta_value) LIKE %s THEN 1
                    WHEN CONCAT_WS(' ', MAX(firstname.meta_value), MAX(lastname.meta_value)) LIKE %s THEN 2
                    WHEN email.meta_value LIKE %s THEN 3
                    ELSE 4
                END,
                MAX(lastname.meta_value) ASC,
                MAX(firstname.meta_value) ASC,
                email.meta_value ASC
            LIMIT %d",
            ppcart_meta_key('email'),
            ppcart_meta_key('firstname'),
            ppcart_meta_key('lastname'),
            'trash',
            'auto-draft',
            $like,
            $like,
            $like,
            $like,
            $prefixLike,
            $prefixLike,
            $prefixLike,
            $prefixLike,
            $limit
        )
    );
    // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $results = [];

    foreach ((array) $rows as $row) {
        $email = strtolower(sanitize_email((string) $row->email));
        if ('' === $email) {
            continue;
        }

        $results[] = [
            'email' => $email,
            'text'  => ppcart_format_report_customer_label(
                $email,
                (string) $row->first_name,
                (string) $row->last_name
            ),
        ];
    }

    return $results;
}
