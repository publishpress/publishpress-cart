<?php

if (! defined('ABSPATH')) {
    exit;
}


function ppcart_validate_payment_key()
{
    $action_needed = [];
    if (get_option('_ppcart_stripe_enable') == '1') {
        $credentials_status = ppcart_get_stripe_platform_credentials_status();
        $credentials = $credentials_status['credentials'];
        $ppcart_stripe['mode'] = $credentials['mode'];
        $ppcart_stripe['sk'] = $credentials['sk'];
        $ppcart_stripe['pk'] = $credentials['pk'];

        if ('live' === $ppcart_stripe['mode']) {
            if (! $credentials_status['is_usable']) {
                $action_needed['stripe'] = __('Stripe keys are not configured in a readable format for the active mode. Use Connect with Stripe to sync keys directly.', 'publishpress-cart');
            } elseif ($credentials_status['requires_reconnect']) {
                $action_needed['stripe'] = __('Stripe is using legacy credentials that are not stored as direct Stripe keys. Use Connect with Stripe to sync keys before removing payment decryptor support.', 'publishpress-cart');
            } else {
                $stripe = ppcart_stripe_client($ppcart_stripe['sk']);
                try {
                    $stripe->webhookEndpoints->all(['limit' => 1]);
                } catch (\Exception $e) {
                    $message = (string) $e->getMessage();

                    if ('' !== $message) {
                        $action_needed['stripe'] = $message; //add custom message
                    }
                }
            }
        }
    }
    return apply_filters('ppcart_integration_validation_error', $action_needed);
}

//UNSUBSCRIBE CUSTOMER
function ppcart_unsubscribe_customer()
{

    global $wpdb, $ppcart_stripe, $current_user;

    wp_get_current_user();

    // Verify nonce
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the nonce value that is verified immediately in this condition.
    if (!isset($_POST['nonce']) || !ppcart_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ppcart_ajax_nonce')) {
        esc_html_e('Oops, something went wrong, please try again later.', 'publishpress-cart');
        die;
    }
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- Remaining request fields are read only after the AJAX nonce check above.

    if (! is_user_logged_in()) {
        wp_send_json_error([ 'error' => __('Authentication required.', 'publishpress-cart') ], 401);
    }

    $post_id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
    $sub_id = isset($_POST['subscription_id']) ? sanitize_text_field(wp_unslash($_POST['subscription_id'])) : '';
    $sub = new PPCart_Subscription($post_id);
    $order = $sub->get_data();

    if (!isset($order['subscription_id'])) {
        esc_html_e('Invalid subscription ID', 'publishpress-cart');
        wp_die();
    }

    $owner_user_id = isset($order['user_account']) ? absint($order['user_account']) : 0;
    $current_user_id = get_current_user_id();
    $can_manage_subscription = current_user_can('manage_options') || ppcart_user_can('manage_orders');

    if ($owner_user_id !== $current_user_id && ! $can_manage_subscription) {
        wp_send_json_error([ 'error' => __('Permission denied.', 'publishpress-cart') ], 403);
    }

    $plan = ppcart_plan($order['option_id'], '', $order['product_id']);
    if (!$plan) {
        $plan = ppcart_plan($order['plan_id'], '', $order['product_id']);
    }
    if ($plan && isset($plan->cancel_immediately) && $plan->cancel_immediately == 'no') {
        $now = false;
    } else {
        $now = true;
    }

    $is_stripe_subscription = isset($order['pay_method']) && 'stripe' === $order['pay_method'];
    $refund_action = 'no_refund';

    if ($is_stripe_subscription) {
        $cancel_timing = isset($_POST['cancel_timing']) ? sanitize_key(wp_unslash($_POST['cancel_timing'])) : '';
        if ('immediate' === $cancel_timing) {
            $now = true;
        } elseif ('period_end' === $cancel_timing) {
            $now = false;
        }

        $refund_action = isset($_POST['refund_action']) ? sanitize_key(wp_unslash($_POST['refund_action'])) : 'no_refund';
        if ('refund' === $refund_action && ! $can_manage_subscription) {
            wp_send_json_error([ 'error' => __('Permission denied.', 'publishpress-cart') ], 403);
        }
    }

    ppcart_do_cancel_subscription(
        $sub,
        $sub_id,
        $now,
        true,
        [
            'cancel_timing' => $now ? 'immediate' : 'period_end',
            'refund_action' => $now ? $refund_action : 'no_refund',
        ]
    );

    // phpcs:enable WordPress.Security.NonceVerification.Missing
    wp_die();
}

