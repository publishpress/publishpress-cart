<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Admin_Order_Ajax_Trait
{
    public function send_email_test()
    {

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce value is sanitized and verified immediately below.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_ajax_nonce')) {
            esc_html_e('Ooops, something went wrong, please try again later.', 'publishpress-cart');
            die();
        }

        if (! current_user_can('manage_options') && ! ppcart_user_can('manage_orders')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'publishpress-cart'));
        }

        $order_info = ppcart_get_email_preview_order_data();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Email type is consumed only after ppcart_verify_nonce() succeeds above.
        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
        if ($type == 'registration') {
            $res = ppcart_new_user_notification($user = false, $order_info, $test = true);
        } else {
            $res = ppcart_notification_send($type, $order_info, $test = true);
        }

        if ($res) {
            esc_html_e('Test email sent.', 'publishpress-cart');
        } else {
            esc_html_e('Test email failed to send.', 'publishpress-cart');
        }

        die();
    }

    public function clean_product_meta_duplicate()
    {

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the nonce value that is verified immediately below.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_ajax_nonce')) {
            esc_html_e('Ooops, something went wrong, please try again later.', 'publishpress-cart');
            die();
        }
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Remaining request fields are read only after the AJAX nonce check above.

        if (!isset($_POST['post_id'])) {
            esc_html_e('Invalid Post ID', 'publishpress-cart');
            die();
        }

        // Get pay options
        $product_id = absint(wp_unslash($_POST['post_id']));
        if (! current_user_can('manage_options') && ! current_user_can('edit_post', $product_id)) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'publishpress-cart'));
        }

        ppcart_update_post_meta($product_id, 'stripe_prod_id', "");

        echo "success";
        die();
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    public function product_plan_options_html()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-ajax-product-plan-options-html.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function get_payment_options()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/order-ajax-ppcart-get-payment-options.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
