<?php

if (! defined('ABSPATH')) {
    exit;
}

const PPCART_CHECKOUT_TYPE_CART = 'cart';

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
 * Whether an order came from cart checkout. This is the only test for it:
 * an empty product_id never means a cart order.
 *
 * @param array|object $order Order data, a PPCart_Order, or a ppcart_setup_order() result.
 */
function ppcart_order_is_shopping_cart(array|object $order): bool
{
    return PPCART_CHECKOUT_TYPE_CART === (is_array($order) ? ($order['checkout_type'] ?? null) : ($order->checkout_type ?? null));
}

/**
 * The plan of a cart order. A cart order has no product plan, so code that reads
 * the order plan gets this one-time plan for the whole order.
 *
 * @param array $order_data Order data with checkout_type, item_name and amount.
 */
function ppcart_shopping_cart_order_plan(array $order_data): ?object
{
    if (! ppcart_order_is_shopping_cart($order_data)) {
        return null;
    }
    $total = (float) ($order_data['amount'] ?? 0);
    return (object) [
        'type'            => 'one-time',
        'option_id'       => 'cart',
        'name'            => (string) ($order_data['item_name'] ?? ''),
        'stripe_id'       => '',
        'price'           => $total,
        'initial_payment' => $total,
    ];
}

/**
 * One context for each main item of a cart order: the order data with the
 * product fields of that item. A context is input for per-product work only.
 * It is never stored as an order and never passed to an order status hook.
 *
 * @param array $order_data Data of the cart order.
 * @return list<array>
 */
function ppcart_shopping_cart_order_contexts(array $order_data): array
{
    $contexts = [];
    foreach (PPCart_Order_Item::get_order_items($order_data['ID'] ?? 0) ?: [] as $item) {
        if ('main' !== $item->item_type) {
            continue;
        }
        $contexts[] = array_merge($order_data, [
            'product_id'            => (int) $item->product_id,
            'option_id'             => $item->price_id,
            'plan_id'               => $item->price_id,
            'amount'                => (float) $item->total_amount,
            'quantity'              => (int) $item->quantity,
            'product_name'          => $item->product_name,
            'item_name'             => $item->price_name,
            'order_type'            => 'main',
            'checkout_type'         => PPCART_CHECKOUT_TYPE_CART,
            'shopping_cart_item_id' => (int) $item->id,
        ]);
    }
    return $contexts;
}

/**
 * Revenue of a cart order for each product it holds. Empty for a Buy now order.
 *
 * @return array<int,float>
 */
function ppcart_shopping_cart_order_revenue(PPCart_Order $order): array
{
    $revenue = [];
    if (! ppcart_order_is_shopping_cart($order)) {
        return $revenue;
    }
    foreach ($order->get_items() ?: [] as $item) {
        if ('main' === $item->item_type) {
            $revenue[(int) $item->product_id] = ($revenue[(int) $item->product_id] ?? 0) + (float) $item->total_amount;
        }
    }
    return $revenue;
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

    $cart_order = ['key' => ppcart_meta_key('checkout_type'), 'value' => PPCART_CHECKOUT_TYPE_CART];
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
        'quantity' => __('Quantity updated.', 'publishpress-cart'),
        'already' => __('Already in your cart.', 'publishpress-cart'),
        'error' => __('The cart could not be updated. Please try again.', 'publishpress-cart'),
    ]);
}

function ppcart_shopping_cart_style_tokens(array $settings): array
{
    return ppcart_block_style_tokens(array_intersect_key($settings, array_flip(['preset', 'accentColor'])));
}

function ppcart_shopping_cart_style(array $attributes): string
{
    $settings = $attributes['styleSettings'] ?? $attributes['stylesettings'] ?? [];
    $style = '';
    foreach (ppcart_shopping_cart_style_tokens(is_array($settings) ? $settings : []) as $key => $value) {
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
    $attributes = (array) $attributes;
    return (new PPCart_Shopping_Cart_View())->render('cart', ppcart_get_shopping_cart(), $attributes, ['continue_text' => ppcart_shopping_cart_continue_text($attributes)]);
}

/** The text of the Continue shopping link on the Cart page, or '' when the block hides the link. */
function ppcart_shopping_cart_continue_text(array $attributes = []): string
{
    if (false === ($attributes['showContinueShopping'] ?? true)) {
        return '';
    }
    $text = trim(sanitize_text_field((string) ($attributes['continueShoppingText'] ?? '')));
    return '' !== $text ? $text : __('← Continue shopping', 'publishpress-cart');
}

/** The cart checkout form, or the confirmation of a cart order the visitor may view. */
function ppcart_render_shopping_cart_checkout($attributes = []): string
{
    return (new PPCart_Public_Cart_Checkout_Controller())->render((array) $attributes);
}

/** The confirmation of a cart order: the Cart settings message and the receipt. */
function ppcart_shopping_cart_confirmation(PPCart_Order $order, array $attributes = []): string
{
    ppcart_shopping_cart_assets();
    $placed = in_array($order->status, ['paid', 'completed'], true) || PPCart_Order::$cod_gateway_str === $order->pay_method;
    return ppcart_get_template('shopping-cart/confirmation', '', [
        'style'    => ppcart_shopping_cart_style($attributes),
        'placed'   => $placed,
        'message'  => (new PPCart_Shopping_Cart_Settings())->confirmation($order)['message'],
        'items'    => ppcart_get_item_list($order->id),
        'order_id' => (int) $order->id,
        'token'    => $placed ? '' : PPCart_Order::ensure_invoice_access_token($order->id),
    ]);
}
