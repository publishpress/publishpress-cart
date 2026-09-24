<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles admin refund requests for Stripe and PayPal orders.
 *
 * @package PPCart
 * @subpackage PPCart/admin
 */
class PPCart_Admin_Order_Refund_Controller
{
    /** @var string */
    private $plugin_name;

    /** @var string */
    private $plugin_title;

    /** @var string */
    private $version;

    /** @var \PublishPress\Stripe\StripeClient|null */
    private $stripe;

    public function __construct($plugin_name, $plugin_title, $version)
    {
        global $ppcart_stripe;

        $this->stripe = empty($ppcart_stripe['sk']) ? null : ppcart_stripe_client($ppcart_stripe['sk']);
        $this->plugin_name = $plugin_name;
        $this->plugin_title = $plugin_title;
        $this->version = $version;
    }

    public function order_refund()
    {
        global $wpdb;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the nonce value that is verified immediately below.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if ('' === $nonce || ! ppcart_verify_nonce($nonce, 'ppcart_ajax_nonce')) {
            esc_html_e('Oops, something went wrong, please try again later.', 'publishpress-cart');
            die;
        }
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Remaining request fields are read only after the AJAX nonce check above.

        $refund_amount = isset($_POST['refund_amount']) ? sanitize_text_field(wp_unslash($_POST['refund_amount'])) : '';
        if ('' === $refund_amount) {
            esc_html_e('Oops, something went wrong, please try again later.', 'publishpress-cart');
            die;
        }

        if (! current_user_can('manage_options') && ! ppcart_user_can('manage_orders')) {
            wp_send_json_error(['message' => __('Unauthorized.', 'publishpress-cart')], 403);
            wp_die();
        }

        $sanitized_refund_amount = ('0' === $refund_amount) ? 0 : $this->sanitizer('price', $refund_amount);
        $data = ppcart_parse_order_refund_request($_POST);
        $data['refund_amount'] = $sanitized_refund_amount;

        echo wp_kses_post(ppcart_order_refund($data));

        wp_die();
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    private function sanitizer($type, $data)
    {

        if (empty($type)) {
            return;
        }
        if (empty($data)) {
            return;
        }

        $return     = '';
        $sanitizer  = new PPCart_Sanitize();

        $sanitizer->set_data($data);
        $sanitizer->set_type($type);

        $return = $sanitizer->clean();

        unset($sanitizer);

        return $return;
    }
}
