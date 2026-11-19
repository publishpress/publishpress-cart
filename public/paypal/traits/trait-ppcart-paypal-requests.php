<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Paypal_Requests_Trait
{
    public function paypal_oauthtoken()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-ppcart-paypal-oauthtoken.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    protected function build_paypal_url($order, $sub)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-ppcart-build-paypal-url.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    public function paypal_request()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-ppcart-paypal-request.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Build the pending order (and subscription) for a PayPal checkout from the posted form.
     *
     * Writes nothing. Sets the global $ppcart_product as checkout code expects.
     *
     * @return array{0: PPCart_Order, 1: PPCart_Subscription|null, 2: int}
     */
    public function paypal_build_checkout_order()
    {
        global $ppcart_product;

        $ppcart_product_id = (int) ppcart_filter_input(INPUT_POST, 'ppcart_product_id', FILTER_VALIDATE_INT);

        do_action('ppcart_before_create_main_order');

        $ppcart_product = ppcart_setup_product($ppcart_product_id);

        $ppcart_order = new PPCart_Order();
        $ppcart_order->load_from_post();
        $ppcart_order = apply_filters('ppcart_after_order_load_from_post', $ppcart_order);
        $ppcart_order->gateway_mode = get_option('_ppcart_paypal_enable_sandbox') != 'disable' ? 'test' : 'live';

        $is_sub = isset($ppcart_order->plan->type) && $ppcart_order->plan->type == 'recurring';

        if (is_array($ppcart_order->order_bumps) && ! $is_sub) {
            foreach ($ppcart_order->order_bumps as $bump) {
                if (isset($bump['plan']) && $bump['plan']->type == 'recurring') {
                    $is_sub = true;
                }
            }
        }

        $sub = $is_sub ? PPCart_Subscription::from_order($ppcart_order) : null;

        return [ $ppcart_order, $sub, $ppcart_product_id ];
    }

    /**
     * Store the pending subscription and order, then set the PayPal cancel and return URLs.
     *
     * @param PPCart_Order              $ppcart_order      Order built from the posted form.
     * @param PPCart_Subscription|null  $sub               Subscription, when the plan is recurring.
     * @param int                       $ppcart_product_id Product ID.
     * @param string                    $cancel_url        Posted cancel URL.
     * @return int Stored order ID.
     */
    public function paypal_store_checkout_order($ppcart_order, $sub, $ppcart_product_id, $cancel_url)
    {
        global $ppcart_product;

        if (! empty($sub)) {
            $sub->store();
            $ppcart_order->subscription_id = $sub->id;
        }

        $order_id = $ppcart_order->store();
        ppcart_remember_auto_login_request($order_id);
        $ppcart_order->cancel_url = esc_url_raw((string) $cancel_url);
        if (! empty($ppcart_product->upsell_path) && $ppcart_product->confirmation != 'redirect') {
            $return_url = $ppcart_product->form_action;
        } else {
            $return_url = $ppcart_product->thanks_url;
        }

        $return_url = apply_filters('ppcart_host_purchase_url', $return_url, $order_id, $ppcart_product_id);

        // Do not put the order-access token on this URL. PayPal treats `token` as its
        // own checkout parameter, so a merchant `token=` here blocks the sandbox redirect.
        $ppcart_order->return_url = add_query_arg(
            array_merge(
                [
                    'ppcart-order' => $order_id,
                    'ppcart-pid' => $ppcart_product_id,
                    'ppcart-pp' => 1,
                ],
                PPCart_Order::access_arg($order_id)
            ),
            $return_url
        );

        return (int) $order_id;
    }

    public function paypal_process_payment()
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-ppcart-paypal-process-payment.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    private function run_pdt_check($order_info, $paypal_data)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-run-pdt-check.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
         * Send a POST request to the PayPal API.
         * @param string $paypalUrl PayPal API URL.
         * @param array $args Post data.
         */

    public function doCurlRequest($paypalUrl, $args = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/paypal-requests-docurlrequest.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
