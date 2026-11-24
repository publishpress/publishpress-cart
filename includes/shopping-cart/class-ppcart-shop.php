<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PPCart_Shop
{
    public function normalize_attributes(array $attributes): array
    {
        $attributes = array_change_key_case($attributes, CASE_LOWER);
        $normalized = [
            'perPage' => min(100, max(1, absint($attributes['perpage'] ?? 12))),
            'columns' => min(4, max(1, absint($attributes['columns'] ?? 3))),
            'orderBy' => in_array($attributes['orderby'] ?? '', ['date', 'title', 'price', 'menu_order'], true) ? $attributes['orderby'] : 'date',
            'styleSettings' => is_array($attributes['stylesettings'] ?? null) ? $attributes['stylesettings'] : [],
        ];
        foreach (['showImage', 'showExcerpt', 'showPrice', 'includeCollections'] as $key) {
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
        if ('price' === $attributes['orderBy']) {
            // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.posts_per_page_posts_per_page -- Current plan prices are serialized and filterable; sort the complete offers before applying pagination.
            $args['posts_per_page'] = -1;
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
        if ('price' === $attributes['orderBy']) {
            $catalog = new PPCart_Shopping_Cart_Catalog();
            $prices = [];
            foreach ($query->posts as $product) {
                $prices[$product->ID] = $catalog->starting_price($product->ID);
            }
            usort($query->posts, static fn ($a, $b) => ($prices[$a->ID] <=> $prices[$b->ID]) ?: ($a->ID <=> $b->ID));
            $query->found_posts = count($query->posts);
            $query->max_num_pages = (int) ceil($query->found_posts / $attributes['perPage']);
            $query->posts = array_slice($query->posts, ($args['paged'] - 1) * $attributes['perPage'], $attributes['perPage']);
            $query->post_count = count($query->posts);
        }
        return $query;
    }

    public function render(array $attributes): string
    {
        $attributes = $this->normalize_attributes($attributes);
        $query = $this->query($attributes);
        $catalog = new PPCart_Shopping_Cart_Catalog();
        $cards = [];
        foreach ($query->posts as $product) {
            $cards[] = $catalog->shop_card($product->ID);
        }
        $tokens = ppcart_block_style_tokens($attributes['styleSettings']);
        $tokens['--ppcart-shop-columns'] = (string) $attributes['columns'];
        $style = '';
        foreach ($tokens as $key => $value) {
            $style .= $key . ':' . $value . ';';
        }
        $previous_query = $GLOBALS['wp_query'];
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Core pagination reads the active query; restore it immediately after rendering.
        $GLOBALS['wp_query'] = $query;
        ob_start();
        try {
            the_posts_pagination(['mid_size' => 1]);
            $pagination = ob_get_contents();
        } finally {
            ob_end_clean();
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the caller's query after core pagination.
            $GLOBALS['wp_query'] = $previous_query;
        }
        return ppcart_get_template('shopping-cart/shop', '', compact('attributes', 'cards', 'style', 'pagination'));
    }
}
