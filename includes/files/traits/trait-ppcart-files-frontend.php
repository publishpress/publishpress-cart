<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Files_Frontend_Trait
{
    public function download_page_rewrites()
    {
        $this->slug = self::get_download_slug();
        add_rewrite_tag(self::DOWNLOAD_REWRITE_TAG, self::DOWNLOAD_KEY_PATTERN);
        add_rewrite_rule(
            self::get_download_rewrite_regex($this->slug),
            'index.php?' . self::DOWNLOAD_QUERY_VAR . '=$matches[1]',
            'top'
        );
    }

    public function download_file()
    {
        if (get_query_var(self::DOWNLOAD_QUERY_VAR) && file_exists(plugin_dir_path(__FILE__) . 'download.php')) {
            require_once((defined('PPCART_BASE_DIR') ? PPCART_BASE_DIR : dirname(__DIR__, 2) . '/') . 'includes/files/download.php');
            exit();
        }
    }

    public function account_tabs($tabs)
    {

        $user_id = get_current_user_id();

        if (!$user_id) {
            return $tabs;
        }

        if ($orders = ppcart_get_user_orders($user_id)) {
            foreach ($orders as $order) {
                if ($this->get_order_downloads($order['ID'])) {
                    $tab = [
                        'id' => 'tab-files',
                        'title' => apply_filters('ppcart_download_tab_name', __('Downloads', 'publishpress-cart')),
                    ];
                    array_splice($tabs, 3, 0, [$tab]);
                    return $tabs;
                }
            }
        }

        return $tabs;
    }

    public function email_download_links($order)
    {
        $id = $order->id ?? $order->ID;
        if ($files = $this->get_order_downloads($id)) : ?>
                <table style="font-family:'Lato',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                    <tbody>
                        <tr>
                        <td style="overflow-wrap:break-word;word-break:break-word;padding:25px 0px 20px;font-family:'Lato',sans-serif;" align="left">
    
                            <div style="color: #303030; line-height: 120%; text-align: left; word-wrap: break-word;">
                                <p style="font-size: 14px; line-height: 120%;"><span style="font-size: 14px; line-height: 16.8px;">
                                <span style="line-height: 16.8px; font-size: 14px;"><strong><?php esc_html_e('Downloads', 'publishpress-cart'); ?></strong><br />
    
                                    <?php foreach ($files as $download) : ?>
                                        <a href="<?php echo esc_url($download->url); ?>" target="_blank"><?php echo esc_html($download->name); ?></a><br>
                                    <?php endforeach; ?>
                                    </span></span>
                                </p>
                            </div>
    
                        </td>
                        </tr>
                    </tbody>
                </table>
        <?php endif;
    }

    public function downloads_shortcode($attr)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only shortcode rendering parameter.
        $default_id = isset($_GET['ppcart-order']) ? absint(wp_unslash($_GET['ppcart-order'])) : false;
        $defaults   = [ 'id' => $default_id, 'full' => true, 'show-title' => true ];
        $attr       = shortcode_atts($defaults, $attr);

        if (! $attr['id']) {
            return;
        }

        if (! PPCart_Order::visitor_can_view(absint($attr['id']))) {
            return;
        }

        return ppcart_kses_frontend_html($this->render_order_downloads_html($attr));
    }

    /**
     * Render order download links. Callers must already hold a trusted Order
     * or have passed {@see PPCart_Order::visitor_can_view()}.
     *
     * @param array $attr Shortcode attributes (`id`, `full`, `show-title`).
     * @return string|void
     */
    public function render_order_downloads_html($attr)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/files-frontend-downloads-shortcode.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function receipt_download_links($items)
    {
        $ids = [];
        $all_files = [];

        foreach ($items['items'] as $item) {
            if (isset($item['order_id']) && !in_array($item['order_id'], $ids)) {
                if ($files = $this->get_order_downloads($item['order_id'])) {
                    $ids[] = $item['order_id'];
                    $all_files = array_merge($all_files, $files);
                }
            }
        }

        if ($all_files) : ?>
                <strong><?php esc_html_e('Your Downloads', 'publishpress-cart'); ?></strong>
                <div class="ppcart-order-table">
                    <?php foreach ($all_files as $download) : ?>
                        <div class="item">
                            <a href="<?php echo esc_url($download->url); ?>" target="_blank"><?php echo esc_html($download->name); ?></a><br>
                        </div>
                        <div class="item"></div>
                    <?php endforeach; ?>
                </div>
        <?php endif;
    }

    public function file_tab_content()
    {
        ob_start();
        $__ppcart_template_result = include __DIR__ . '/templates/files-frontend-file-tab-content.php';
        $buffered = ob_get_clean();

        if (false === $__ppcart_template_result) {
            return false;
        }

        echo ppcart_kses_frontend_html($buffered); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by ppcart_kses_frontend_html().
    }
}
