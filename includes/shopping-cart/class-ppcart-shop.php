<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PPCart_Shop
{
    public const PAGINATION = ['numbers', 'prev_next', 'load_more', 'none'];

    public function normalize_attributes(array $attributes): array
    {
        $attributes = array_change_key_case($attributes, CASE_LOWER);
        $normalized = [
            'perPage' => min(100, max(1, absint($attributes['perpage'] ?? 12))),
            'columns' => min(4, max(1, absint($attributes['columns'] ?? 3))),
            'orderBy' => in_array($attributes['orderby'] ?? '', ['date', 'title', 'price', 'menu_order'], true) ? $attributes['orderby'] : 'date',
            'pagination' => in_array($attributes['pagination'] ?? '', self::PAGINATION, true) ? $attributes['pagination'] : 'numbers',
            'styleSettings' => is_array($attributes['stylesettings'] ?? null) ? $attributes['stylesettings'] : [],
        ];
        foreach (['showImage', 'showExcerpt', 'showPrice', 'includeCollections', 'includeSubscriptions'] as $key) {
            $normalized[$key] = filter_var($attributes[strtolower($key)] ?? true, FILTER_VALIDATE_BOOLEAN);
        }
        foreach (['categories', 'tags'] as $key) {
            $values = $attributes[$key] ?? [];
            $normalized[$key] = array_values(array_filter(array_map('absint', is_array($values) ? $values : explode(',', (string) $values))));
        }
        return $normalized;
    }

    public function query(array $attributes): WP_Query
    {
        $types = ppcart_query_post_types('product');
        if ($attributes['includeCollections']) {
            $types = array_merge($types, ppcart_query_pro_post_types('collection'));
        }
        $args = [
            'posts_per_page' => $attributes['perPage'],
            'paged'          => max(1, (int) get_query_var('paged'), (int) get_query_var('page')),
            'orderby'        => $attributes['orderBy'],
            'order'          => in_array($attributes['orderBy'], ['title', 'price', 'menu_order'], true) ? 'ASC' : 'DESC',
        ];
        $sort_by_price = 'price' === $attributes['orderBy'];
        $hide_subscriptions = ! $attributes['includeSubscriptions'];
        $show_all = 'none' === $attributes['pagination'];
        if ($show_all) {
            $args['paged'] = 1;
        }
        if ($sort_by_price || $hide_subscriptions || $show_all) {
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- Plan prices and types are serialized and filterable; sort and filter the complete offers before applying pagination.
            $args['posts_per_page'] = -1;
        }
        if ($sort_by_price) {
            $args['orderby'] = 'ID';
        }
        foreach (['categories' => 'product_cat', 'tags' => 'product_tag'] as $key => $taxonomy) {
            if ($attributes[$key]) {
                $args['tax_query'][] = ['taxonomy' => ppcart_live_taxonomy($taxonomy), 'field' => 'term_id', 'terms' => $attributes[$key]];
            }
        }
        $args = apply_filters('ppcart_shop_query_args', $args, $attributes);
        $args['post_type'] = $types;
        $args['post_status'] = 'publish';
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Missing visibility metadata means visible, so both branches are required.
        $args['meta_query'] = ['relation' => 'AND', $args['meta_query'] ?? [], [
            'relation' => 'OR',
            ['key' => '_ppcart_show_in_shop', 'compare' => 'NOT EXISTS'],
            ['key' => '_ppcart_show_in_shop', 'value' => 'no', 'compare' => '!='],
        ]];
        $query = new WP_Query($args);
        if ($sort_by_price || $hide_subscriptions || $show_all) {
            $catalog = new PPCart_Shopping_Cart_Catalog();
            if ($hide_subscriptions) {
                $query->posts = array_values(array_filter($query->posts, static fn ($product) => ! $catalog->sells_by_subscription_only($product->ID)));
            }
            if ($sort_by_price) {
                $prices = [];
                foreach ($query->posts as $product) {
                    $prices[$product->ID] = $catalog->starting_price($product->ID);
                }
                usort($query->posts, static fn ($a, $b) => ($prices[$a->ID] <=> $prices[$b->ID]) ?: ($a->ID <=> $b->ID));
            }
            $query->found_posts = count($query->posts);
            $query->max_num_pages = $show_all ? 1 : (int) ceil($query->found_posts / $attributes['perPage']);
            if (! $show_all) {
                $query->posts = array_slice($query->posts, ($args['paged'] - 1) * $attributes['perPage'], $attributes['perPage']);
            }
            $query->post_count = count($query->posts);
        }
        return $query;
    }

    public function render(array $attributes): string
    {
        $attributes = $this->normalize_attributes($attributes);
        $query = $this->query($attributes);
        $catalog = new PPCart_Shopping_Cart_Catalog();
        // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE -- Browsing without a cart must stay cacheable and create no row.
        $cart = isset($_COOKIE[PPCart_Shopping_Cart_Store::COOKIE]) || is_user_logged_in() ? ppcart_get_shopping_cart() : null;
        $cards = [];
        foreach ($query->posts as $product) {
            $cards[] = $catalog->shop_card($product->ID, $cart);
        }
        $tokens = ppcart_shopping_cart_style_tokens($attributes['styleSettings']);
        $tokens['--ppcart-shop-columns'] = (string) $attributes['columns'];
        $style = '';
        foreach ($tokens as $key => $value) {
            $style .= $key . ':' . $value . ';';
        }
        $pagination = $this->pagination($attributes['pagination'], (int) $query->get('paged') ?: 1, (int) $query->max_num_pages);
        return ppcart_get_template('shopping-cart/shop', '', compact('attributes', 'cards', 'style', 'pagination'));
    }

    private function pagination(string $style, int $current, int $total): array
    {
        if ($total < 2 || 'none' === $style) {
            return ['style' => 'none'];
        }
        $current = min(max(1, $current), $total);
        $pages = [
            'style'    => $style,
            'current'  => $current,
            'total'    => $total,
            'previous' => $current > 1 ? get_pagenum_link($current - 1) : '',
            'next'     => $current < $total ? get_pagenum_link($current + 1) : '',
            'links'    => [],
        ];
        if ('numbers' === $style) {
            $pages['links'] = (array) paginate_links([
                'type'      => 'array',
                'current'   => $current,
                'total'     => $total,
                'mid_size'  => 1,
                'prev_next' => false,
            ]);
        }
        return $pages;
    }
}
