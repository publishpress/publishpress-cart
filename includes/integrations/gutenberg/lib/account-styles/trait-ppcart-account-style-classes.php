<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * CSS class helpers for Gutenberg account blocks.
 *
 * @package PPCart
 * @subpackage PPCart/includes/integrations/gutenberg
 */
trait PPCart_Account_Style_Classes
{
    /**
     * Wrap rendered account-block HTML with wrapper attributes and style classes.
     *
     * @param string $block_name Block name (publishpress-cart/...).
     * @param array  $attributes Block attributes.
     * @param string $output     Inner HTML.
     * @return string
     */
    public function wrap_block_output($block_name, $attributes, $output)
    {
        $class_name     = trim(str_replace('/', '-', $block_name) . ' ' . $this->get_account_block_style_classes($block_name, $attributes));
        $style_attribute = $this->get_account_block_style_attribute($block_name, $attributes);
        $block_id        = $this->get_block_id($attributes, str_replace('publishpress-cart/', 'ppcart-', $block_name) . '-');
        $extra_attributes = [
            'id'    => $block_id,
            'class' => $class_name,
        ];

        if ('' !== $style_attribute) {
            $extra_attributes['style'] = $style_attribute;
        }

        if (function_exists('get_block_wrapper_attributes') && is_array(WP_Block_Supports::$block_to_render)) {
            $wrapper_attributes = get_block_wrapper_attributes($extra_attributes);
        } else {
            $wrapper_attributes = sprintf(
                'id="%1$s" class="%2$s"%3$s',
                esc_attr($block_id),
                esc_attr($class_name),
                '' !== $style_attribute ? ' style="' . esc_attr($style_attribute) . '"' : ''
            );
        }

        $output = ppcart_kses_frontend_html($output);

        return sprintf(
            '<div %1$s>%2$s</div>',
            $wrapper_attributes,
            $output
        );
    }

