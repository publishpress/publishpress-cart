<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_currency, $ppcart_debug_logger;

$ppcart_record_rejected_webhook = function ($reason, $context = []) {
    if (class_exists('PPCart_Stripe_Webhook_Logger')) {
        PPCart_Stripe_Webhook_Logger::record_rejected_request($reason, $context);
    }
};

$sig_header = ! empty($_SERVER['HTTP_STRIPE_SIGNATURE'])
    ? sanitize_text_field(wp_unslash($_SERVER['HTTP_STRIPE_SIGNATURE'])) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized immediately after unslashing.
    : '';
if (!$sig_header) {
    // Direct access — no Stripe-Signature header means this is not a Stripe request.
    $ppcart_record_rejected_webhook('missing_signature');
    http_response_code(400);
    exit();
}

$env = $ppcart_stripe['mode'];
$endpoint_secret = ppcart_get_sensitive_option('_ppcart_stripe_' . $env . '_webhook_secret');

// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsRemoteFile -- Reads raw local request body from php://input.
$payload = file_get_contents('php://input');
$event = null;

if (empty($endpoint_secret)) {
    $ppcart_record_rejected_webhook(
        'missing_secret',
        [
            'mode' => $env,
        ]
    );
    $ppcart_debug_logger->log_debug('Stripe webhook secret not configured for mode: ' . $env, 4);
    http_response_code(400);
    exit();
}

try {
    $event = \PublishPress\Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    $ppcart_record_rejected_webhook('invalid_payload');
    $ppcart_debug_logger->log_debug('Stripe webhook error (invalid payload): ' . wp_json_encode($e));
    http_response_code(400);
    exit();
} catch (\PublishPress\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature for currently active mode.
    $ppcart_record_rejected_webhook('invalid_signature');
    $ppcart_debug_logger->log_debug('Stripe webhook error (invalid signature): ' . wp_json_encode($e));
    http_response_code(400);
    exit();
}

$event_livemode = isset($event->livemode) ? (bool) $event->livemode : false;
$expects_live_event = ('live' === $env);
if ($event_livemode !== $expects_live_event) {
    if (class_exists('PPCart_Stripe_Sync')) {
        PPCart_Stripe_Sync::record_webhook_log(
            $event,
            'rejected',
            'Stripe webhook mode mismatch.',
            [
                'expected_mode' => $env,
            ]
        );
    }
    $ppcart_debug_logger->log_debug('Stripe webhook mode mismatch. Expected mode: ' . $env, 4);
    // Return 200 so Stripe does not keep retrying mismatched-mode events.
    http_response_code(200);
    exit();
}

if (! class_exists('PPCart_Stripe_Sync')) {
    if (class_exists('PPCart_Stripe_Webhook_Logger')) {
        PPCart_Stripe_Webhook_Logger::record_event($event, 'failed', 'Stripe webhook sync class missing.');
    }
    $ppcart_debug_logger->log_debug('Stripe webhook sync class missing.', 4);
    http_response_code(500);
    exit();
}

if (! PPCart_Stripe_Sync::event_dedupe_gate($event)) {
    PPCart_Stripe_Sync::record_webhook_log($event, 'duplicate', 'Duplicate Stripe webhook skipped.');
    http_response_code(200);
    exit();
}

try {
    $handled = PPCart_Stripe_Sync::handle_webhook_event($event);
    if (false === $handled) {
        PPCart_Stripe_Sync::release_event_dedupe_gate($event);
        PPCart_Stripe_Sync::record_webhook_log($event, 'not_applied', 'Stripe webhook did not apply to a local record.');
    } else {
        $context = [];
        if (is_object($handled) && isset($handled->id)) {
            $context['record_id'] = $handled->id;
            if ($handled instanceof PPCart_Order) {
                $context['order_id'] = $handled->id;
                $context['subscription_id'] = ! empty($handled->subscription_id) ? $handled->subscription_id : '';
                $context['amount'] = $handled->amount ?? '';
                $context['currency'] = $handled->currency ?? '';
                $context['order_status'] = $handled->status ?? '';
            } elseif ($handled instanceof PPCart_Subscription) {
                $context['subscription_id'] = $handled->id;
                $context['amount'] = $handled->amount ?? '';
                $context['currency'] = $handled->currency ?? '';
                $context['subscription_status'] = $handled->status ?? '';
            }
        }

        PPCart_Stripe_Sync::record_webhook_log($event, 'handled', 'Stripe webhook handler completed.', $context);
    }
} catch (\Exception $e) {
    PPCart_Stripe_Sync::release_event_dedupe_gate($event);
    PPCart_Stripe_Sync::record_webhook_log($event, 'failed', 'Stripe webhook sync error: ' . $e->getMessage());
    $ppcart_debug_logger->log_debug('Stripe webhook sync error: ' . $e->getMessage(), 4);
    http_response_code(500);
    exit();
}

http_response_code(200);
exit();
