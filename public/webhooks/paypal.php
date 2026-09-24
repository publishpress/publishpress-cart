<?php

if (! defined('ABSPATH')) {
    exit;
}


// Handle the PayPal response.
// The raw payload must survive untouched: ppcart_verifyTransaction() echoes every
// submitted key back to PayPal as the cmd=_notify-validate body, and PayPal rejects an
// altered copy. Only the consumed copy below is narrowed and read by field.
$raw_request_data = (isset($_POST) && is_array($_POST)) ? wp_unslash($_POST) : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- PayPal IPN endpoint verifies the raw payload with PayPal below.
$request_data = ppcart_parse_paypal_ipn_fields(isset($_POST) ? $_POST : []); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified with PayPal below; every value is read by field.

if (empty($request_data['payer_email'])) {
    http_response_code(200);
    exit();
}

$paypal_ipn_dedupe_key = ppcart_paypal_ipn_get_dedupe_key($request_data);
$paypal_ipn_verified = false;

if ($paypal_ipn_dedupe_key || !empty($request_data['subscr_id']) || !empty($request_data['txn_id'])) {
    $paypal_ipn_verified = ppcart_verifyTransaction($raw_request_data);

    if ($paypal_ipn_dedupe_key && get_transient($paypal_ipn_dedupe_key)) {
        http_response_code(200);
        exit();
    }
}

// Assign posted variables to local data array.
$order_id = '';
$subscription_id = '0';

// Grab PublishPress Cart order/subscription IDs from custom meta.
if (isset($raw_request_data['custom'])) {
    $custom = is_scalar($raw_request_data['custom']) ? (string) $raw_request_data['custom'] : '';
    if (is_numeric($custom) || strpos($custom, '=') !== false) { // deprecated
        $custom_id  = explode("=", $custom);
        $order_id = absint($custom_id[0]);
        if (isset($custom_id[1])) {
            $subscription_id = absint($custom_id[1]);
        }
    } else {
        $custom = json_decode(stripslashes(urldecode($custom)));
        if (is_object($custom) && isset($custom->order_id)) {
            $order_id = absint($custom->order_id);
        }
        if (is_object($custom) && isset($custom->subscription_id)) {
            $subscription_id = absint($custom->subscription_id);
        }
    }
}

$ppcart_order = new PPCart_Order($order_id);
if (!empty($request_data['txn_id'])) {
    $txnid = sanitize_text_field($request_data['txn_id']);
    if ($found = PPCart_Order::get_by_trans_id($txnid)) {
        $ppcart_order = $found;
    } elseif (!empty($request_data['parent_txn_id'])) {
        $txnid = sanitize_text_field($request_data['parent_txn_id']);
        if ($found = PPCart_Order::get_by_trans_id($txnid)) {
            $ppcart_order = $found;
            $child_txn_id = $request_data['txn_id'];
            $request_data['txn_id'] = sanitize_text_field($request_data['parent_txn_id']);
        }
    }
}

global $ppcart_debug_logger;
$ppcart_debug_logger->log_debug('Processing paypal ipn response:' . wp_json_encode($request_data));

if ($subscription_id) {
    $sub = new PPCart_Subscription($subscription_id);

    $ppcart_debug_logger->log_debug("sub->id: " . $sub->id);
    $ppcart_debug_logger->log_debug("order #" . $ppcart_order->id . " status: " . $ppcart_order->status);

    // if the first and only payment is failed/pending
    // leave $order as is so that 'product purchased'
    // integrations can run. Otherwise, create a renewal.
    if ($sub->order_count() == 1 && $ppcart_order->status != 'paid') {
        $ppcart_debug_logger->log_debug('first payment failed/pending: ' . wp_json_encode([$ppcart_order->status, ($sub->order_count() == 1), ($ppcart_order->status === 'failed')]));
    } elseif ($ppcart_order->transaction_id != ($request_data['txn_id'] ?? '')) {
        $ppcart_order = $sub->new_order();
    }
}

