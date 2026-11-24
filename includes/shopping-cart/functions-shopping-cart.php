<?php

if (! defined('ABSPATH')) {
    exit;
}

function ppcart_product_shows_in_shop(int $product_id): bool
{
    return 'no' !== get_post_meta($product_id, '_ppcart_show_in_shop', true);
}

function ppcart_render_shop($attributes = []): string
{
    ppcart_shopping_cart_assets();
    return (new PPCart_Shop())->render((array) $attributes);
}

function ppcart_get_shopping_cart(): PPCart_Shopping_Cart_Priced
{
    return (new PPCart_Shopping_Cart_Store())->read();
}

/**
 * Store an extension value on the current visitor's cart. A null value removes the key.
 * Keys that start with an underscore are reserved.
 */
function ppcart_shopping_cart_set_meta(string $key, mixed $value): PPCart_Shopping_Cart_Priced|WP_Error
{
    return (new PPCart_Shopping_Cart_Store())->update(static fn (PPCart_Shopping_Cart $cart): true|WP_Error => $cart->set_meta($key, $value));
}

/**
 * Query the cart orders that hold a product as a main item.
 *
 * @param array    $query_args get_posts() arguments. A meta_query is combined with the cart order clause.
 * @param string[] $plan_ids   Limit the match to these plans of the product.
 * @return WP_Post[]|int[]
 */
function ppcart_get_shopping_cart_orders_with_item(array $query_args, int $product_id, array $plan_ids = []): array
{
    global $wpdb;

    $sql = 'SELECT order_id FROM %i WHERE product_id = %d AND item_type = %s';
    $values = [ppcart_live_table('order_items'), $product_id, 'main'];
    if ($plan_ids) {
        $sql .= ' AND price_id IN (' . implode(',', array_fill(0, count($plan_ids), '%s')) . ')';
        $values = [...$values, ...array_values($plan_ids)];
    }
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- The SQL holds only placeholders, one per value.
    $items = $wpdb->prepare($sql, $values);
    $where = static fn (string $clauses): string => $clauses . " AND {$wpdb->posts}.ID IN ({$items})";

    $cart_order = ['key' => ppcart_meta_key('checkout_type'), 'value' => 'cart'];
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- A cart order is identified by its checkout type.
    $query_args['meta_query'] = isset($query_args['meta_query']) ? ['relation' => 'AND', $cart_order, $query_args['meta_query']] : [$cart_order];
    add_filter('posts_where', $where);
    try {
        return get_posts(['suppress_filters' => false] + $query_args);
    } finally {
        remove_filter('posts_where', $where);
    }
}

function ppcart_shopping_cart_assets(): void
{
    wp_enqueue_style('ppcart-shopping-cart', PPCART_BASE_URL . 'public/css/ppcart-shopping-cart.css', [], PPCART_VERSION);
    wp_enqueue_script('ppcart-shopping-cart', PPCART_BASE_URL . 'public/js/ppcart-shopping-cart.js', [], PPCART_VERSION, true);
    wp_localize_script('ppcart-shopping-cart', 'ppcartShoppingCart', [
        'restUrl' => esc_url_raw(rest_url('ppcart/v1/cart')),
        'nonce' => wp_create_nonce('wp_rest'),
        'add' => __('Add to cart', 'publishpress-cart'),
        'inCart' => __('✓ In cart', 'publishpress-cart'),
        'added' => __('Added to your cart.', 'publishpress-cart'),
        'removed' => __('Removed from your cart.', 'publishpress-cart'),
        'already' => __('Already in your cart.', 'publishpress-cart'),
        'error' => __('The cart could not be updated. Please try again.', 'publishpress-cart'),
    ]);
}

function ppcart_shopping_cart_style(array $attributes): string
{
    $settings = $attributes['styleSettings'] ?? $attributes['stylesettings'] ?? [];
    $style = '';
    foreach (ppcart_block_style_tokens(is_array($settings) ? $settings : []) as $key => $value) {
        $style .= $key . ':' . $value . ';';
    }
    return $style;
}

function ppcart_render_mini_cart($attributes = []): string
{
    if (! (new PPCart_Shopping_Cart_Settings())->enabled()) {
        return '';
    }
    ppcart_shopping_cart_assets();
    $attributes = array_change_key_case((array) $attributes, CASE_LOWER);
    foreach (['showcount' => true, 'showtotal' => false, 'opensdrawer' => true] as $key => $default) {
        $attributes[$key] = filter_var($attributes[$key] ?? $default, FILTER_VALIDATE_BOOLEAN);
    }
    return ppcart_get_template('shopping-cart/mini-cart', '', [
        'attributes' => $attributes,
        'style' => ppcart_shopping_cart_style($attributes),
        'links' => (new PPCart_Shopping_Cart_Settings())->urls(),
    ]);
}

function ppcart_shopping_cart_nocache(): void
{
    if (! defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }
    if (! headers_sent()) {
        nocache_headers();
    }
}

function ppcart_render_shopping_cart($attributes = []): string
{
    ppcart_shopping_cart_nocache();
    ppcart_shopping_cart_assets();
    return (new PPCart_Shopping_Cart_View())->render('cart', ppcart_get_shopping_cart(), (array) $attributes);
}
