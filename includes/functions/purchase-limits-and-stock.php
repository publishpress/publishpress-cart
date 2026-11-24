<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Paid orders of one customer that hold a product. A Buy now order holds it as
 * its product. A cart order holds it as a main item.
 *
 * @param int                                $product_id Product ID. 0 matches every order of the customer.
 * @param array{user_id?:int,email?:string} $customer   Who to look for.
 * @param int|string                         $limit      Number of orders to return.
 * @return WP_Post[]|false False when the customer cannot be identified.
 */
function ppcart_get_customer_product_orders(int $product_id, array $customer, $limit = 1)
{
    $identity = ['relation' => 'OR'];
    $user_id = absint($customer['user_id'] ?? 0);
    if ($user_id) {
        $identity[] = ['key' => ppcart_meta_key('user_account'), 'value' => $user_id];
    }
    $email = sanitize_email((string) ($customer['email'] ?? ''));
    if ('' !== $email) {
        $identity[] = ['key' => ppcart_meta_key('email'), 'value' => $email];
    }
    $identity = apply_filters('ppcart_current_user_orders_meta_query_args', $identity, $product_id);
    if (count($identity) <= 1) {
        return false;
    }

    $args = [
        'post_type'      => ppcart_query_post_types('order'),
        'post_status'    => ['paid', 'completed'],
        'posts_per_page' => $limit,
    ];
    if (! $product_id) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Orders are found by the stored customer.
        return get_posts($args + ['meta_query' => $identity]);
    }

    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Orders are found by the stored customer and product.
    $orders = get_posts($args + ['meta_query' => ['relation' => 'AND', ['key' => ppcart_meta_key('product_id'), 'value' => $product_id], $identity]]);
    $missing = max(1, (int) $limit) - count($orders);
    if ($missing < 1) {
        return $orders;
    }
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Orders are found by the stored customer.
    return array_merge($orders, ppcart_get_shopping_cart_orders_with_item(['posts_per_page' => $missing, 'meta_query' => $identity] + $args, $product_id));
}

/**
 * The purchase limit of one product. A Buy now order is one purchase. A cart
 * order is one purchase for each line of the product.
 *
 * @param int                                $product_id Product ID.
 * @param array{user_id?:int,email?:string} $customer   The buyer. A missing user_id means the current user.
 * @param object|null                        $product    The product, when the caller already loaded it.
 * @param int                                $wanted     Purchases the buyer wants now: the lines of the product in a cart.
 */
function ppcart_check_product_purchase_limit(int $product_id, array $customer, ?object $product = null, int $wanted = 1): true|WP_Error
{
    global $wpdb;

    $product ??= ppcart_setup_product($product_id);
    if (! isset($product->customer_purchase_limit)) {
        return true;
    }
    $customer['user_id'] ??= get_current_user_id();
    $limit = max(1, (int) ($product->customer_limit ?? 1));
    $bought = 0;
    foreach (ppcart_get_customer_product_orders($product_id, $customer, $limit) ?: [] as $order) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned table.
        $lines = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE order_id = %d AND product_id = %d AND item_type = %s', ppcart_live_table('order_items'), $order->ID, $product_id, 'main'));
        $bought += max(1, $lines);
    }
    if ($bought + $wanted <= $limit) {
        return true;
    }
    if ($bought >= $limit) {
        return new WP_Error(
            'ppcart_purchase_limit_reached',
            $product->customer_limit_message ?? __('Sorry, you have already purchased this product!', 'publishpress-cart')
        );
    }
    $left = $limit - $bought;
    return new WP_Error(
        'ppcart_purchase_limit_reached',
        sprintf(
            /* translators: 1: product name, 2: number of times the customer can still buy the product. */
            _n(
                'You can buy %1$s %2$d more time. Remove the other plans of this product from your cart.',
                'You can buy %1$s %2$d more times. Remove the other plans of this product from your cart.',
                $left,
                'publishpress-cart'
            ),
            get_the_title($product_id),
            $left
        )
    );
}

/**
 * Whether a product has stock for a quantity. A product that does not manage stock always has.
 *
 * @param int|object|null $product  Product ID, or the product when the caller already loaded it.
 * @param int             $quantity Quantity wanted.
 */
function ppcart_check_product_stock($product, int $quantity = 1): bool
{
    if (is_numeric($product)) {
        $product = ppcart_setup_product((int) $product);
    }
    return ! isset($product->manage_stock) || ! ($product->limit < $quantity);
}
