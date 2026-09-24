<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Account_Renderer_Navigation
{
    public function render_navigation($attributes, $block = null)
    {
        if (! is_user_logged_in() || ! function_exists('ppcart_account_tabs')) {
            return '';
        }

        $inner_blocks           = $this->get_layout_inner_blocks($block);
        $has_nested_tab_blocks  = ! empty($inner_blocks);
        $previous_layout_state  = $this->is_rendering_layout;
        $active_tab = 'tab-orders';

        if ($this->is_rendering_layout) {
            $active_tab = $this->layout_active_tab;
        } elseif (isset($attributes['activeTab'])) {
            $active_tab = sanitize_html_class($attributes['activeTab']);
        }

        $route_active_tab = $this->context->get_detail_route_active_tab();

        if ('' !== $route_active_tab) {
            $active_tab = $route_active_tab;
        }

        $included_tabs = $this->get_configured_navigation_tabs($attributes);

        if (is_array($included_tabs) && empty($included_tabs)) {
            return '';
        }

        if ($has_nested_tab_blocks) {
            $panel_tabs = $this->get_layout_panel_tabs($inner_blocks);

            if (is_array($included_tabs)) {
                $panel_tabs = array_values(array_intersect($panel_tabs, $included_tabs));
            }

            if ($panel_tabs && ! in_array($active_tab, $panel_tabs, true)) {
                $active_tab = reset($panel_tabs);
            }

            if (is_array($included_tabs) && ! in_array($active_tab, $included_tabs, true)) {
                $active_tab = reset($included_tabs);
            }
        }

        $tabs             = $this->get_navigation_tabs($attributes, $inner_blocks);
        $renders_fragment = $this->is_rendering_layout || $has_nested_tab_blocks;
        $output           = $renders_fragment
            ? '<div class="tab"><ul class="ppcart-nav-tabs">'
            : '<div class="ppcart-my-account ppcart-account-list"><div class="tab"><ul class="ppcart-nav-tabs">';

        foreach ($tabs as $account_tab) {
            if (empty($account_tab['id']) || empty($account_tab['title'])) {
                continue;
            }

            $tab_id = sanitize_html_class($account_tab['id']);

            if (is_array($included_tabs) && ! in_array($tab_id, $included_tabs, true)) {
                continue;
            }

            $active = $tab_id === $active_tab ? ' active' : '';
            $output .= sprintf(
                '<li><a class="tablinks%1$s" href="#%2$s" data-testid="%4$s">%3$s</a></li>',
                esc_attr($active),
                esc_attr($tab_id),
                esc_html($account_tab['title']),
                esc_attr(ppcart_testid('ppcart-account-tab-' . $tab_id))
            );
        }

        $output .= $renders_fragment ? '</ul></div>' : '</ul></div></div>';

        if ($has_nested_tab_blocks) {
            $nested_output = $this->with_layout_context(
                $active_tab,
                function () use ($inner_blocks, $included_tabs) {
                    $nested_output = '';

                    foreach ($inner_blocks as $inner_block) {
                        if (empty($inner_block['blockName'])) {
                            continue;
                        }

                        $tab_id = $this->get_tab_id_for_inner_block($inner_block);

                        if (is_array($included_tabs) && '' !== $tab_id && ! in_array($tab_id, $included_tabs, true)) {
                            continue;
                        }

                        $nested_output .= render_block($inner_block);
                    }

                    return $nested_output;
                }
            );

            $output .= $nested_output;

            if (! $previous_layout_state) {
                $output = '<div class="ppcart-my-account ppcart-account-list">' . $output . '</div>';
            }
        }

        return $output;
    }

    private function get_navigation_tabs($attributes, $inner_blocks)
    {
        $included_tabs = $this->get_configured_navigation_tabs($attributes);

        $account_tab_blocks = array_filter(
            $inner_blocks,
            function ($inner_block) {
                return ! empty($inner_block['blockName']) && 'publishpress-cart/account-tab' === $inner_block['blockName'];
            }
        );

        if ($account_tab_blocks) {
            $tabs = [];

            foreach ($account_tab_blocks as $account_tab_block) {
                $tab_attributes = ! empty($account_tab_block['attrs']) && is_array($account_tab_block['attrs']) ? $account_tab_block['attrs'] : [];
                $tab_id         = ! empty($tab_attributes['tabId']) ? sanitize_html_class($tab_attributes['tabId']) : 'tab-orders';

                if (is_array($included_tabs) && ! in_array($tab_id, $included_tabs, true)) {
                    continue;
                }

                $tabs[] = [
                    'id'    => $tab_id,
                    'title' => ! empty($tab_attributes['label']) ? sanitize_text_field($tab_attributes['label']) : $this->get_account_tab_label($tab_id),
                ];
            }

            return $tabs;
        }

        $tabs = ppcart_account_tabs();

        if (! is_array($included_tabs)) {
            return $tabs;
        }

        return array_values(
            array_filter(
                $tabs,
                function ($tab) use ($included_tabs) {
                    return ! empty($tab['id']) && in_array(sanitize_html_class($tab['id']), $included_tabs, true);
                }
            )
        );
    }

    private function get_account_tab_label($tab_id)
    {
        foreach ($this->context->get_account_navigation_options() as $account_tab) {
            if (! empty($account_tab['value']) && $tab_id === $account_tab['value'] && ! empty($account_tab['label'])) {
                return $account_tab['label'];
            }
        }

        $fallback_labels = [
            'tab-orders'        => __('Orders', 'publishpress-cart'),
            'tab-subscriptions' => __('Subscriptions', 'publishpress-cart'),
            'tab-plans'         => __('Installment Plans', 'publishpress-cart'),
            'tab-profile'       => __('My Profile', 'publishpress-cart'),
            'tab-files'         => __('Downloads', 'publishpress-cart'),
        ];

        return $fallback_labels[ $tab_id ] ?? $tab_id;
    }

    public function apply_tab_block_attributes($tab_id, $output, $attributes)
    {
        if ('' === trim((string) $output)) {
            return $output;
        }

        if ('tab-orders' === $tab_id && ! empty($attributes['emptyText'])) {
            $custom = wp_kses_post($attributes['emptyText']);
            $output = preg_replace_callback(
                '/(<td\b(?=[^>]*\bppcart-account-orders-empty\b)[^>]*>)(.*?)(<\/td>)/is',
                function ($matches) use ($custom) {
                    return $matches[1] . $custom . $matches[3];
                },
                $output,
                1
            );
        }

        if (
            in_array($tab_id, [ 'tab-subscriptions', 'tab-plans' ], true)
            && array_key_exists('visibleGroups', $attributes)
            && is_array($attributes['visibleGroups'])
        ) {
            $output = $this->filter_subscription_groups($output, $attributes['visibleGroups'], $tab_id);
        }

        if (
            in_array($tab_id, [ 'tab-orders', 'tab-subscriptions', 'tab-plans' ], true)
            && (
                in_array($tab_id, [ 'tab-orders', 'tab-subscriptions' ], true)
                || 'tab-plans' === $tab_id
            )
        ) {
            $output = $this->apply_status_pill_markup($output, $tab_id);
        }

        if ('tab-profile' === $tab_id && ! empty($attributes['showAvatar'])) {
            $output = $this->prepend_profile_avatar($output);
        }

        return $output;
    }

    private function apply_status_pill_markup($output, $tab_id)
    {
        $column_index = 'tab-orders' === $tab_id ? 3 : 2;

        return preg_replace_callback(
            '/<tr>(.*?)<\/tr>/s',
            function ($matches) use ($column_index) {
                $row = $matches[1];
                $cells = [];
                preg_match_all('/<td[^>]*>.*?<\/td>/s', $row, $cells);

                if (empty($cells[0]) || count($cells[0]) < $column_index) {
                    return $matches[0];
                }

                $target_cell = $cells[0][ $column_index - 1 ];

                if (false !== strpos($target_cell, 'ppcart-status-pill')) {
                    return $matches[0];
                }

                $wrapped = preg_replace_callback(
                    '/(<td[^>]*>)(.*?)(<\/td>)/s',
                    function ($cell) {
                        $slug = '';

                        if (preg_match('/\sdata-status=["\']([^"\']+)["\']/', $cell[1], $status_matches)) {
                            $slug = sanitize_html_class(sanitize_key($status_matches[1]));
                        }

                        if ('' === $slug) {
                            $inner_label = preg_replace('/<[^>]+>/', '', $cell[2]);
                            $inner_label = trim($inner_label);
                            $slug = sanitize_html_class(strtolower(str_replace(' ', '-', $inner_label)));
                        }

                        $slug = $slug ? $slug : 'unknown';

                        return $cell[1] . '<span class="ppcart-status-pill" data-status="' . esc_attr($slug) . '">' . $cell[2] . '</span>' . $cell[3];
                    },
                    $target_cell
                );

                return str_replace($target_cell, $wrapped, $matches[0]);
            },
            $output
        );
    }

    private function prepend_profile_avatar($output)
    {
        if (! function_exists('get_avatar') || ! is_user_logged_in()) {
            return $output;
        }

        $current_user = wp_get_current_user();
        $avatar       = get_avatar($current_user->ID, 64);
        $name         = trim($current_user->display_name);

        if ('' === $name) {
            $name = $current_user->user_email;
        }

        $email = esc_html($current_user->user_email);

        $identity = '<div class="ppcart-account-profile-identity">' . $avatar . '<div class="ppcart-account-profile-identity__meta"><strong>' . esc_html($name) . '</strong><span>' . $email . '</span></div></div>';

        return preg_replace('/(<div\s+class="profile-wrapper[^"]*"[^>]*>)/', '$1' . $identity, $output, 1);
    }

    private function filter_subscription_groups($output, $visible_groups, $tab_id)
    {
        $prefix = 'tab-plans' === $tab_id ? 'plan-' : 'subscription-';
        $allowed = [];

        foreach ($visible_groups as $group) {
            $group = sanitize_html_class($group);

            if ('' !== $group) {
                $allowed[] = $prefix . $group;
            }
        }

        if (! $allowed) {
            return '';
        }

        return preg_replace_callback(
            '/<div\s+id="(' . preg_quote($prefix, '/') . '[^"]+)"\s+class="ppcart-account-tab-pane">.*?<\/div>\s*<\/div>/s',
            function ($matches) use ($allowed) {
                $id = $matches[1];

                return in_array($id, $allowed, true) ? $matches[0] : '';
            },
            $output
        );
    }
}