function ppcart_get_stripe_resource_value($resource, $key, $default = null)
{
    if (is_array($resource) && array_key_exists($key, $resource)) {
        return $resource[ $key ];
    }

    if (is_object($resource) && isset($resource->{$key})) {
        return $resource->{$key};
    }

    return $default;
}

function ppcart_get_stripe_resource_id($resource)
{
    if (is_string($resource)) {
        return $resource;
    }

    return (string) ppcart_get_stripe_resource_value($resource, 'id', '');
}

function ppcart_get_subscription_cancel_success_response($refund_error = '')
{
    $refund_error = trim((string) $refund_error);
    if ('' === $refund_error) {
        return 'OK';
    }

    return 'OK_REFUND_FAILED|' . rawurlencode($refund_error);
}

function ppcart_issue_stripe_subscription_cancellation_refund($sub, $stripe_sub, $stripe)
{
    $latest_invoice = ppcart_get_stripe_resource_value($stripe_sub, 'latest_invoice', '');
    $invoice_id     = ppcart_get_stripe_resource_id($latest_invoice);

    if ('' === $invoice_id) {
        throw new \Exception(esc_html__('Unable to refund subscription because Stripe did not return a latest invoice.', 'publishpress-cart'));
    }

    $invoice = is_string($latest_invoice) ? $stripe->invoices->retrieve($invoice_id) : $latest_invoice;
    if (! $invoice) {
        throw new \Exception(esc_html__('Unable to refund subscription because the Stripe invoice could not be loaded.', 'publishpress-cart'));
    }

    $amount_paid                      = absint(ppcart_get_stripe_resource_value($invoice, 'amount_paid', 0));
    $post_payment_credit_notes_amount = absint(ppcart_get_stripe_resource_value($invoice, 'post_payment_credit_notes_amount', 0));
    $max_refund_amount                = max(0, $amount_paid - $post_payment_credit_notes_amount);

    $refund_amount = min(
        $max_refund_amount,
        absint(apply_filters('ppcart_subscription_cancel_refund_amount', $max_refund_amount, $sub, $invoice, $stripe_sub))
    );
    if ($refund_amount <= 0) {
        throw new \Exception(esc_html__('Unable to refund subscription because the latest Stripe invoice has no refundable paid amount.', 'publishpress-cart'));
    }

    $credit_note_args = [
        'invoice'       => $invoice_id,
        'amount'        => $refund_amount,
        'refund_amount' => $refund_amount,
        'reason'        => 'order_change',
        'metadata'      => [
            'ppcart_subscription_id'        => (string) $sub->id,
            'ppcart_stripe_subscription_id' => (string) ppcart_get_stripe_resource_value($stripe_sub, 'id', $sub->subscription_id ?? ''),
        ],
    ];

    $credit_note_args = apply_filters('ppcart_subscription_cancel_credit_note_args', $credit_note_args, $sub, $invoice, $stripe_sub);
    $credit_note      = $stripe->creditNotes->create($credit_note_args);
    $credit_note_id   = ppcart_get_stripe_resource_id($credit_note);

    if ('' !== $credit_note_id) {
        ppcart_log_entry(
            $sub->id,
            sprintf(
                /* translators: %s Stripe credit note ID. */
                __('Stripe credit note %s created while canceling subscription.', 'publishpress-cart'),
                $credit_note_id
            )
        );
    }

    if (class_exists('PPCart_Stripe_Sync')) {
        $refund = ppcart_get_stripe_resource_value($credit_note, 'refund', null);
        if (is_string($refund) && '' !== $refund) {
            $refund = $stripe->refunds->retrieve($refund);
        }

        if ($refund) {
            PPCart_Stripe_Sync::sync_refund($refund, $stripe, null, true);
        }
    }

    return $credit_note;
}

