<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Account_Renderer_Tab_Content
{
    public function should_render_tab_block_preview($tab_id)
    {
        if (! $this->is_editor_preview()) {
            return false;
        }

        if ('tab-orders' === $tab_id && $this->context->get_request_id('ppcart-order')) {
            return false;
        }

        if ('tab-subscriptions' === $tab_id && $this->context->get_request_id('ppcart-plan')) {
            return false;
        }

        return true;
    }

    private function render_tab_block_preview($tab_id)
    {
        $preview_blocks = [
            'tab-orders'        => 'account-orders',
            'tab-subscriptions' => 'account-subscriptions',
            'tab-plans'         => 'account-payment-plans',
            'tab-profile'       => 'account-profile',
        ];

        if (empty($preview_blocks[ $tab_id ])) {
            return '';
        }

        $preview_file = dirname(__DIR__, 2) . '/blocks/' . $preview_blocks[ $tab_id ] . '/preview.php';

        if (! file_exists($preview_file)) {
            return '';
        }

        return require $preview_file;
    }

    public function render_tab_block_content($tab_id, $template, $attributes)
    {
        $template_output = ppcart_get_template('my-account/tabs/' . $template, '', $attributes);

        ob_start();
        do_action("ppcart_tab_content_{$tab_id}");
        $template_output .= ob_get_clean();

        return $template_output;
    }

    public function render_account_tab($attributes, $block = null)
    {
        if (! is_user_logged_in()) {
            return '';
        }

        $tab_id       = ! empty($attributes['tabId']) ? sanitize_html_class($attributes['tabId']) : 'tab-orders';
        $inner_blocks = $this->get_layout_inner_blocks($block);
        $output       = '';

        foreach ($inner_blocks as $inner_block) {
            if (empty($inner_block['blockName'])) {
                continue;
            }

            $output .= $this->render_account_tab_child($inner_block);
        }

        return $this->render_tab_panel($tab_id, $output, $attributes);
    }

    private function render_account_tab_child($inner_block)
    {
        $attributes = ! empty($inner_block['attrs']) && is_array($inner_block['attrs']) ? $inner_block['attrs'] : [];
        $block_name = $inner_block['blockName'];
        $output     = '';

        if (isset($this->tab_blocks[ $block_name ])) {
            $tab_config = $this->tab_blocks[ $block_name ];

            if ($this->should_render_tab_block_preview($tab_config['tab_id'])) {
                $output = $this->render_tab_block_preview($tab_config['tab_id']);
            } else {
                $output = $this->render_tab_block_content($tab_config['tab_id'], $tab_config['template'], $attributes);
                $output = $this->apply_tab_block_attributes($tab_config['tab_id'], $output, $attributes);
                $output = $this->append_detail_presentation_to_tab($tab_config['tab_id'], $output, $attributes);
            }
        } elseif ('publishpress-cart/account-downloads' === $block_name) {
            if ($this->is_editor_preview()) {
                $preview_file = dirname(__DIR__, 2) . '/blocks/account-downloads/preview.php';
                $output       = file_exists($preview_file) ? require $preview_file : '';
            } else {
                $output = $this->render_downloads_content();
            }
        } else {
            return render_block($inner_block);
        }

        if ('' === trim((string) $output)) {
            return '';
        }

        return $this->wrap_block_output($block_name, $attributes, $output);
    }

    private function render_order_detail($attributes)
    {
        $order_id = $this->context->get_request_or_attribute_id('ppcart-order', 'orderId', $attributes);

        if (! $order_id && $this->is_editor_preview()) {
            return $this->render_order_detail_preview($attributes);
        }

        if (! is_user_logged_in()) {
            return '';
        }

        if (! $order_id) {
            return '';
        }

        if (! $this->context->verify_user_access($order_id, 'order')) {
            return $this->render_access_denied();
        }

        $attributes['order'] = $order_id;

        return ppcart_get_template('my-account/order', 'detail', $attributes);
    }

    private function render_subscription_detail($attributes)
    {
        $subscription_id = $this->context->get_request_or_attribute_id('ppcart-plan', 'subscriptionId', $attributes);

        if (! $subscription_id && $this->is_editor_preview()) {
            return $this->render_subscription_detail_preview();
        }

        if (! is_user_logged_in()) {
            return '';
        }

        if (! $subscription_id) {
            return '';
        }

        if (! $this->context->verify_user_access($subscription_id, 'subscription')) {
            return $this->render_access_denied();
        }

        $attributes['plan'] = $subscription_id;

        return ppcart_get_template('my-account/subscription', 'detail', $attributes);
    }

    public function append_detail_presentation_to_tab($tab_id, $output, $attributes)
    {
        if ('tab-orders' === $tab_id) {
            if (! $this->context->get_request_id('ppcart-order')) {
                return $output;
            }

            $detail_attributes = [ 'showBackLink' => false ];
            $detail_output     = $this->render_order_detail($detail_attributes);

            return $output . $this->render_presented_detail(
                'publishpress-cart/account-order-detail',
                $attributes,
                $detail_attributes,
                $detail_output,
                __('Order details', 'publishpress-cart')
            );
        }

        if ('tab-subscriptions' === $tab_id) {
            if (! $this->should_render_subscription_detail_presentation()) {
                return $output;
            }

            $detail_attributes = [ 'showBackLink' => false ];
            $detail_output     = $this->render_subscription_detail($detail_attributes);

            return $output . $this->render_presented_detail(
                'publishpress-cart/account-subscription-detail',
                $attributes,
                $detail_attributes,
                $detail_output,
                __('Subscription details', 'publishpress-cart')
            );
        }

        return $output;
    }

    private function should_render_subscription_detail_presentation()
    {
        if (! $this->context->get_request_id('ppcart-plan')) {
            return false;
        }

        $manage = $this->context->get_request_string('ppcart-manage');
        $action = $this->context->get_request_string('action');

        return 'stripe' !== $manage && 'pay' !== $action;
    }

    private function render_presented_detail(
        $detail_block_name,
        $source_attributes,
        $detail_attributes,
        $detail_output,
        $dialog_label,
        $return_url = '',
        $source_block_name = ''
    ) {
        if ('' === trim((string) $detail_output)) {
            return '';
        }

        $presentation       = $this->get_detail_presentation($source_attributes, $source_block_name);
        $presentation_class = '' === $presentation ? 'popup' : $presentation;
        $return_url         = '' !== $return_url ? $return_url : $this->context->get_account_detail_return_url();
        $wrapped_detail     = $this->wrap_block_output($detail_block_name, $detail_attributes, $detail_output);
        $wrapped_detail     = ppcart_kses_frontend_html($wrapped_detail);
        $close_label        = esc_attr__('Close details', 'publishpress-cart');

        return sprintf(
            '<div class="ppcart-account-detail-presenter ppcart-account-detail-presenter--%1$s" role="presentation"><a class="ppcart-account-detail-presenter__backdrop" href="%2$s" aria-label="%3$s" data-testid="%6$s"></a><section class="ppcart-account-detail-presenter__panel" role="dialog" aria-modal="true" aria-label="%4$s"><a class="ppcart-account-detail-presenter__close" href="%2$s" aria-label="%3$s" data-testid="%7$s">&times;</a>%5$s</section></div>',
            esc_attr($presentation_class),
            esc_url($return_url),
            $close_label,
            esc_attr($dialog_label),
            $wrapped_detail,
            esc_attr(ppcart_testid('ppcart-account-detail-backdrop-close')),
            esc_attr(ppcart_testid('ppcart-account-detail-close'))
        );
    }

    public function is_editor_preview()
    {
        return $this->context->is_editor_preview();
    }

    public function prepend_login_intro($output, $attributes)
    {
        $intro = $this->render_login_intro($attributes);

        if ('' === $intro) {
            return $output;
        }

        $updated_output = preg_replace_callback(
            '/(<div[^>]+id=["\']ppcart-login["\'][^>]*>)/',
            function ($matches) use ($intro) {
                return $matches[1] . $intro;
            },
            $output,
            1
        );

        return is_string($updated_output) && '' !== $updated_output ? $updated_output : $intro . $output;
    }

    public function render_login_intro($attributes)
    {
        $heading     = ! empty($attributes['headingText']) ? trim(sanitize_text_field($attributes['headingText'])) : '';
        $description = ! empty($attributes['descriptionText']) ? trim(sanitize_textarea_field($attributes['descriptionText'])) : '';

        if ('' === $heading && '' === $description) {
            return '';
        }

        $output = '<div class="ppcart-account-login-intro">';

        if ('' !== $heading) {
            $output .= '<h3 class="ppcart-account-login-intro__heading">' . esc_html($heading) . '</h3>';
        }

        if ('' !== $description) {
            $output .= '<p class="ppcart-account-login-intro__description">' . nl2br(esc_html($description)) . '</p>';
        }

        $output .= '</div>';

        return $output;
    }

    private function render_order_detail_preview($attributes = [])
    {
        $show_back_link = ! isset($attributes['showBackLink']) || (bool) $attributes['showBackLink'];
        $output         = '<div class="ppcart-my-account ppcart-my-subscription-page">';

        if ($show_back_link) {
            $output .= '<div class="back-btn"><a href="#" data-testid="ppcart-account-order-back-preview"><img src="' . esc_url(PPCART_BASE_URL . 'public/images/arrow-back.svg') . '" alt="Icon" /> ' . esc_html__('Back', 'publishpress-cart') . '</a></div>';
        }

        $output .= '<div class="ppcart-account-subscription">';
        $output .= '<div id="ppcart-order-details"><h3>' . esc_html__('Order Details', 'publishpress-cart') . '</h3><div class="ppcart-order-table">';
        $output .= '<div class="item ppcart-heading"><strong>' . esc_html__('Product', 'publishpress-cart') . '</strong></div><div class="order-total ppcart-heading"><strong>' . esc_html__('Price', 'publishpress-cart') . '</strong></div>';
        $output .= '<div class="item">' . esc_html__('Sample Product', 'publishpress-cart') . '<br><small>' . esc_html__('One-time purchase', 'publishpress-cart') . '</small></div><div class="order-total">$50.00</div>';
        $output .= '<div class="item"><strong>' . esc_html__('Total', 'publishpress-cart') . '</strong></div><div class="order-total"><strong>$50.00</strong></div>';
        $output .= '</div></div>';
        $output .= '<table class="ppcart-subscription-table" border="0"><tr><td><h3 class="ppcart-account-title">' . esc_html__('Address', 'publishpress-cart') . '</h3>Sample Customer<br>123 Example Street<br>Springfield, USA</td><td width="200"><h3 class="ppcart-account-title">' . esc_html__('Invoice', 'publishpress-cart') . '</h3><a href="#" data-testid="ppcart-account-order-invoice-preview">' . esc_html__('Download Invoice', 'publishpress-cart') . '</a></td></tr></table>';
        $output .= '</div></div>';

        return $output;
    }

    private function render_subscription_detail_preview()
    {
        $back_label    = esc_html__('Back', 'publishpress-cart');
        $product_label = esc_html__('Sample Subscription', 'publishpress-cart');
        $price_label   = esc_html__('Premium Plan - $19/month', 'publishpress-cart');
        $cancel_label  = esc_html__('Cancel', 'publishpress-cart');
        $pause_label   = esc_html__('Pause', 'publishpress-cart');
        $details_label = esc_html__('Details', 'publishpress-cart');
        $start_label   = esc_html__('Start Date', 'publishpress-cart');
        $next_label    = esc_html__('Next Payment', 'publishpress-cart');

        ob_start();
        ?>
        <div class="ppcart-my-account ppcart-my-subscription-page">
            <div class="back-btn"><a href="#" data-testid="ppcart-account-subscription-back-preview">&larr; <?php echo esc_html($back_label); ?></a></div>
            <div class="ppcart-account-subscription">
                <div id="subscription-all" class="ppcart-content-inner">
                    <div class="ppcart-subscription-wrap">
                        <div class="ppcart-order-header"><h3 class="ppcart-heading"><?php echo esc_html($product_label); ?></h3></div>
                        <div class="ppcart-order-premium">
                            <div class="ppcart-premium-info"><?php echo esc_html($price_label); ?></div>
                            <div class="ppcart-premium-addon">
                                <a href="#" class="ppcart-cancel-sub" data-testid="ppcart-account-subscription-cancel-preview"><?php echo esc_html($cancel_label); ?></a>
                                <a href="#" class="ppcart-pause-restart-sub" data-testid="ppcart-account-subscription-pause-preview"><?php echo esc_html($pause_label); ?></a>
                            </div>
                        </div>
                    </div>
                    <div class="ppcart-subscription-wrap">
                        <div class="ppcart-order-header"><h4 class="ppcart-heading"><?php echo esc_html($details_label); ?></h4></div>
                        <table class="ppcart-subscription-table" cellpadding="6">
                            <tr><td width="200"><?php echo esc_html($start_label); ?></td><td><?php esc_html_e('January 1, 2026', 'publishpress-cart'); ?></td></tr>
                            <tr><td><?php echo esc_html($next_label); ?></td><td><?php esc_html_e('February 1, 2026', 'publishpress-cart'); ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_downloads_content()
    {
        ob_start();
        do_action('ppcart_tab_content_tab-files');
        return ob_get_clean();
    }

    public function wrap_downloads_output($output, $attributes)
    {
        if ('' === trim((string) $output)) {
            $empty_text = ! empty($attributes['emptyText'])
                ? wp_kses_post($attributes['emptyText'])
                : esc_html__('No downloads available yet.', 'publishpress-cart');

            return '<div class="ppcart-downloads-empty">' . $empty_text . '</div>';
        }

        return '<div class="ppcart-downloads-list">' . ppcart_kses_frontend_html($output) . '</div>';
    }

    public function render_tab_panel($tab_id, $content, $attributes)
    {
        if ('' === trim((string) $content)) {
            return '';
        }

        $tab_id  = sanitize_html_class($tab_id);
        $active  = $this->is_rendering_layout ? ($tab_id === $this->layout_active_tab ? ' active' : '') : (! empty($attributes['active']) ? ' active' : '');
        $content = ppcart_kses_frontend_html($content);

        return sprintf(
            $this->is_rendering_layout ? '<div id="%1$s" class="tabcontent%2$s">%3$s</div>' : '<div class="ppcart-my-account ppcart-account-list"><div id="%1$s" class="tabcontent%2$s">%3$s</div></div>',
            esc_attr($tab_id),
            esc_attr($active),
            $content
        );
    }

    public function wrap_block_output($block_name, $attributes, $output)
    {
        return $this->styles->wrap_block_output($block_name, $attributes, $output);
    }

    private function get_detail_presentation($attributes, $block_name = '')
    {
        return $this->styles->get_detail_presentation($attributes, $block_name);
    }

    public function get_login_template_attributes()
    {
        return $this->context->get_login_template_attributes();
    }

    private function render_access_denied()
    {
        return '<p class="publishpress-cart-account-block__notice">' . esc_html__('You do not have permission to access this account content.', 'publishpress-cart') . '</p>';
    }

    public function get_account_navigation_options()
    {
        return $this->context->get_account_navigation_options();
    }

    private function get_default_attributes($block_name)
    {
        $metadata = $this->get_metadata_for_block($block_name);
        $defaults = [];

        if (empty($metadata['attributes']) || ! is_array($metadata['attributes'])) {
            return $defaults;
        }

        foreach ($metadata['attributes'] as $name => $schema) {
            if (isset($schema['default'])) {
                $defaults[ $name ] = $schema['default'];
            }
        }

        return $defaults;
    }

    private function get_metadata_for_block($block_name)
    {
        if (isset($this->metadata[ $block_name ])) {
            return $this->metadata[ $block_name ];
        }

        $block_slug = str_replace('publishpress-cart/', '', $block_name);

        if (! in_array($block_slug, $this->block_slugs, true)) {
            return [];
        }

        $metadata = $this->get_metadata($block_slug);

        if (! empty($metadata['name'])) {
            $this->metadata[ $metadata['name'] ] = $metadata;
        }

        return $this->metadata[ $block_name ] ?? [];
    }
}