$data = [
    'item_name' => isset($request_data['item_name']) ? sanitize_text_field($request_data['item_name']) : '',
    'item_number' => isset($request_data['item_number']) ? sanitize_text_field($request_data['item_number']) : '',
    'paypal_status' => isset($request_data['payment_status']) ? sanitize_text_field($request_data['payment_status']) : '',
    'payment_amount' => isset($request_data['mc_gross']) ? floatval($request_data['mc_gross']) : 0,
    'payment_currency' => isset($request_data['mc_currency']) ? sanitize_text_field($request_data['mc_currency']) : '',
    'txn_id' => isset($request_data['txn_id']) ? sanitize_text_field($request_data['txn_id']) : false,
    'txn_type' => isset($request_data['txn_type']) ? sanitize_text_field($request_data['txn_type']) : '',
    'subscr_id' => (!empty($request_data['subscr_id'])) ? sanitize_text_field($request_data['subscr_id']) : '',
    'receiver_email' => isset($request_data['receiver_email']) ? sanitize_email($request_data['receiver_email']) : '',
    'payer_email' => isset($request_data['payer_email']) ? sanitize_email($request_data['payer_email']) : '',
    'order_id' => intval($order_id),
    'subscription_id' => intval($subscription_id),
];

$ppcart_debug_logger->log_debug('Processing paypal ipn response:' . wp_json_encode($data));
$ppcart_debug_logger->log_debug('current $order->id: ' . $ppcart_order->id);

switch ($request_data['payment_status'] ?? '') {
    case 'Completed':
    case 'Canceled_Reversal':
        $data['payment_status'] = 'paid';
        break;
    case 'Created':
    case 'Pending':
    case 'Processed':
        $data['payment_status'] = 'pending-payment';
        break;
    case 'Failed':
        $data['payment_status'] = 'failed';
        break;
    case 'Denied':
    case 'Expired':
    case 'Voided':
        $data['payment_status'] = 'uncollectible';
        break;
    case 'Reversed':
        $data['payment_status'] = 'refunded';
        break;
    case 'Refunded':
        $data['payment_status'] = 'refunded';

        $ppcart_debug_logger->log_debug('checking refund');

        // exit if we don't have the right txn ID or refund amount
        if (!isset($child_txn_id) || !isset($request_data['payment_gross'])) {
            $ppcart_debug_logger->log_debug('exit missing info');
            exit();
        }

        // refund with same txn ID already processed so exit
        if (in_array($child_txn_id, wp_list_pluck($ppcart_order->refund_log, 'refundID'))) {
            exit();
        } elseif ($paypal_ipn_verified) {
            // Add to the refund log only after PayPal verifies the IPN.
            $refund_amount = floatval($request_data['payment_gross']);

            if ($refund_amount < 0) {
                $refund_amount *= -1;
            }
            $ppcart_order->refund_log($refund_amount, $child_txn_id);
            $data['payment_amount'] = $ppcart_order->amount;
        }

        break;
    default:
        $data['payment_status'] = false;
        break;
}

$ppcart_debug_logger->log_debug('$data[payment_status]: ' . $data['payment_status']);

// We need to verify the transaction comes from PayPal and check we've not
// already processed the transaction before adding the payment to our
// database.
if (($data['subscr_id'] || $data['txn_id']) && $paypal_ipn_verified) {
    $enableSandbox = get_option('_ppcart_paypal_enable_sandbox');
    $paypalPDT = ($enableSandbox != 'disable')
        ? ppcart_get_sensitive_option('_ppcart_paypal_sandbox_pdt_token')
        : ppcart_get_sensitive_option('_ppcart_paypal_pdt_token');

    // update subscription ID
    if ($data['subscr_id']) {
        $sub->subscription_id = $data['subscr_id'];

        // free trial start
        if ($data['txn_type'] == 'subscr_signup' || $data['txn_type'] == 'subscr_payment') {
            //set a txn_id for free trials
            if (isset($request_data['amount1']) && floatval($request_data['amount1']) === 0.0) {
                $data['txn_id'] = $data['subscr_id'];
                $data['payment_status'] = 'paid';
            }
        }
    }

    // update transaction ID
    $ppcart_order->transaction_id = $data['txn_id'];
    $ppcart_order->amount = $data['payment_amount'];
    $ppcart_order->status = ($data['payment_status']) ? $data['payment_status'] : $ppcart_order->status;
    $ppcart_order->payment_status = $data['paypal_status'];
    $ppcart_order->paypal_payer_email = $data['payer_email'];

    if ($ppcart_order->transaction_id) {
        $ppcart_order->store();
    }

    if (strpos($data['txn_type'], 'subscr_') === 0 && isset($data['subscription_id'])) {
        do_action('ppcart_paypal_recurring_payment_data', $request_data, $data);

        // handle subscription
        switch ($data['txn_type']) {
            case 'subscr_payment':
            case 'subscr_signup':
                $sub->status = 'active';
                $sub->sub_status = 'active';
                break;
            case 'subscr_cancel':
                $sub->status = 'canceled';
                $sub->sub_status = 'canceled';
                break;
            case 'subscr_eot':
                $sub->status = 'canceled';
                $sub->sub_status = 'canceled';
                if (isset($sub->sub_end_date) && strtotime($sub->sub_end_date) <= strtotime(gmdate('Y-m-d'))) {
                    $sub->status = 'completed';
                    $sub->sub_status = 'completed';
                }
                break;
            case 'subscr_failed':
                $sub->status = 'failed';
                $sub->sub_status = 'failed';
                break;
        }

        $ppcart_debug_logger->log_debug(
            wp_json_encode([
                '$data[txn_type]: ' . $data['txn_type'],
                '$sub->free_trial_days: ' . $sub->free_trial_days,
                '$sub->status :' . $sub->status,
            ])
        );

        // make status trialing if still in trial
        if ($sub->sub_status == 'active' && $sub->free_trial_days) {
            $datepay = gmdate('Y-m-d', strtotime(get_the_time('Y-m-d', $sub->id) . "+" . $sub->free_trial_days . " day"));
            if (gmdate('Y-m-d') <  $datepay) {
                $sub->status = 'trialing';
                $sub->sub_status = 'trialing';
            }
        }

        // update next bill date
        if ($sub->sub_next_bill_date <= time()) {
            $sub->sub_next_bill_date = ppcart_get_next_bill_date_paypal($sub);
        }

        $ppcart_debug_logger->log_debug(
            '$sub->status: ' . $sub->status . ' $sub->sub_next_bill_date: ' . $sub->sub_next_bill_date
        );

        $sub->store();
    }

    if ($paypal_ipn_dedupe_key) {
        ppcart_paypal_ipn_mark_processed($paypal_ipn_dedupe_key);
    }
}

