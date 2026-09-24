<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Account_Style_Attributes
{
    private function get_account_block_style_attribute($block_name, $attributes)
    {
        $style_properties = [];

        switch ($block_name) {
            case 'publishpress-cart/account-page-builder':
                $this->append_account_page_builder_styles($style_properties, $attributes);
                break;
            case 'publishpress-cart/account-navigation':
                $this->append_account_navigation_styles($style_properties, $attributes);
                break;
            case 'publishpress-cart/account-tab':
                $this->append_account_tab_styles($style_properties, $attributes);
                break;
            case 'publishpress-cart/account-login':
                $this->append_account_login_styles($style_properties, $attributes);
                break;
            case 'publishpress-cart/account-order-detail':
            case 'publishpress-cart/account-subscription-detail':
                $this->append_account_detail_styles($style_properties, $attributes);
                break;
            case 'publishpress-cart/account-orders':
                $this->append_color_styles($style_properties, $attributes, [
                    'productLinkColor'      => '--ppcart-account-orders-product-link',
                    'productLinkHoverColor' => '--ppcart-account-orders-product-link-hover',
                    'actionLinkColor'       => '--ppcart-account-orders-action-link',
                    'actionLinkHoverColor'  => '--ppcart-account-orders-action-link-hover',
                    'statusColor'           => '--ppcart-account-orders-status-color',
                    'emptyTextColor'        => '--ppcart-account-orders-empty-color',
                ]);
                break;
            case 'publishpress-cart/account-subscriptions':
                $this->append_account_leaf_styles($style_properties, $attributes);
                $this->append_color_style_property($style_properties, $attributes, 'cancelNoticeColor', '--ppcart-account-subs-cancel-notice');
                break;
            case 'publishpress-cart/account-payment-plans':
                $this->append_account_leaf_styles($style_properties, $attributes);
                break;
            case 'publishpress-cart/account-profile':
                $this->append_account_profile_styles($style_properties, $attributes);
                break;
            case 'publishpress-cart/account-downloads':
                $this->append_color_styles($style_properties, $attributes, [
                    'linkColor'            => '--ppcart-account-downloads-link-color',
                    'linkHoverColor'       => '--ppcart-account-downloads-link-hover-color',
                    'itemBackgroundColor'  => '--ppcart-account-downloads-item-background',
                    'itemBorderColor'      => '--ppcart-account-downloads-item-border-color',
                    'emptyTextColor'       => '--ppcart-account-downloads-empty-color',
                ]);
                break;
        }

        return implode(';', $style_properties);
    }

    private function append_account_page_builder_styles(&$style_properties, $attributes)
    {
        $width = $this->get_layout_width_attribute($attributes);

        if (null !== $width) {
            $style_properties[] = '--ppcart-account-page-builder-max-width:' . $width;
        }

        $this->append_number_styles($style_properties, $attributes, [
            'containerPadding' => [ '--ppcart-account-page-builder-padding', 0, 80 ],
            'containerRadius'  => [ '--ppcart-account-page-builder-radius', 0, 32 ],
        ]);
        $this->append_color_styles($style_properties, $attributes, [
            'containerBackgroundColor' => '--ppcart-account-page-builder-background',
            'containerBorderColor'     => '--ppcart-account-page-builder-border-color',
        ]);
    }

    private function append_account_navigation_styles(&$style_properties, $attributes)
    {
        $tab_gap = $this->get_number_attribute($attributes, 'tabGap', 0, 80);

        if (null !== $tab_gap && 48 !== $tab_gap) {
            $style_properties[] = '--ppcart-account-nav-gap:' . $tab_gap . 'px';
        }

        $this->append_number_styles($style_properties, $attributes, [
            'tabPaddingX'           => [ '--ppcart-account-nav-padding-x', 0, 60 ],
            'tabPaddingY'           => [ '--ppcart-account-nav-padding-y', 0, 40 ],
            'tabFontSize'           => [ '--ppcart-account-nav-font-size', 10, 32 ],
            'tabIndicatorThickness' => [ '--ppcart-account-nav-indicator', 0, 8 ],
        ]);
        $this->append_choice_styles($style_properties, $attributes, [
            'tabFontWeight' => [ '--ppcart-account-nav-font-weight', [ '', '400', '500', '600', '700' ] ],
        ]);
        $this->append_color_styles($style_properties, $attributes, [
            'accentColor'              => '--ppcart-account-nav-accent',
            'tabTextColor'             => '--ppcart-account-nav-color',
            'activeTabTextColor'       => '--ppcart-account-nav-active-color',
            'hoverTabTextColor'        => '--ppcart-account-nav-hover-color',
            'tabBackgroundColor'       => '--ppcart-account-nav-background',
            'activeTabBackgroundColor' => '--ppcart-account-nav-active-background',
            'hoverTabBackgroundColor'  => '--ppcart-account-nav-hover-background',
        ]);
    }

    private function append_account_tab_styles(&$style_properties, $attributes)
    {
        $this->append_number_styles($style_properties, $attributes, [
            'panelPadding'    => [ '--ppcart-account-panel-padding', 0, 80 ],
            'panelRadius'     => [ '--ppcart-account-panel-radius', 0, 32 ],
            'headingFontSize' => [ '--ppcart-account-heading-font-size', 12, 48 ],
            'buttonRadius'    => [ '--ppcart-account-button-radius', 0, 32 ],
        ]);
        $this->append_choice_styles($style_properties, $attributes, [
            'headingFontWeight' => [ '--ppcart-account-heading-font-weight', [ '', '400', '500', '600', '700' ] ],
        ]);
        $this->append_color_styles($style_properties, $attributes, [
            'panelBackgroundColor'      => '--ppcart-account-panel-background',
            'panelTextColor'            => '--ppcart-account-panel-color',
            'panelBorderColor'          => '--ppcart-account-panel-border-color',
            'panelLinkColor'            => '--ppcart-account-panel-link-color',
            'panelLinkHoverColor'       => '--ppcart-account-panel-link-hover-color',
            'headingColor'              => '--ppcart-account-heading-color',
            'tableHeaderBackgroundColor' => '--ppcart-account-table-header-background',
            'tableHeaderTextColor'      => '--ppcart-account-table-header-color',
            'tableRowBackgroundColor'   => '--ppcart-account-table-row-background',
            'tableRowAltBackgroundColor' => '--ppcart-account-table-row-alt-background',
            'tableRowTextColor'         => '--ppcart-account-table-row-color',
            'tableBorderColor'          => '--ppcart-account-table-border-color',
            'buttonBackgroundColor'     => '--ppcart-account-button-background',
            'buttonTextColor'           => '--ppcart-account-button-color',
            'buttonHoverBackgroundColor' => '--ppcart-account-button-hover-background',
            'buttonHoverTextColor'      => '--ppcart-account-button-hover-color',
        ]);
    }

    private function append_account_login_styles(&$style_properties, $attributes)
    {
        $this->append_number_styles($style_properties, $attributes, [
            'formMaxWidth'        => [ '--ppcart-account-login-max-width', 280, 800 ],
            'formPadding'         => [ '--ppcart-account-login-padding', 0, 80 ],
            'formRadius'          => [ '--ppcart-account-login-radius', 0, 32 ],
            'headingFontSize'     => [ '--ppcart-account-login-heading-font-size', 14, 48 ],
            'descriptionFontSize' => [ '--ppcart-account-login-description-font-size', 12, 30 ],
            'inputRadius'         => [ '--ppcart-account-login-input-radius', 0, 24 ],
            'buttonRadius'        => [ '--ppcart-account-login-button-radius', 0, 32 ],
        ]);
        $this->append_choice_styles($style_properties, $attributes, [
            'headingFontWeight'     => [ '--ppcart-account-login-heading-font-weight', [ '', '400', '500', '600', '700' ] ],
            'headingAlignment'      => [ '--ppcart-account-login-heading-align', [ '', 'left', 'center', 'right' ] ],
            'descriptionFontWeight' => [ '--ppcart-account-login-description-font-weight', [ '', '400', '500', '600', '700' ] ],
            'descriptionAlignment'  => [ '--ppcart-account-login-description-align', [ '', 'left', 'center', 'right' ] ],
        ]);
        $this->append_color_styles($style_properties, $attributes, [
            'formBackgroundColor'       => '--ppcart-account-login-background',
            'formBorderColor'           => '--ppcart-account-login-border-color',
            'headingColor'              => '--ppcart-account-login-heading-color',
            'descriptionColor'          => '--ppcart-account-login-description-color',
            'labelColor'                => '--ppcart-account-login-label-color',
            'buttonBackgroundColor'     => '--ppcart-account-login-button-background',
            'buttonTextColor'           => '--ppcart-account-login-button-color',
            'buttonHoverBackgroundColor' => '--ppcart-account-login-button-hover-background',
            'buttonHoverTextColor'      => '--ppcart-account-login-button-hover-color',
        ]);
    }

    private function append_account_detail_styles(&$style_properties, $attributes)
    {
        $this->append_number_styles($style_properties, $attributes, [
            'sectionPadding'  => [ '--ppcart-account-detail-section-padding', 0, 80 ],
            'sectionRadius'   => [ '--ppcart-account-detail-section-radius', 0, 32 ],
            'headingFontSize' => [ '--ppcart-account-detail-heading-font-size', 12, 48 ],
        ]);
        $this->append_choice_styles($style_properties, $attributes, [
            'headingFontWeight' => [ '--ppcart-account-detail-heading-font-weight', [ '', '400', '500', '600', '700' ] ],
        ]);
        $this->append_color_styles($style_properties, $attributes, [
            'backLinkColor'              => '--ppcart-account-detail-back-color',
            'backLinkHoverColor'         => '--ppcart-account-detail-back-hover-color',
            'sectionBackgroundColor'     => '--ppcart-account-detail-section-background',
            'sectionBorderColor'         => '--ppcart-account-detail-section-border-color',
            'headingColor'               => '--ppcart-account-detail-heading-color',
            'tableHeaderBackgroundColor' => '--ppcart-account-detail-table-header-background',
            'tableHeaderTextColor'       => '--ppcart-account-detail-table-header-color',
            'tableRowBackgroundColor'    => '--ppcart-account-detail-table-row-background',
            'tableRowTextColor'          => '--ppcart-account-detail-table-row-color',
            'tableBorderColor'           => '--ppcart-account-detail-table-border-color',
            'linkColor'                  => '--ppcart-account-detail-link-color',
            'linkHoverColor'             => '--ppcart-account-detail-link-hover-color',
            'actionLinkColor'            => '--ppcart-account-detail-action-color',
            'actionLinkHoverColor'       => '--ppcart-account-detail-action-hover-color',
        ]);
    }

    private function append_account_leaf_styles(&$style_properties, $attributes)
    {
        $this->append_color_styles($style_properties, $attributes, [
            'actionLinkColor'      => '--ppcart-account-leaf-action-link',
            'actionLinkHoverColor' => '--ppcart-account-leaf-action-link-hover',
            'statusColor'          => '--ppcart-account-leaf-status-color',
            'emptyTextColor'       => '--ppcart-account-leaf-empty-color',
        ]);
    }

    private function append_account_profile_styles(&$style_properties, $attributes)
    {
        $this->append_number_styles($style_properties, $attributes, [
            'formMaxWidth'    => [ '--ppcart-account-profile-max-width', 320, 1200 ],
            'fieldGap'        => [ '--ppcart-account-profile-field-gap', 0, 80 ],
            'headingFontSize' => [ '--ppcart-account-profile-heading-font-size', 14, 48 ],
            'labelFontSize'   => [ '--ppcart-account-profile-label-font-size', 10, 28 ],
            'inputFontSize'   => [ '--ppcart-account-profile-input-font-size', 10, 28 ],
            'labelMinWidth'   => [ '--ppcart-account-profile-label-min-width', 80, 320 ],
            'inputRadius'     => [ '--ppcart-account-profile-input-radius', 0, 24 ],
            'buttonRadius'    => [ '--ppcart-account-profile-button-radius', 0, 32 ],
        ]);
        $this->append_choice_styles($style_properties, $attributes, [
            'headingFontWeight' => [ '--ppcart-account-profile-heading-font-weight', [ '', '400', '500', '600', '700' ] ],
            'labelFontWeight'   => [ '--ppcart-account-profile-label-font-weight', [ '', '400', '500', '600', '700' ] ],
        ]);
        $this->append_color_styles($style_properties, $attributes, [
            'headingColor'                => '--ppcart-account-profile-heading-color',
            'labelColor'                  => '--ppcart-account-profile-label-color',
            'inputBorderColor'            => '--ppcart-account-profile-input-border',
            'inputFocusBorderColor'       => '--ppcart-account-profile-input-focus-border',
            'buttonBackgroundColor'       => '--ppcart-account-profile-button-background',
            'buttonTextColor'             => '--ppcart-account-profile-button-color',
            'editButtonBackgroundColor'   => '--ppcart-account-profile-edit-button-background',
            'editButtonTextColor'         => '--ppcart-account-profile-edit-button-color',
            'editButtonHoverBackgroundColor' => '--ppcart-account-profile-edit-button-hover-background',
            'editButtonHoverTextColor'    => '--ppcart-account-profile-edit-button-hover-color',
            'saveButtonBackgroundColor'   => '--ppcart-account-profile-save-button-background',
            'saveButtonTextColor'         => '--ppcart-account-profile-save-button-color',
            'saveButtonHoverBackgroundColor' => '--ppcart-account-profile-save-button-hover-background',
            'saveButtonHoverTextColor'    => '--ppcart-account-profile-save-button-hover-color',
            'cancelButtonBackgroundColor' => '--ppcart-account-profile-cancel-button-background',
            'cancelButtonTextColor'       => '--ppcart-account-profile-cancel-button-color',
            'cancelButtonHoverBackgroundColor' => '--ppcart-account-profile-cancel-button-hover-background',
            'cancelButtonHoverTextColor'  => '--ppcart-account-profile-cancel-button-hover-color',
            'buttonHoverBackgroundColor'  => '--ppcart-account-profile-button-hover-background',
            'buttonHoverTextColor'        => '--ppcart-account-profile-button-hover-color',
        ]);
    }

    private function append_number_styles(&$style_properties, $attributes, $styles)
    {
        foreach ($styles as $attribute => $style) {
            $value = $this->get_number_attribute($attributes, $attribute, $style[1], $style[2]);

            if (null !== $value) {
                $style_properties[] = $style[0] . ':' . $value . 'px';
            }
        }
    }

    private function append_choice_styles(&$style_properties, $attributes, $styles)
    {
        foreach ($styles as $attribute => $style) {
            $value = $this->get_choice_attribute($attributes, $attribute, $style[1], '');

            if ('' !== $value) {
                $style_properties[] = $style[0] . ':' . $value;
            }
        }
    }

    private function append_color_styles(&$style_properties, $attributes, $styles)
    {
        foreach ($styles as $attribute => $property) {
            $this->append_color_style_property($style_properties, $attributes, $attribute, $property);
        }
    }
}