    /**
     * Build CSS class names for an account Gutenberg block from its attributes.
     *
     * @param string $block_name Block name (publishpress-cart/...).
     * @param array  $attributes Block attributes.
     * @return string Space-separated class list.
     */
    private function get_account_block_style_classes($block_name, $attributes)
    {
        $classes = [];

        if ('publishpress-cart/account-page-builder' === $block_name) {
            if ($this->layout_has_container_styles($attributes)) {
                $classes[] = 'ppcart-account-page-builder-has-container';
            }

            $inner_layout = $this->get_layout_inner_layout_attribute($attributes);

            if ('' !== $inner_layout) {
                $classes[] = 'ppcart-account-page-builder-inner-' . $inner_layout;
            }
        }

        if ('publishpress-cart/account-navigation' === $block_name) {
            $tab_style = $this->get_choice_attribute($attributes, 'tabStyle', [ 'underline', 'pills', 'boxed' ], 'underline');

            if ('underline' !== $tab_style) {
                $classes[] = 'ppcart-account-nav-style-' . $tab_style;
            }

            $tab_alignment = $this->get_choice_attribute($attributes, 'tabAlignment', [ 'left', 'center', 'right', 'stretch' ], 'left');

            if ('left' !== $tab_alignment) {
                $classes[] = 'ppcart-account-nav-align-' . $tab_alignment;
            }

            $text_transform = $this->get_choice_attribute($attributes, 'tabTextTransform', [ '', 'none', 'uppercase', 'lowercase', 'capitalize' ], '');

            if ('' !== $text_transform) {
                $classes[] = 'ppcart-account-nav-transform-' . $text_transform;
            }
        }

        if ('publishpress-cart/account-tab' === $block_name) {
            $panel_style = $this->get_choice_attribute($attributes, 'panelStyle', [ 'plain', 'card', 'bordered' ], 'plain');

            if ('plain' !== $panel_style) {
                $classes[] = 'ppcart-account-tab-panel-style-' . $panel_style;
            }

            if ('plain' !== $panel_style || '' !== $this->get_account_block_style_attribute($block_name, $attributes)) {
                $classes[] = 'ppcart-account-tab-has-custom-panel';
            }

            $density = $this->get_choice_attribute($attributes, 'tableDensity', [ '', 'comfortable', 'compact' ], '');

            if ('' !== $density) {
                $classes[] = 'ppcart-account-tab-table-' . $density;
            }
        }

        if ('publishpress-cart/account-login' === $block_name) {
            if ($this->has_any_login_styles($attributes)) {
                $classes[] = 'ppcart-account-login-has-customization';
            }

            $alignment = $this->get_choice_attribute($attributes, 'formAlignment', [ '', 'left', 'center', 'right' ], '');

            if ('' !== $alignment) {
                $classes[] = 'ppcart-account-login-align-' . $alignment;
            }

            $login_block_style = $this->get_choice_attribute($attributes, 'blockStyle', [ '', 'minimal' ], '');

            if ('' !== $login_block_style) {
                $classes[] = 'ppcart-account-login-style-' . $login_block_style;
            }

            $input_shape = $this->get_choice_attribute($attributes, 'inputShape', [ '', 'pill', 'underline' ], '');

            if ('' !== $input_shape) {
                $classes[] = 'ppcart-account-login-input-' . $input_shape;
            }

            $login_hover = $this->get_choice_attribute($attributes, 'hoverEffect', [ '', 'lift', 'border' ], '');

            if ('' !== $login_hover) {
                $classes[] = 'ppcart-account-login-hover-' . $login_hover;
            }
        }

        if (
            'publishpress-cart/account-order-detail' === $block_name
                        || 'publishpress-cart/account-subscription-detail' === $block_name
        ) {
            if (isset($attributes['showBackLink']) && false === (bool) $attributes['showBackLink']) {
                $classes[] = 'ppcart-account-detail-hide-back';
            }

            $section_style = $this->get_choice_attribute($attributes, 'sectionStyle', [ '', 'card', 'bordered' ], '');

            if ('' !== $section_style) {
                $classes[] = 'ppcart-account-detail-section-' . $section_style;
            }

            if ($this->has_any_detail_styles($attributes)) {
                $classes[] = 'ppcart-account-detail-has-customization';
            }

            $detail_block_style = $this->get_choice_attribute($attributes, 'blockStyle', [ '', 'card', 'timeline', 'dashboard', 'receipt' ], '');

            if ('' !== $detail_block_style) {
                $classes[] = 'ppcart-account-detail-style-' . $detail_block_style;
            }

            $detail_layout = $this->get_choice_attribute($attributes, 'detailLayout', [ '', 'stacked', 'sidebar' ], '');

            if ('' !== $detail_layout) {
                $classes[] = 'ppcart-account-detail-layout-' . $detail_layout;
            }

            $section_shape = $this->get_choice_attribute($attributes, 'sectionShape', [ '', 'card', 'bordered', 'elevated', 'minimal' ], '');

            if ('' !== $section_shape) {
                $classes[] = 'ppcart-account-detail-shape-' . $section_shape;
            }

            $back_link_style = $this->get_choice_attribute($attributes, 'backLinkStyle', [ '', 'arrow', 'chip', 'button' ], '');

            if ('' !== $back_link_style) {
                $classes[] = 'ppcart-account-detail-back-' . $back_link_style;
            }

            $section_hover = $this->get_choice_attribute($attributes, 'sectionHoverEffect', [ '', 'lift', 'tint', 'border' ], '');

            if ('' !== $section_hover) {
                $classes[] = 'ppcart-account-detail-section-hover-' . $section_hover;
            }
        }

        if ('publishpress-cart/account-subscription-detail' === $block_name) {
            $action_shape = $this->get_choice_attribute($attributes, 'actionLinkShape', [ '', 'filled', 'ghost', 'chip' ], '');

            if ('' !== $action_shape) {
                $classes[] = 'ppcart-account-detail-action-' . $action_shape;
            }

            $pill_style = $this->get_choice_attribute($attributes, 'statusPillStyle', [ '', 'pill', 'square', 'dot' ], '');

            if ('' !== $pill_style) {
                $classes[] = 'ppcart-account-detail-status-' . $pill_style;
            }
        }

        if ('publishpress-cart/account-downloads' === $block_name) {
            $download_block_style = $this->get_choice_attribute($attributes, 'blockStyle', [ '', 'card', 'minimal', 'dashboard', 'folder' ], '');

            if ('' !== $download_block_style) {
                $classes[] = 'ppcart-account-downloads-style-' . $download_block_style;
            }

            $download_layout = $this->get_choice_attribute($attributes, 'itemLayout', [ '', 'cards', 'rows', 'tiles' ], '');

            if ('' !== $download_layout) {
                $classes[] = 'ppcart-account-downloads-layout-' . $download_layout;
            }

            $download_hover = $this->get_choice_attribute($attributes, 'itemHoverEffect', [ '', 'lift', 'tint', 'border-slide', 'reveal' ], '');

            if ('' !== $download_hover) {
                $classes[] = 'ppcart-account-downloads-hover-' . $download_hover;
            }

            $download_button_shape = $this->get_choice_attribute($attributes, 'buttonShape', [ '', 'filled', 'ghost', 'chip', 'icon' ], '');

            if ('' !== $download_button_shape) {
                $classes[] = 'ppcart-account-downloads-button-' . $download_button_shape;
            }

            if (! empty($attributes['showFileIcon'])) {
                $classes[] = 'ppcart-account-downloads-show-icon';
            }

            if ($this->has_any_downloads_styles($attributes)) {
                $classes[] = 'ppcart-account-downloads-has-customization';
            }
        }

        if (
            'publishpress-cart/account-orders' === $block_name
                        || 'publishpress-cart/account-subscriptions' === $block_name
                        || 'publishpress-cart/account-payment-plans' === $block_name
                        || 'publishpress-cart/account-profile' === $block_name
        ) {
            if ($this->has_any_leaf_styles($attributes)) {
                $classes[] = 'ppcart-account-leaf-has-customization';
            }

            if ('publishpress-cart/account-profile' === $block_name) {
                $allowed_block_styles = [ '', 'card', 'minimal', 'identity' ];
            } elseif (
                'publishpress-cart/account-orders' === $block_name
                                || 'publishpress-cart/account-subscriptions' === $block_name
                                || 'publishpress-cart/account-payment-plans' === $block_name
            ) {
                $allowed_block_styles = [ '', 'card', 'minimal', 'pill' ];
            } else {
                $allowed_block_styles = [ '', 'card', 'minimal', 'pill', 'dashboard' ];
            }

            $block_style = $this->get_choice_attribute($attributes, 'blockStyle', $allowed_block_styles, '');

            if ('' !== $block_style) {
                $classes[] = 'ppcart-account-block-style-' . $block_style;
            }
        }

        if (
            'publishpress-cart/account-orders' === $block_name
                        || 'publishpress-cart/account-subscriptions' === $block_name
                        || 'publishpress-cart/account-payment-plans' === $block_name
        ) {
            $allowed_item_layouts = [ '', 'cards' ];
            $item_layout = $this->get_choice_attribute($attributes, 'itemLayout', $allowed_item_layouts, '');

            if ('' !== $item_layout) {
                $classes[] = 'ppcart-account-item-layout-' . $item_layout;
            }

            $pill_style = ('publishpress-cart/account-orders' === $block_name
                || 'publishpress-cart/account-subscriptions' === $block_name
                || 'publishpress-cart/account-payment-plans' === $block_name)
                ? 'pill'
                : $this->get_choice_attribute($attributes, 'statusPillStyle', [ '', 'pill', 'square', 'dot' ], '');

            if ('' !== $pill_style) {
                $classes[] = 'ppcart-account-status-pill-' . $pill_style;
            }

            $action_shape = ('publishpress-cart/account-orders' === $block_name
                || 'publishpress-cart/account-subscriptions' === $block_name
                || 'publishpress-cart/account-payment-plans' === $block_name)
                ? 'chip'
                : $this->get_choice_attribute($attributes, 'actionButtonShape', [ '', 'filled', 'ghost', 'chip' ], '');

            if ('' !== $action_shape) {
                $classes[] = 'ppcart-account-action-shape-' . $action_shape;
            }
        }

        if (
            'publishpress-cart/account-orders' === $block_name
                        || 'publishpress-cart/account-subscriptions' === $block_name
        ) {
            $detail_presentation = $this->get_detail_presentation($attributes, $block_name);
            $classes[]           = 'ppcart-account-detail-presentation-' . ('' === $detail_presentation ? 'popup' : $detail_presentation);
        }

        if ('publishpress-cart/account-profile' === $block_name) {
            $layout_mode = $this->get_choice_attribute($attributes, 'layoutMode', [ '', 'compact', 'two-column' ], '');

            if ('' !== $layout_mode) {
                $classes[] = 'ppcart-account-profile-layout-' . $layout_mode;
            }

            $button_shape = $this->get_choice_attribute($attributes, 'buttonShape', [ '', 'pill', 'square', 'ghost' ], '');

            if ('' !== $button_shape) {
                $classes[] = 'ppcart-account-profile-button-' . $button_shape;
            }

            $input_hover = $this->get_choice_attribute($attributes, 'inputHoverEffect', [ '', 'glow', 'tint', 'underline' ], '');

            if ('' !== $input_hover) {
                $classes[] = 'ppcart-account-profile-input-hover-' . $input_hover;
            }

            if (! empty($attributes['showAvatar'])) {
                $classes[] = 'ppcart-account-profile-has-avatar';
            }
        }

        return implode(' ', array_filter($classes));
    }

