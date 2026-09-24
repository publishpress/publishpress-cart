<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_stripe, $ppcart_debug_logger;

$ttl    = absint(apply_filters('ppcart_preloaded_intent_ttl', HOUR_IN_SECONDS));
$cutoff = time() - max($ttl, 15 * MINUTE_IN_SECONDS);

$orders = get_posts(
    [
        'post_type'              => ppcart_query_post_types('order'),
        'post_status'            => 'any',
        'posts_per_page'         => 100,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required cleanup lookup for intentionally preloaded checkout intents.
        'meta_query'             => [
            'relation' => 'AND',
            [
                'key' => ppcart_meta_key('preloaded_intent'),
                'value' => '1',
            ],
            [
                'key' => ppcart_meta_key('temp_order_token'),
                'compare' => 'EXISTS',
            ],
            [
                'key' => ppcart_meta_key('preloaded_intent_created'),
                'value'   => $cutoff,
                'compare' => '<',
                'type'    => 'NUMERIC',
            ],
            [
                'key' => ppcart_meta_key('status'),
                'value' => 'pending-payment',
            ],
        ],
    ]
);

if (empty($orders)) {
    return;
}

$stripe = null;
if (isset($ppcart_stripe['sk']) && ! empty($ppcart_stripe['sk']) && class_exists('PublishPress\\Stripe\\StripeClient')) {
    $stripe = ppcart_stripe_client($ppcart_stripe['sk']);
}

$max_cleanup_attempts = max(1, absint(apply_filters('ppcart_preloaded_intent_cleanup_force_archive_attempts', 3)));

foreach ($orders as $order_id) {
    $intent_id    = ppcart_get_post_meta($order_id, 'transaction_id', true);
    $gateway_mode = ppcart_get_post_meta($order_id, 'gateway_mode', true);
    $can_archive  = true;
    $has_intent   = is_string($intent_id) && preg_match('/^pi_[A-Za-z0-9]+$/', $intent_id);

    if (is_string($gateway_mode) && '' !== $gateway_mode && isset($ppcart_stripe['mode']) && $gateway_mode !== $ppcart_stripe['mode']) {
        continue;
    }

    if ($has_intent && ! $stripe) {
        if (is_object($ppcart_debug_logger) && method_exists($ppcart_debug_logger, 'log_event')) {
            $ppcart_debug_logger->log_event(
                'checkout.preloaded_intent.cleanup_deferred',
                'Could not clean up preloaded PaymentIntent because Stripe is not configured or the SDK is unavailable.',
                [
                    'order_id'          => (int) $order_id,
                    'payment_intent_id' => $intent_id,
                ],
                4
            );
        }
        continue;
    }

    if ($has_intent) {
        try {
            $intent        = $stripe->paymentIntents->retrieve($intent_id);
            $intent_status = isset($intent->status) ? sanitize_text_field($intent->status) : '';

            if (in_array($intent_status, [ 'succeeded', 'processing' ], true)) {
                $can_archive = false;
            } elseif ('canceled' !== $intent_status) {
                $stripe->paymentIntents->cancel($intent_id);
            }
        } catch (Exception $e) {
            $cleanup_attempts = absint(ppcart_get_post_meta($order_id, 'preloaded_intent_cleanup_attempts', true)) + 1;
            ppcart_update_post_meta($order_id, 'preloaded_intent_cleanup_attempts', $cleanup_attempts);
            $can_archive = $this->is_missing_stripe_resource_exception($e) && $cleanup_attempts >= $max_cleanup_attempts;

            if (is_object($ppcart_debug_logger) && method_exists($ppcart_debug_logger, 'log_event')) {
                $ppcart_debug_logger->log_event(
                    $can_archive ? 'checkout.preloaded_intent.cleanup_forced' : 'checkout.preloaded_intent.cleanup_failed',
                    $can_archive ? 'Locally archiving abandoned preloaded checkout order after repeated missing PaymentIntent responses.' : 'Could not clean up preloaded PaymentIntent: ' . $e->getMessage(),
                    [
                        'order_id'          => (int) $order_id,
                        'payment_intent_id' => $intent_id,
                        'attempts'          => $cleanup_attempts,
                    ],
                    4
                );
            }
        }
    }

    if (! $can_archive) {
        continue;
    }

    $this->clear_temp_order_meta($order_id);
    wp_trash_post($order_id);

    if (is_object($ppcart_debug_logger) && method_exists($ppcart_debug_logger, 'log_event')) {
        $ppcart_debug_logger->log_event(
            'checkout.preloaded_intent.cleaned',
            "Cleaned up abandoned preloaded checkout order #{$order_id}.",
            [
                'order_id'          => (int) $order_id,
                'payment_intent_id' => is_string($intent_id) ? $intent_id : '',
            ],
            0
        );
    }
}