function ppcart_get_next_bill_date_paypal($sub)
{

    if ($sub->gateway_mode == 'test') {
        $url = 'https://api.sandbox.paypal.com/v1/billing/subscriptions/';
    } else {
        $url = 'https://api.paypal.com/v1/billing/subscriptions/';
    }

    $ppcart_paypal = new PPCart_Paypal('', '');
    $access_token = $ppcart_paypal->paypal_oauthtoken();
    $endpoint = $url . $sub->subscription_id;

    // Set the request headers
    $headers = [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
    ];

    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get -- vip_safe_wp_remote_get is not guaranteed to be available in all environments for this plugin.
    $response = wp_remote_get($endpoint, [
        'headers' => $headers,
        'timeout' => 3,
    ]);

    // Check for errors
    if (is_wp_error($response)) {
        return false;
    } else {
        // Parse the JSON response
        $data = json_decode(wp_remote_retrieve_body($response), true);

        // Extract the next billing date
        $next_billing_date = strtotime($data['billing_info']['next_billing_time']);

        // Output the next billing date as a timestamp
        return $next_billing_date;
    }
}


function ppcart_paypal_ipn_get_dedupe_key($request_data)
{
    $source_id = '';

    if (!empty($request_data['txn_id'])) {
        $source_id = sanitize_text_field((string) $request_data['txn_id']);
    } elseif (!empty($request_data['ipn_track_id'])) {
        $source_id = sanitize_text_field((string) $request_data['ipn_track_id']);
    }

    if ('' === $source_id) {
        return '';
    }

    return 'ppcart_paypal_ipn_' . md5($source_id);
}


function ppcart_paypal_ipn_mark_processed($dedupe_key)
{
    $expiration = 30 * (defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 86400);

    set_transient($dedupe_key, 1, $expiration);
}


function ppcart_verifyTransaction($data)
{

    $enableSandbox = get_option('_ppcart_paypal_enable_sandbox');
    $paypalUrl = ($enableSandbox != 'disable') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

    $req = 'cmd=_notify-validate';
    foreach ($data as $key => $value) {
        $value = urlencode(stripslashes($value));
        $value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i', '${1}%0D%0A${3}', $value); // IPN fix
        $req .= "&$key=$value";
    }

    $response = ppcart_safe_remote_post(
        $paypalUrl,
        [
            'headers'     => [ 'Connection' => 'Close' ],
            'body'        => $req,
            'httpversion' => '1.1',
            'timeout' => 3,
            'user-agent'  => apply_filters('ppcart_plugin_title', 'PublishPress Cart') . ' ' . PPCART_VERSION,
        ]
    );

    if (is_wp_error($response)) {
        throw new Exception(esc_html($response->get_error_message()));
    }

    $res      = wp_remote_retrieve_body($response);
    $httpCode = wp_remote_retrieve_response_code($response);

    if ($httpCode != 200) {
        throw new Exception(esc_html("PayPal responded with http code $httpCode"));
    }

    return $res === 'VERIFIED';
}

exit();