    private function has_any_leaf_styles($attributes)
    {
        $numeric_attrs = [
            'groupHeadingFontSize', 'formMaxWidth', 'fieldGap', 'headingFontSize',
            'labelFontSize', 'inputFontSize', 'labelMinWidth', 'inputRadius', 'buttonRadius',
        ];

        foreach ($numeric_attrs as $key) {
            if (isset($attributes[ $key ]) && is_numeric($attributes[ $key ])) {
                return true;
            }
        }

        $color_attrs = [
            'emptyTextColor', 'actionLinkColor', 'actionLinkHoverColor', 'productLinkColor',
            'productLinkHoverColor', 'statusColor', 'cancelNoticeColor', 'groupHeadingColor',
            'headingColor', 'labelColor', 'inputBorderColor', 'inputFocusBorderColor',
            'buttonBackgroundColor', 'buttonTextColor',
            'editButtonBackgroundColor', 'editButtonTextColor', 'saveButtonBackgroundColor',
            'saveButtonTextColor', 'cancelButtonBackgroundColor', 'cancelButtonTextColor',
            'editButtonHoverBackgroundColor', 'editButtonHoverTextColor',
            'saveButtonHoverBackgroundColor', 'saveButtonHoverTextColor',
            'cancelButtonHoverBackgroundColor', 'cancelButtonHoverTextColor',
            'buttonHoverBackgroundColor', 'buttonHoverTextColor',
        ];

        foreach ($color_attrs as $key) {
            if (! empty($attributes[ $key ])) {
                return true;
            }
        }

        if (! empty($attributes['groupHeadingFontWeight']) || ! empty($attributes['headingFontWeight']) || ! empty($attributes['labelFontWeight'])) {
            return true;
        }

        return false;
    }

