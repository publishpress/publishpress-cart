<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Public_Page_Downloads_Trait
{
    public function customer_csv_export()
    {
        if (! get_query_var('ppcart-csv-export')) {
            return;
        }

        if (! current_user_can('manage_options') && ! ppcart_user_can('manage_orders')) {
            wp_die(esc_html__('Unauthorized.', 'publishpress-cart'), 403);
        }

        $nonce = ppcart_filter_input_request('_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (! is_string($nonce) || ! ppcart_verify_nonce(sanitize_text_field($nonce), 'ppcart_csv_export')) {
            wp_die(esc_html__('Invalid request.', 'publishpress-cart'), 403);
        }

        // Pro redefines PPCART_BASE_FILE to the Pro bootstrap. Use the Free plugin
        // directory (PPCART_BASE_DIR) so this still resolves under lib/vendor.
        $csv_export = defined('PPCART_BASE_DIR')
            ? PPCART_BASE_DIR . 'public/partials/csv-export.php'
            : dirname(__DIR__, 3) . '/partials/csv-export.php';
        if (! file_exists($csv_export)) {
            return;
        }

        require $csv_export;
        exit;
    }

    public function invoices_download()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/page-downloads-ppcart-invoices-download.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
