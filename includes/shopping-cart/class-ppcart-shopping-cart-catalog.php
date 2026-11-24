<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PPCart_Shopping_Cart_Catalog
{
    public function product_is_eligible(int $product_id): bool
    {
        if ('publish' !== get_post_status($product_id) || ! ppcart_product_shows_in_shop($product_id) || ppcart_is_cart_closed($product_id)) {
            return false;
        }
        $eligible = in_array(get_post_type($product_id), ppcart_query_post_types('product'), true)
            && [] !== array_filter($this->plans($product_id), fn ($plan) => $this->plan_is_eligible($plan, $product_id));
        return (bool) apply_filters('ppcart_shopping_cart_product_is_eligible', $eligible, $product_id);
    }

    public function plan_is_eligible(object $plan, int $product_id): bool
    {
        return (bool) apply_filters('ppcart_shopping_cart_plan_is_eligible', in_array($plan->type, ['one-time', 'free'], true), $plan, $product_id);
    }

    public function resolve_line(int $product_id, string $plan_id): array|WP_Error
    {
        if (! $this->product_is_eligible($product_id)) {
            return new WP_Error('ppcart_cart_not_eligible', __('This product cannot be added to the cart.', 'publishpress-cart'));
        }
        $plan = ppcart_plan($plan_id, 'current', $product_id);
        if (! $plan || (string) $plan->option_id !== $plan_id || ! $this->plan_is_eligible($plan, $product_id)) {
            return new WP_Error('ppcart_cart_plan_not_eligible', __('This plan cannot be added to the cart.', 'publishpress-cart'));
        }
        return [
            'product_id'   => $product_id,
            'plan_id'      => $plan_id,
            'unit_price'   => (float) $plan->initial_payment,
            'product_name' => $this->title($product_id),
            'plan_name'    => (string) $plan->name,
            'image_id'     => (int) get_post_thumbnail_id($product_id),
            'permalink'    => (string) get_permalink($product_id),
        ];
    }

    public function max_quantity(int $product_id, string $plan_id): int
    {
        return max(1, (int) apply_filters('ppcart_shopping_cart_item_max_quantity', 1, $product_id, $plan_id));
    }

    public function shop_card(int $product_id, ?PPCart_Shopping_Cart_Priced $cart = null): array
    {
        $all_plans = $this->plans($product_id);
        $plans = [];
        $eligible = $this->product_is_eligible($product_id);
        foreach ($all_plans as $plan) {
            if ($eligible && $this->plan_is_eligible($plan, $product_id)) {
                $plans[] = [
                    'plan_id' => (string) $plan->option_id,
                    'label'   => trim($plan->name . ', ' . $this->price_label($plan), ', '),
                    'in_cart' => $cart && $cart->has_line($product_id . ':' . $plan->option_id),
                ];
            }
        }
        $action = [] !== $plans ? 'add' : 'subscribe';
        if (! in_array(get_post_type($product_id), ppcart_query_post_types('product'), true) || ! (new PPCart_Shopping_Cart_Settings())->enabled() || [] === $all_plans || ppcart_is_cart_closed($product_id)) {
            $action = 'view';
        }
        $price_labels = array_map(fn ($plan) => $this->price_label($plan), $all_plans);
        usort($all_plans, static fn ($a, $b) => (float) $a->initial_payment <=> (float) $b->initial_payment);
        $price_label = $all_plans ? $this->price_label($all_plans[0]) : '';
        if (count($price_labels) > 1) {
            /* translators: %s: lowest plan price. */
            $price_label = sprintf(__('From %s', 'publishpress-cart'), $price_label);
        }
        $purchased = false;
        if (is_user_logged_in()) {
            $purchased = (bool) do_shortcode('[ppcart_customer_bought_product user_id="' . get_current_user_id() . '" product_id="' . $product_id . '"]');
        }
        return [
            'product_id'  => $product_id,
            'title'       => $this->title($product_id),
            'permalink'   => (string) get_permalink($product_id),
            'image_id'    => (int) get_post_thumbnail_id($product_id),
            'excerpt'     => (string) get_the_excerpt($product_id),
            'price_label' => $price_label,
            'purchased'   => $purchased,
            'action'      => $action,
            'plans'       => $plans,
            'selected'    => $plans[0]['plan_id'] ?? '',
            'form'        => ['nonce' => wp_create_nonce('ppcart_add_to_cart'), 'product_id' => $product_id],
        ];
    }

    public function starting_price(int $product_id): float
    {
        $prices = array_map(static fn ($plan) => (float) $plan->initial_payment, $this->plans($product_id));
        return $prices ? min($prices) : 0.0;
    }

    private function plans(int $product_id): array
    {
        $plans = [];
        foreach ((array) ppcart_get_post_meta($product_id, 'pay_options', true) as $option) {
            if (empty($option['option_id'])) {
                continue;
            }
            $id = (string) $option['option_id'];
            $plan = ppcart_plan($id, 'current', $product_id);
            if ($plan && $id === (string) $plan->option_id) {
                $plans[] = $plan;
            }
        }
        return $plans;
    }

    private function title(int $product_id): string
    {
        return (string) (get_post_meta($product_id, '_ppcart_product_name', true) ?: get_the_title($product_id));
    }

    private function price_label(object $plan): string
    {
        if ('free' === $plan->type) {
            return __('Free', 'publishpress-cart');
        }
        $price = wp_strip_all_tags(ppcart_format_price($plan->price));
        if ('recurring' === $plan->type) {
            $price .= ' / ' . ((int) $plan->frequency > 1 ? $plan->frequency . ' ' . ppcart_pluralize_interval($plan->interval) : $plan->interval);
        }
        return $price;
    }
}
