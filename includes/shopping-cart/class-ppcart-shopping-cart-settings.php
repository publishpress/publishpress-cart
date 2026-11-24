<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PPCart_Shopping_Cart_Settings
{
    public function definitions(): array
    {
        return require dirname(__DIR__, 2) . '/admin/settings/traits/options/shop-cart-fields.php';
    }

    public function enabled(): bool
    {
        return 1 === $this->option('_ppcart_shopping_cart_enable');
    }

    public function urls(): array
    {
        $urls = [];
        foreach ($this->definitions() as $fields) {
            foreach ($fields as $field) {
                if (isset($field['settings']['create_page'])) {
                    $page = $this->option($field['settings']['id']);
                    $urls[$field['settings']['create_page']['role']] = $page ? (string) get_permalink($page) : '';
                }
            }
        }
        return $urls;
    }

    public function after_add(): string
    {
        return $this->option('_ppcart_after_add_to_cart');
    }

    public function lifetime_days(): int
    {
        return $this->option('_ppcart_shopping_cart_lifetime');
    }

    public function button_text(): string
    {
        return $this->option('_ppcart_cart_checkout_button_text');
    }

    public function checkout_fields(): array
    {
        $fields = [
            'first_name' => ['type' => 'text', 'label' => __('First name', 'publishpress-cart'), 'required' => true],
            'last_name' => ['type' => 'text', 'label' => __('Last name', 'publishpress-cart'), 'required' => true],
            'email' => ['type' => 'email', 'label' => __('Email', 'publishpress-cart'), 'required' => true],
        ];
        if ($this->option('_ppcart_cart_checkout_phone')) {
            $fields['phone'] = ['type' => 'text', 'label' => __('Phone', 'publishpress-cart'), 'required' => false];
        }
        if ($this->option('_ppcart_cart_checkout_address')) {
            foreach (['country' => __('Country', 'publishpress-cart'), 'address1' => __('Address', 'publishpress-cart'), 'address2' => __('Address line 2', 'publishpress-cart'), 'city' => __('City', 'publishpress-cart'), 'state' => __('State', 'publishpress-cart'), 'zip' => __('Postal code', 'publishpress-cart')] as $name => $label) {
                $fields[$name] = ['type' => 'text', 'label' => $label, 'required' => 'address2' !== $name];
            }
        }
        $terms_url = $this->option('_ppcart_cart_checkout_terms_url');
        if ($this->option('_ppcart_cart_checkout_terms') && $terms_url) {
            $fields['terms'] = ['type' => 'checkbox', 'label' => __('I agree to the terms and conditions', 'publishpress-cart'), 'required' => true, 'url' => $terms_url];
        }
        return (array) apply_filters('ppcart_shopping_cart_checkout_fields', $fields);
    }

    public function confirmation(PPCart_Order $order): array
    {
        $type = $this->option('_ppcart_cart_confirmation');
        $url = $this->urls()['checkout'];
        if ('page' === $type) {
            $page = $this->option('_ppcart_cart_confirmation_page');
            $url = $page ? (string) get_permalink($page) : $url;
        }
        $redirect = 'url' === $type ? $this->option('_ppcart_cart_redirect') : '';
        return [
            'type' => $type,
            'message' => wp_kses_post(ppcart_personalize($this->option('_ppcart_cart_confirmation_message'), $order->get_data())),
            'url' => $redirect ?: ($url ? PPCart_Order::confirmation_url($url, $order->id) : ''),
        ];
    }

    public function sanitize_field(array $field, mixed $value): mixed
    {
        $settings = $field['settings'];
        $default = $settings['value'];
        if (! is_scalar($value) && null !== $value) {
            return $default;
        }
        if (! empty($settings['page_select'])) {
            $id = absint($value);
            return $id && 'page' === get_post_type($id) && 'publish' === get_post_status($id) ? $id : 0;
        }
        if (isset($settings['selections'])) {
            return array_key_exists((string) $value, $settings['selections']) ? (string) $value : $default;
        }
        $type = $settings['type'] ?? $field['type'];
        return match ($type) {
            'checkbox' => in_array($value, [1, '1', true], true) ? 1 : 0,
            'number' => is_numeric($value) ? max(1, (int) $value) : $default,
            'url' => esc_url_raw((string) $value),
            'textarea', 'editor' => wp_kses_post((string) $value),
            default => sanitize_text_field((string) $value),
        };
    }

    public function create_missing_pages(): array|WP_Error
    {
        $result = ['created' => [], 'skipped' => []];
        foreach ($this->definitions() as $fields) {
            foreach ($fields as $field) {
                $settings = $field['settings'];
                if (! isset($settings['create_page'])) {
                    continue;
                }
                $page = $settings['create_page'];
                $role = $page['role'];
                $current = $this->option($settings['id']);
                if ($current) {
                    $result['skipped'][$role] = $current;
                    continue;
                }
                $id = wp_insert_post([
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'post_title' => $page['title'],
                    'post_content' => serialize_block(['blockName' => $page['block'], 'attrs' => $page['attributes'], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []]),
                ], true);
                if (is_wp_error($id)) {
                    return $id;
                }
                update_option($settings['id'], $id);
                $result['created'][$role] = (int) $id;
            }
        }
        return $result;
    }

    private function option(string $id): mixed
    {
        foreach ($this->definitions() as $fields) {
            foreach ($fields as $field) {
                if ($id === $field['settings']['id']) {
                    return $this->sanitize_field($field, get_option($id, $field['settings']['value']));
                }
            }
        }
        return null;
    }
}
