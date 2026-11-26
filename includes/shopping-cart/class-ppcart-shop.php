<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PPCart_Shop
{
    public const PAGINATION = ['numbers', 'prev_next', 'load_more', 'none'];

    /** Image shapes of the Shop card, as CSS aspect ratios. */
    public const IMAGE_RATIOS = ['landscape' => '16/10', 'wide' => '16/9', 'standard' => '4/3', 'square' => '1/1', 'portrait' => '3/4', 'original' => 'auto'];

    /** Card design colors and the CSS variables they set. */
    private const CARD_COLORS = ['cardBackground' => '--ppcart-shop-card-bg', 'titleColor' => '--ppcart-shop-title-color', 'textColor' => '--ppcart-shop-text-color', 'priceColor' => '--ppcart-shop-price-color'];

    public function normalize_attributes(array $attributes): array
    {
        $attributes = array_change_key_case($attributes, CASE_LOWER);
        $normalized = [
            'perPage' => min(100, max(1, absint($attributes['perpage'] ?? 12))),
            'columns' => min(4, max(1, absint($attributes['columns'] ?? 3))),
            'orderBy' => in_array($attributes['orderby'] ?? '', ['date', 'title', 'price', 'menu_order'], true) ? $attributes['orderby'] : 'date',
            'pagination' => in_array($attributes['pagination'] ?? '', self::PAGINATION, true) ? $attributes['pagination'] : 'numbers',
            'descriptionSource' => 'full' === ($attributes['descriptionsource'] ?? '') ? 'full' : 'excerpt',
            'styleSettings' => is_array($attributes['stylesettings'] ?? null) ? $attributes['stylesettings'] : [],
        ];
        $normalized['cardDesign'] = $this->card_design($attributes['carddesign'] ?? []);
        foreach (['showImage', 'showExcerpt', 'showPrice', 'includeCollections', 'includeSubscriptions', 'linkToProduct'] as $key) {
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
            $card = $catalog->shop_card($product->ID, $cart);
            if ($attributes['showExcerpt'] && 'full' === $attributes['descriptionSource']) {
                $card['description'] = $this->description($product);
            }
            $cards[] = $card;
        }
        $tokens = ppcart_shopping_cart_style_tokens($attributes['styleSettings']);
        $tokens['--ppcart-shop-columns'] = (string) $attributes['columns'];
        $tokens += $this->card_design_tokens($attributes['cardDesign']);
        $style = '';
        foreach ($tokens as $key => $value) {
            $style .= $key . ':' . $value . ';';
        }
        $pagination = $this->pagination($attributes['pagination'], (int) $query->get('paged') ?: 1, (int) $query->max_num_pages);
        return ppcart_get_template('shopping-cart/shop', '', compact('attributes', 'cards', 'style', 'pagination'));
    }

    /**
     * The product description with its formatting. Shortcodes and dynamic blocks are left out,
     * so a checkout form or a Shop inside a description does not render in the card.
     */
    private function description(WP_Post $product): string
    {
        $content = strip_shortcodes($product->post_content);
        $content = has_blocks($content) ? excerpt_remove_blocks($content) : wpautop($content);
        return trim(wp_kses_post($content));
    }

    /** Keep only the card design values the Shop knows, in a safe form. */
    private function card_design(mixed $design): array
    {
        $design = is_array($design) ? $design : [];
        $clean = [];
        if (isset(self::IMAGE_RATIOS[$design['imageRatio'] ?? ''])) {
            $clean['imageRatio'] = $design['imageRatio'];
        }
        foreach (['cardPadding', 'cardGap'] as $key) {
            if (isset($design[$key]) && is_numeric($design[$key])) {
                $clean[$key] = min(64, max(0, (int) $design[$key]));
            }
        }
        foreach (array_keys(self::CARD_COLORS) as $key) {
            $color = ppcart_shopping_cart_css_color($design[$key] ?? '');
            if ('' !== $color) {
                $clean[$key] = $color;
            }
        }
        return $clean;
    }

    private function card_design_tokens(array $design): array
    {
        $tokens = [];
        if (isset($design['imageRatio'])) {
            $tokens['--ppcart-shop-image-ratio'] = self::IMAGE_RATIOS[$design['imageRatio']];
        }
        if (isset($design['cardPadding'])) {
            $tokens['--ppcart-shop-card-padding'] = $design['cardPadding'] . 'px';
        }
        if (isset($design['cardGap'])) {
            $tokens['--ppcart-shop-gap'] = $design['cardGap'] . 'px';
        }
        foreach (self::CARD_COLORS as $key => $token) {
            if (isset($design[$key])) {
                $tokens[$token] = $design[$key];
            }
        }
        return $tokens;
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