/**
 * Cancel a subscription at the gateway and locally.
 *
 * @param PPCart_Subscription|int $sub     Subscription object or post ID.
 * @param string|false            $sub_id  Gateway subscription ID, or false to use stored meta.
 * @param bool                    $now     True to cancel immediately.
 * @param bool                    $echo    True to print the response instead of returning it.
 * @param array                   $options Extra cancel options.
 * @return string|void
 */
function ppcart_do_cancel_subscription($sub, $sub_id = false, $now = true, $echo = true, $options = [])
{

    global $ppcart_stripe;

    if (is_numeric($sub)) {
        $sub = new PPCart_Subscription($sub);
    }

    if (! is_array($options)) {
        $options = [];
    }

    $refund_action = isset($options['refund_action']) ? sanitize_key($options['refund_action']) : 'no_refund';
    if (! $now) {
        $refund_action = 'no_refund';
    }

    if (!$sub_id) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Called from internal cancellation flow where nonce is already validated at entrypoint.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Internal cancellation flow reads posted subscription identifier.
        if (isset($_POST['subscription_id'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Internal cancellation flow reads posted subscription identifier.
            $sub_id = sanitize_text_field(wp_unslash($_POST['subscription_id']));
        } else {
            $sub_id = $sub->subscription_id;
        }
    }

    $canceled = false;
    $refund_error = '';

    if ($sub->pay_method == 'stripe') {
        //stripe

        $apikey = $ppcart_stripe['sk'];
        $stripe = ppcart_stripe_client($apikey);
        try {
            $stripesub = $stripe->subscriptions->retrieve($sub_id);
            $sub->cancel_date = gmdate('Y-m-d');
            if ($now) {
                // cancel now
                $stripesub = $stripe->subscriptions->cancel($sub_id, []);
                if ($stripesub->status == "canceled") {
                    if (class_exists('PPCart_Stripe_Sync')) {
                        PPCart_Stripe_Sync::sync_subscription_resource($stripesub, $stripe, null, true);
                    } else {
                        $sub->cancel_date = gmdate('Y-m-d');
                        $sub->status = 'canceled';
                        $sub->sub_status = 'canceled';
                        $sub->store();
                    }
                    if ('refund' === $refund_action) {
                        try {
                            ppcart_issue_stripe_subscription_cancellation_refund($sub, $stripesub, $stripe);
                        } catch (\Exception $refund_exception) {
                            $refund_error = $refund_exception->getMessage();
                            ppcart_log_entry(
                                $sub->id,
                                sprintf(
                                    /* translators: %s Stripe refund failure message. */
                                    __('Stripe refund failed after subscription cancellation: %s', 'publishpress-cart'),
                                    $refund_error
                                )
                            );
                        }
                    }
                    $canceled = true;
                } else {
                    esc_html_e('Unable to cancel subscription.', 'publishpress-cart');
                }
            } else {
                // cancel later
                $stripe = ppcart_stripe_client($apikey);
                $stripesub = $stripe->subscriptions->update($sub_id, ['cancel_at_period_end' => true]);

                if (isset($stripesub->cancel_at) && $stripesub->cancel_at) {
                    if (class_exists('PPCart_Stripe_Sync')) {
                        PPCart_Stripe_Sync::sync_subscription_resource($stripesub, $stripe, null, true);
                    } else {
                        $sub->cancel_date = gmdate('Y-m-d');
                        $sub->cancel_at($stripesub->cancel_at);
                        $sub->store();
                    }
                    $canceled = true;
                } else {
                    esc_html_e('Unable to cancel subscription.', 'publishpress-cart');
                }
            }
        } catch (\Exception $e) {
            if ($echo) {
                echo esc_html($e->getMessage()); //add custom message
            } else {
                return esc_html($e->getMessage());
            }
        }
    }

    $canceled = apply_filters('ppcart_cancel_subscription', $canceled, $sub, $sub_id, $now, $options);

    if ($canceled) {
        $current_user = wp_get_current_user();
        //update status
        /* translators: %s: user login. */
        $log_entry = sprintf(__('Subscription canceled by %s', 'publishpress-cart'), esc_html($current_user->user_login));
        ppcart_log_entry($sub->id, $log_entry);
        $response = ppcart_get_subscription_cancel_success_response($refund_error);
        if ($echo) {
            echo esc_html($response);
        } else {
            return $response;
        }
    }
}

function ppcart_order_refund($data)
{
    global $wpdb;
    $postID = intval($data['id']);
    $order = new PPCart_Order($postID);
    $prodID = intval($order->product_id);
    $data['refund_amount'] ??= $order->amount;
    $stripe_refund_synced = false;

    do_action('ppcart_before_order_refund', $data);

    if ($order->pay_method == 'free' || $order->pay_method == 'cod') {
        $amount = $data['refund_amount'];
        $order->refund_log($amount, 'manual');
    } elseif (!isset($order->transaction_id)) {
        return esc_html__('INVALID CHARGE ID', 'publishpress-cart');
    } elseif ($order->pay_method == 'stripe') {
        //stripe
        $gateway_mode = $data['mode'] ?? $order->gateway_mode;
        $apikey = ppcart_get_sensitive_option('_ppcart_stripe_' . sanitize_text_field($gateway_mode) . '_sk');
        if (empty($apikey)) {
            /* translators: %s: Stripe gateway mode (e.g. live or test) */
            return sprintf(esc_html__('Oops, Stripe %s key missing!', 'publishpress-cart'), esc_html($gateway_mode));
        }
        $stripe = ppcart_stripe_client($apikey);

        try {
            $refund_amount = ppcart_price_in_cents($data['refund_amount']);
            $refund_args = ['amount' => $refund_amount];

            if (substr($order->transaction_id, 0, 2) == 'pi') {
                $refund_args['payment_intent'] = $order->transaction_id;
            } else {
                $refund_args['charge'] = $order->transaction_id;
            }

            $refund = $stripe->refunds->create($refund_args);
            if (isset($refund->id) && $refund->status == "succeeded") {
                if (class_exists('PPCart_Stripe_Sync')) {
                    $stripe_refund_synced = (bool) PPCart_Stripe_Sync::sync_refund($refund, $stripe, null, true);
                } else {
                    $order->refund_log($data['refund_amount'], $refund->id);
                }
            } else {
                return sprintf('Something went wrong, Stripe refund ID: %s and refund status: %s', ($refund->id ?? ''), ($refund->status ?? ''));
            }
        } catch (\Exception $e) {
            return $e->getMessage(); //add custom message
        }
    } else {
        try {
            do_action('ppcart_order_refund_' . $order->pay_method, $data, $order);
        } catch (\Exception $e) {
            return $e->getMessage(); //add custom message
        }
    }

    if (! $stripe_refund_synced) {
        ppcart_update_post_meta($postID, 'refund_amount', $data['refund_amount']);

        $order->status = 'refunded';
        $order->payment_status = 'refunded';
        $order->store();
    }

    $current_user = wp_get_current_user();
    $log_entry = __('Payment refunded by', 'publishpress-cart') . ' ' . $current_user->user_login;
    ppcart_log_entry($postID, $log_entry);

    if ($data['restock'] == 'YES') {
        ppcart_maybe_update_stock($prodID, 'increase');
        ppcart_update_post_meta($postID, 'refund_restock', 'YES');
    }

    return 'OK';
}

/**
 * Pause-Restart Subscription
 */
add_action('wp_ajax_ppcart_pause_restart_subscription', 'ppcart_pause_restart_subscription');

function ppcart_pause_restart_subscription()
{

    global $ppcart_stripe;
    $response = false;

    // Verify nonce
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- This reads the nonce value that is verified immediately in this condition.
    if (!isset($_POST['nonce']) || !ppcart_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ppcart_ajax_nonce')) {
        esc_html_e('Oops, something went wrong, please try again later.', 'publishpress-cart');
        die;
    }
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- Remaining request fields are read only after the AJAX nonce check above.

    $post_id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
    $sub = new PPCart_Subscription($post_id);

    $owner_user_id = isset($sub->user_account) ? absint($sub->user_account) : 0;
    $current_user_id = get_current_user_id();
    if ($owner_user_id !== $current_user_id && ! current_user_can('manage_options')) {
        wp_send_json_error([ 'error' => __('Permission denied.', 'publishpress-cart') ], 403);
    }

    $type = sanitize_text_field(wp_unslash($_POST['type'] ?? ''));
    $status = ($type == 'started') ? 'active' : $type;

    if ($sub->pay_method == 'stripe') {
        //stripe

        $apikey = $ppcart_stripe['sk'];
        $stripe = ppcart_stripe_client($apikey);
        try {
            $data = [
                'pause_collection' => [
                    'behavior' => 'void',
                ],
            ];
            if ($type == 'started') {
                $data = [
                    'pause_collection' => '',
                ];
            }
            $stripesub = $stripe->subscriptions->update($sub->subscription_id, $data);
            $response = $stripesub->current_period_end;
        } catch (\Exception $e) {
            echo esc_html($e->getMessage()); //add custom message
        }
    } else {
        $response = apply_filters('ppcart_subscription_pause_restart', $response, $sub, $type);
    }

    if ($response) {
        $current_user = wp_get_current_user();

        if ($type == 'paused') {
            /* translators: %s: user login. */
            $log_entry = sprintf(__('Subscription paused by %s', 'publishpress-cart'), esc_html($current_user->user_login));
        } else {
            /* translators: %s: user login. */
            $log_entry = sprintf(__('Subscription started by %s', 'publishpress-cart'), esc_html($current_user->user_login));
        }

        if ($sub->pay_method == 'stripe' && isset($stripesub) && class_exists('PPCart_Stripe_Sync')) {
            PPCart_Stripe_Sync::sync_subscription_resource($stripesub, $stripe, null, true);
        } else {
            $sub->status = $status;
            $sub->sub_status = $status;

            if ($type != 'paused' && $sub->pay_method == 'stripe') {
                $sub->sub_next_bill_date = $response;
            }

            $sub->store();
        }

        ppcart_log_entry($sub->id, $log_entry);
        echo 'OK';
        exit;
    }
    // phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Check if a CSV file is valid.
 *
 * @since 1.0.0
 * @param string $file       File name.
 * @param bool   $check_path If should check for the path.
 * @return bool
 */
function ppcart_is_file_valid_csv($file, $check_path = true)
{
    /**
     * Filter check for CSV file path.
     *
     * @since 1.0.0
     * @param bool   $check_import_file_path If requires file path check. Defaults to true.
     * @param string $file                   Path of the file to be checked.
     */
    $check_import_file_path = apply_filters('ppcart_csv_import_check_file_path', true, $file);

    if ($check_path && $check_import_file_path && false !== stripos($file, '://')) {
        return false;
    }

    /**
     * Filter CSV valid file types.
     *
     * @since 1.0.0
     * @param array $valid_filetypes List of valid file types.
     */
    $valid_filetypes = apply_filters(
        'ppcart_csv_import_valid_filetypes',
        [
            'csv' => 'text/csv',
            'txt' => 'text/plain',
        ]
    );

    $filetype = wp_check_filetype($file, $valid_filetypes);

    if (in_array($filetype['type'], $valid_filetypes, true)) {
        return true;
    }

    return false;
}

/**
 * Wrapper for set_time_limit to see if it is enabled.
 *
 * @since 1.0.0
 * @param int $limit Time limit.
 */
function ppcart_set_time_limit($limit = 0)
{
    if (function_exists('set_time_limit') && false === strpos(ini_get('disable_functions'), 'set_time_limit') && ! ini_get('safe_mode')) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
        @set_time_limit($limit); // @codingStandardsIgnoreLine
    }
}

add_action('wp_ajax_ppcart_update_user_profile', 'ppcart_update_user_profile');