    private function has_any_login_styles($attributes)
    {
        $numeric_attrs = [ 'formMaxWidth', 'formPadding', 'formRadius', 'headingFontSize', 'descriptionFontSize', 'inputRadius', 'buttonRadius' ];

        foreach ($numeric_attrs as $key) {
            if (isset($attributes[ $key ]) && is_numeric($attributes[ $key ])) {
                return true;
            }
        }

        $color_attrs = [
            'formBackgroundColor', 'formBorderColor', 'headingColor', 'descriptionColor', 'labelColor',
            'buttonBackgroundColor', 'buttonTextColor', 'buttonHoverBackgroundColor', 'buttonHoverTextColor',
        ];

        foreach ($color_attrs as $key) {
            if (! empty($attributes[ $key ])) {
                return true;
            }
        }

        if (
            ! empty($attributes['headingFontWeight'])
            || ! empty($attributes['descriptionFontWeight'])
            || ! empty($attributes['headingAlignment'])
            || ! empty($attributes['descriptionAlignment'])
        ) {
            return true;
        }

        return false;
    }

    private function has_any_detail_styles($attributes)
    {
        $numeric_attrs = [ 'sectionPadding', 'sectionRadius', 'headingFontSize' ];

        foreach ($numeric_attrs as $key) {
            if (isset($attributes[ $key ]) && is_numeric($attributes[ $key ])) {
                return true;
            }
        }

        $color_attrs = [
            'backLinkColor', 'backLinkHoverColor',
            'sectionBackgroundColor', 'sectionBorderColor',
            'headingColor',
            'actionLinkColor', 'actionLinkHoverColor',
            'tableHeaderBackgroundColor', 'tableHeaderTextColor',
            'tableRowBackgroundColor', 'tableRowTextColor', 'tableBorderColor',
            'linkColor', 'linkHoverColor',
        ];

        foreach ($color_attrs as $key) {
            if (! empty($attributes[ $key ])) {
                return true;
            }
        }

        if (! empty($attributes['headingFontWeight'])) {
            return true;
        }

        return false;
    }

    private function has_any_downloads_styles($attributes)
    {
        $color_attrs = [
            'linkColor', 'linkHoverColor', 'itemBackgroundColor', 'itemBorderColor', 'emptyTextColor',
        ];

        foreach ($color_attrs as $key) {
            if (! empty($attributes[ $key ])) {
                return true;
            }
        }

        return false;
    }

    private function layout_has_container_styles($attributes)
    {
        $width   = $this->get_layout_width_attribute($attributes);
        $padding = $this->get_number_attribute($attributes, 'containerPadding', 0, 80);
        $radius  = $this->get_number_attribute($attributes, 'containerRadius', 0, 32);

        if (null !== $width || null !== $padding || null !== $radius) {
            return true;
        }

        foreach ([ 'containerBackgroundColor', 'containerBorderColor' ] as $color_attribute) {
            if (! empty($attributes[ $color_attribute ])) {
                return true;
            }
        }

        return false;
    }
}
