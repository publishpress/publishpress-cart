<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Account_Renderer_Context
{
    public function __construct($styles = null, $context = null)
    {
        $this->styles  = $styles instanceof PPCart_Account_Styles ? $styles : new PPCart_Account_Styles();
        $this->context = $context instanceof PPCart_Account_Context ? $context : new PPCart_Account_Context();
    }

    public function can_view_account_detail()
    {
        return $this->context->can_view_account_detail();
    }

    public function get_account_detail(WP_REST_Request $request)
    {
        $type         = sanitize_key($request->get_param('type'));
        $detail_id    = absint($request->get_param('id'));
        $presentation = sanitize_key($request->get_param('presentation'));
        $return_url   = $this->context->sanitize_account_detail_return_url($request->get_param('returnUrl'));

        if (! $detail_id || ! in_array($type, [ 'order', 'subscription' ], true)) {
            return new WP_Error(
                'publishpress_cart_invalid_account_detail',
                __('Invalid account detail request.', 'publishpress-cart'),
                [ 'status' => 400 ]
            );
        }

        if (! $this->context->verify_user_access($detail_id, $type)) {
            return new WP_Error(
                'publishpress_cart_account_detail_forbidden',
                __('You do not have permission to access this account content.', 'publishpress-cart'),
                [ 'status' => 403 ]
            );
        }

        $source_attributes = [
            'detailPresentation' => $presentation,
        ];
        $detail_attributes = [
            'showBackLink' => false,
        ];

        if ('order' === $type) {
            $detail_attributes['orderId'] = $detail_id;
            $detail_output                = $this->render_order_detail($detail_attributes);
            $detail_html                  = $this->render_presented_detail(
                'publishpress-cart/account-order-detail',
                $source_attributes,
                $detail_attributes,
                $detail_output,
                __('Order details', 'publishpress-cart'),
                $return_url,
                'publishpress-cart/account-orders'
            );
        } else {
            $detail_attributes['subscriptionId'] = $detail_id;
            $detail_output                       = $this->render_subscription_detail($detail_attributes);
            $detail_html                         = $this->render_presented_detail(
                'publishpress-cart/account-subscription-detail',
                $source_attributes,
                $detail_attributes,
                $detail_output,
                __('Subscription details', 'publishpress-cart'),
                $return_url,
                'publishpress-cart/account-subscriptions'
            );
        }

        if ('' === trim((string) $detail_html)) {
            return new WP_Error(
                'publishpress_cart_account_detail_not_found',
                __('Account detail content was not found.', 'publishpress-cart'),
                [ 'status' => 404 ]
            );
        }

        return rest_ensure_response(
            [
                'type' => $type,
                'id'   => $detail_id,
                'html' => $detail_html,
            ]
        );
    }

    public function enqueue_view_assets()
    {
        if ($this->is_editor_preview() || is_admin()) {
            return;
        }

        wp_enqueue_script(self::VIEW_SCRIPT_HANDLE);
    }

    public function prepare_block_attributes($block_name, $attributes)
    {
        return wp_parse_args($attributes, $this->get_default_attributes($block_name));
    }

    public function render_account_block_output($block_name, $attributes, $output)
    {
        if ('' === trim((string) $output)) {
            return '';
        }

        $this->enqueue_view_assets();

        return $this->wrap_block_output($block_name, $attributes, $output);
    }

    public function render_layout($content, $block = null)
    {
        $inner_blocks = $this->get_layout_inner_blocks($block);

        if (empty($inner_blocks)) {
            return $content;
        }

        $included_tabs = $this->get_layout_included_tabs($inner_blocks);
        $active_tab    = $this->get_layout_active_tab($inner_blocks);

        if (is_array($included_tabs) && empty($included_tabs)) {
            return '';
        }

        $output = $this->with_layout_context(
            $active_tab,
            function () use ($inner_blocks, $included_tabs) {
                $output = '';

                foreach ($inner_blocks as $inner_block) {
                    $tab_id = $this->get_tab_id_for_inner_block($inner_block);

                    if (is_array($included_tabs) && '' !== $tab_id && ! in_array($tab_id, $included_tabs, true)) {
                        continue;
                    }

                    $output .= render_block($inner_block);
                }

                return $output;
            }
        );

        if ('' === trim((string) $output)) {
            return '';
        }

        return '<div class="ppcart-my-account ppcart-account-list">' . ppcart_kses_frontend_html($output) . '</div>';
    }

    private function get_layout_inner_blocks($block)
    {
        if (is_object($block) && isset($block->parsed_block['innerBlocks']) && is_array($block->parsed_block['innerBlocks'])) {
            return $block->parsed_block['innerBlocks'];
        }

        return [];
    }

    private function with_layout_context($active_tab, $callback)
    {
        $previous_layout_state = $this->is_rendering_layout;
        $previous_active_tab   = $this->layout_active_tab;

        $this->is_rendering_layout = true;
        $this->layout_active_tab   = sanitize_html_class($active_tab);

        try {
            return call_user_func($callback);
        } finally {
            $this->is_rendering_layout = $previous_layout_state;
            $this->layout_active_tab   = $previous_active_tab;
        }
    }

    private function get_layout_included_tabs($inner_blocks)
    {
        foreach ($inner_blocks as $inner_block) {
            if (empty($inner_block['blockName']) || 'publishpress-cart/account-navigation' !== $inner_block['blockName']) {
                continue;
            }

            $attributes = isset($inner_block['attrs']) && is_array($inner_block['attrs']) ? $inner_block['attrs'] : [];

            return $this->get_configured_navigation_tabs($attributes);
        }

        return null;
    }

    private function get_layout_active_tab($inner_blocks)
    {
        $active_tab    = 'tab-orders';
        $included_tabs = null;

        foreach ($inner_blocks as $inner_block) {
            if (empty($inner_block['blockName']) || 'publishpress-cart/account-navigation' !== $inner_block['blockName']) {
                continue;
            }

            $attributes    = isset($inner_block['attrs']) && is_array($inner_block['attrs']) ? $inner_block['attrs'] : [];
            $included_tabs = $this->get_configured_navigation_tabs($attributes);

            if (! empty($attributes['activeTab'])) {
                $active_tab = sanitize_html_class($attributes['activeTab']);
            }

            if (is_array($included_tabs) && empty($included_tabs)) {
                return '';
            }

            $route_active_tab = $this->context->get_detail_route_active_tab();

            if ('' !== $route_active_tab) {
                $active_tab = $route_active_tab;
            }

            if (is_array($included_tabs) && ! in_array($active_tab, $included_tabs, true)) {
                $active_tab = reset($included_tabs);
            }

            break;
        }

        $panel_tabs = $this->get_layout_panel_tabs($inner_blocks);

        if (is_array($included_tabs)) {
            $panel_tabs = array_values(array_intersect($panel_tabs, $included_tabs));

            if (empty($panel_tabs)) {
                return '';
            }
        }

        if ($panel_tabs && ! in_array($active_tab, $panel_tabs, true)) {
            $active_tab = reset($panel_tabs);
        }

        return sanitize_html_class($active_tab);
    }

    private function get_configured_navigation_tabs($attributes)
    {
        $raw_tabs        = isset($attributes['includedTabs']) && is_array($attributes['includedTabs']) ? $attributes['includedTabs'] : [];
        $configured_tabs = ! empty($attributes['includedTabsConfigured']);

        if (! $configured_tabs && empty($raw_tabs)) {
            return null;
        }

        $raw_tabs = array_filter($raw_tabs, 'is_scalar');

        return array_values(array_filter(array_map('sanitize_html_class', $raw_tabs)));
    }

    private function get_layout_panel_tabs($inner_blocks)
    {
        $panel_tabs = [];

        foreach ($inner_blocks as $inner_block) {
            if (empty($inner_block['blockName'])) {
                continue;
            }

            $tab_id = $this->get_tab_id_for_inner_block($inner_block);

            if ('' !== $tab_id) {
                $panel_tabs[] = $tab_id;
            }

            if (! empty($inner_block['innerBlocks']) && is_array($inner_block['innerBlocks'])) {
                $panel_tabs = array_merge($panel_tabs, $this->get_layout_panel_tabs($inner_block['innerBlocks']));
            }
        }

        return array_values(array_unique($panel_tabs));
    }

    private function get_tab_id_for_inner_block($inner_block)
    {
        if (empty($inner_block['blockName'])) {
            return '';
        }

        if ('publishpress-cart/account-tab' === $inner_block['blockName']) {
            $attributes = ! empty($inner_block['attrs']) && is_array($inner_block['attrs']) ? $inner_block['attrs'] : [];
            return ! empty($attributes['tabId']) ? sanitize_html_class($attributes['tabId']) : 'tab-orders';
        }

        return $this->get_tab_id_for_block_name($inner_block['blockName']);
    }

    private function get_tab_id_for_block_name($block_name)
    {
        if (isset($this->tab_blocks[ $block_name ])) {
            return $this->tab_blocks[ $block_name ]['tab_id'];
        }

        if ('publishpress-cart/account-downloads' === $block_name) {
            return 'tab-files';
        }

        return '';
    }
}
