<?php

if (! defined('ABSPATH')) {
    exit;
}

trait PPCart_Paypal_Subscriptions_Trait
{
    public function paypal_refund($data, $order)
    {

        $access_token = $this->paypal_oauthtoken();

        $ret = '';
        $payment_intent = trim($order->transaction_id);
        $ppcart_currency = get_option('_ppcart_currency');
        $amount = $data['refund_amount'];
        if (get_option('_ppcart_paypal_enable_sandbox') == 'enable') {
            $refundurl = 'https://api.sandbox.paypal.com/v1/payments/sale/';
        } else {
            $refundurl = 'https://api.paypal.com/v1/payments/sale/';
        }
        $amount = ['amount' => ['total' => $amount, 'currency' => $ppcart_currency], 'description' => 'Cancelled in PublishPress Cart'];
        $amounts = wp_json_encode($amount);

        $response = ppcart_safe_remote_post(
            $refundurl . rawurlencode($payment_intent) . '/refund',
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $access_token,
                ],
                'body'    => $amounts,
                'timeout' => 3,
            ]
        );

        if (is_wp_error($response)) {
            $ret = 'Error:' . $response->get_error_message();
        }

        $respons = is_wp_error($response) ? null : json_decode(wp_remote_retrieve_body($response));

        if (isset($respons->state) && $respons->state == 'completed') {
            $order->refund_log($data['refund_amount'], $respons->id);
            return;
        } else {
            $ret = $respons->message ?? $ret;
        }

        throw new Exception(esc_html(sanitize_text_field($ret)));
    }

    public function paypal_pause_restart_subscription($return, $sub, $type)
    {

        if ($sub->pay_method != 'paypal') {
            return $return;
        }
        $status = 'paused';
        $paypalUrl = $this->api_url . '/billing/subscriptions/' . $sub->subscription_id . '/suspend';

        if ($type == 'started') {
            $paypalUrl = $this->api_url . '/billing/subscriptions/' . $sub->subscription_id . '/activate';
            $status = 'active';
        }

        $sub_args = ['reason' => $type . ' subscription'];
        $response = $this->doCurlRequest($paypalUrl, $sub_args);
        return $this->handleCurlResponse($response, $sub, $status);
    }

    public function paypal_cancel_subscription($canceled, $sub, $sub_id, $now = true)
    {
        global $ppcart_currency;

        if ($sub->pay_method != 'paypal') {
            return $canceled;
        }

        $access_token = $this->paypal_oauthtoken();
        $enableSandbox = get_option('_ppcart_paypal_enable_sandbox');
        $paypalUrl = ($enableSandbox != 'disable') ? 'https://api.sandbox.paypal.com/v1/billing/subscriptions/' : 'https://api.paypal.com/v1/billing/subscriptions/';
        $subscription_id = $sub->subscription_id;

        $paypal_request_url = $paypalUrl . $subscription_id . '/cancel';
        $sub_args = ['reason' => 'Not satisfied with the service'];

        $response = ppcart_safe_remote_post(
            $paypal_request_url,
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $access_token,
                ],
                'body'    => wp_json_encode($sub_args),
                'timeout' => 3,
            ]
        );

        if (is_wp_error($response)) {
            $canceled = false;
            echo esc_html('Error:' . $response->get_error_message());
        }

        $results = is_wp_error($response) ? null : json_decode(wp_remote_retrieve_body($response));
        if (empty($results)) {
            $canceled = true;
            $sub->cancel_date = gmdate('Y-m-d');

            if ($now) {
                $sub->status = 'canceled';
                $sub->sub_status = 'canceled';
            } else {
                // change status at next bill date
                $sub->cancel_at();
            }

            $sub->store();
        } else {
            if (isset($results->message)) {
                echo esc_html($results->message);
            } elseif (isset($results->error_description)) {
                echo esc_html($results->error_description);
            }
            $canceled = false;
        }

        return $canceled;
    }

    public function handleCurlResponse($response, $sub, $status = 'active')
    {

        $return = false;

        if (empty($response)) {
            $return = true;
            $sub->status = $status;
            $sub->sub_status = $status;
            $sub->store();
        } else {
            if (isset($response->message)) {
                echo esc_html($response->message);
            } elseif (isset($response->error_description)) {
                echo esc_html($response->error_description);
            }
        }

        return $return;
    }
}
