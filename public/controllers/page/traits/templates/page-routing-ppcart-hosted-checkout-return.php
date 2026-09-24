<?php

if (! defined('ABSPATH')) {
    exit;
}


global $ppcart_debug_logger;

$ppcart_hosted  = ppcart_filter_input(INPUT_GET, 'ppcart_hosted', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$ppcart_session = ppcart_filter_input(INPUT_GET, 'ppcart_session', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$ppcart_hosted  = is_string($ppcart_hosted) ? sanitize_text_field($ppcart_hosted) : '';
$ppcart_session = is_string($ppcart_session) ? sanitize_text_field($ppcart_session) : '';
$ppcart_retry   = ppcart_filter_input(INPUT_GET, 'ppcart_retry', FILTER_VALIDATE_INT);
$ppcart_retry   = (false !== $ppcart_retry && null !== $ppcart_retry) ? absint($ppcart_retry) : 0;

$log_return = static function ($event, $message, $context = [], $level = 1) use (&$ppcart_debug_logger) {
    if (isset($ppcart_debug_logger) && is_object($ppcart_debug_logger) && method_exists($ppcart_debug_logger, 'log_event')) {
        $ppcart_debug_logger->log_event($event, $message, $context, $level);
        return;
    }
    if (isset($ppcart_debug_logger) && is_object($ppcart_debug_logger) && method_exists($ppcart_debug_logger, 'log_debug')) {
        $ppcart_debug_logger->log_debug($message . ' ' . wp_json_encode($context), $level);
    }
};

if ('' === $ppcart_hosted) {
    return;
}

$clean_url = remove_query_arg([ 'ppcart_session', 'ppcart_hosted', 'ppcart_retry' ]);

if ('cancel' === $ppcart_hosted) {
    $log_return(
        'checkout.hosted_return.cancel',
        'Hosted checkout return: buyer cancelled.',
        [ 'session_id' => $ppcart_session ],
        1
    );
    wp_safe_redirect($clean_url);
    exit;
}

// processing re-enters reconciliation (keeps ppcart_session) so a lagging webhook can still finish.
if (! in_array($ppcart_hosted, [ 'success', 'processing' ], true) || '' === $ppcart_session) {
    $log_return(
        'checkout.hosted_return.skip',
        'Hosted checkout return: not a success/processing return or missing session.',
        [
            'ppcart_hosted'  => $ppcart_hosted,
            'has_session' => '' !== $ppcart_session,
        ],
        2
    );
    return;
}

if (! function_exists('ppcart_hosted_checkout_resolve_return_context')) {
    require_once PPCART_BASE_DIR . 'includes/helpers/ppcart-hosted-checkout.php';
}

$context      = ppcart_hosted_checkout_resolve_return_context($ppcart_session);
$order_id     = (int) $context['order_id'];
$gateway_mode = (string) $context['gateway_mode'];

$log_return(
    'checkout.hosted_return.begin',
    'Hosted checkout return: reconciling session.',
    [
        'session_id' => $ppcart_session,
        'order_id'   => $order_id,
        'mode'       => $gateway_mode,
        'source'     => $context['source'],
        'ppcart_hosted'  => $ppcart_hosted,
        'retry'      => $ppcart_retry,
    ],
    1
);

// Reconcile server-side; the webhook is the source of truth but may lag the return.
if (class_exists('PPCart_Stripe_Sync')) {
    $modes = function_exists('ppcart_hosted_checkout_modes_to_try')
        ? ppcart_hosted_checkout_modes_to_try($gateway_mode)
        : array_values(array_filter([ $gateway_mode, 'test', 'live' ]));

    $session_obj = null;
    $last_error  = '';
    $stripe      = null;

    foreach ($modes as $try_mode) {
        try {
            $stripe        = PPCart_Stripe_Sync::get_client_for_mode($try_mode);
            $session_obj   = $stripe->checkout->sessions->retrieve($ppcart_session);
            $gateway_mode  = $try_mode;
            break;
        } catch (Throwable $e) {
            $last_error  = $e->getMessage();
            $session_obj = null;
        }
    }

    if ($session_obj) {
        // Prefer durable metadata if local order id is still unknown.
        if (! $order_id) {
            $order_id = absint(ppcart_stripe_metadata($session_obj, 'ppcart_order_id', 0));
            if ($order_id) {
                $log_return(
                    'checkout.hosted_return.order_from_metadata',
                    'Hosted checkout return: recovered order id from Stripe session metadata.',
                    [ 'order_id' => $order_id, 'session_id' => $ppcart_session ],
                    1
                );
            }
        }

        try {
            if ('complete' === ($session_obj->status ?? '') || 'paid' === ($session_obj->payment_status ?? '')) {
                $result = PPCart_Stripe_Sync::sync_checkout_session_completed($session_obj, $stripe, null);
                if ($result && $result->id) {
                    $order_id = (int) $result->id;
                }
            }
        } catch (Throwable $e) {
            $log_return(
                'checkout.hosted_return.sync_failed',
                'Hosted checkout return: sync_checkout_session_completed failed: ' . $e->getMessage(),
                [
                    'session_id' => $ppcart_session,
                    'order_id'   => $order_id,
                    'mode'       => $gateway_mode,
                ],
                4
            );
        }
    } elseif ('' !== $last_error) {
        $log_return(
            'checkout.hosted_return.retrieve_failed',
            'Hosted checkout return: could not retrieve Checkout Session from Stripe: ' . $last_error,
            [
                'session_id'  => $ppcart_session,
                'modes_tried' => $modes,
                'order_id'    => $order_id,
            ],
            4
        );
    }
}

if ($order_id) {
    $cart_order = new PPCart_Order($order_id);

    if ($cart_order->id && 'paid' === $cart_order->payment_status) {
        $confirmation = function_exists('ppcart_hosted_checkout_confirmation_url')
            ? ppcart_hosted_checkout_confirmation_url($order_id, (int) $cart_order->product_id)
            : PPCart_Order::confirmation_url(home_url('/'), $order_id);

        $log_return(
            'checkout.hosted_return.confirmed',
            'Hosted checkout return: order paid; redirecting to confirmation.',
            [
                'order_id' => $order_id,
                'url'      => $confirmation,
            ],
            1
        );

        // Not wp_safe_redirect(): Confirmation Type "redirect" accepts an off-site
        // URL, which the classic checkout also honors via window.location.
        // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Destination comes from product settings, not from the request.
        wp_redirect(esc_url_raw($confirmation));
        exit;
    }

    // Not yet finalized — keep session id and retry a few times, then show an explicit processing page.
    $max_retries = 5;
    if ($ppcart_retry < $max_retries) {
        $processing = add_query_arg(
            [
                'ppcart_hosted'  => 'processing',
                'ppcart_session' => $ppcart_session,
                'ppcart_retry'   => $ppcart_retry + 1,
            ],
            $clean_url
        );

        $log_return(
            'checkout.hosted_return.processing_retry',
            'Hosted checkout return: order not paid yet; scheduling retry.',
            [
                'order_id' => $order_id,
                'retry'    => $ppcart_retry + 1,
            ],
            2
        );

        // Brief client delay before retry so a lagging webhook can land.
        nocache_headers();
        $target = esc_url($processing);
        echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
        echo '<meta http-equiv="refresh" content="2;url=' . esc_url($target) . '">';
        echo '<title>' . esc_html__('Processing payment…', 'publishpress-cart') . '</title></head><body>';
        echo '<p>' . esc_html__('Your payment is being confirmed. This page will refresh automatically…', 'publishpress-cart') . '</p>';
        echo '<p><a href="' . esc_url($target) . '">' . esc_html__('Continue', 'publishpress-cart') . '</a></p>';
        echo '</body></html>';
        exit;
    }

    $log_return(
        'checkout.hosted_return.processing_timeout',
        'Hosted checkout return: order still not paid after retries.',
        [ 'order_id' => $order_id, 'session_id' => $ppcart_session ],
        4
    );

    nocache_headers();
    $status_url = esc_url(PPCart_Order::confirmation_url($clean_url, $order_id));
    echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
    echo '<title>' . esc_html__('Payment processing', 'publishpress-cart') . '</title></head><body>';
    echo '<p>' . esc_html__('We are still confirming your payment. You can leave this page open and refresh shortly, or check your email for a confirmation.', 'publishpress-cart') . '</p>';
    if ($order_id) {
        echo '<p><a href="' . esc_url($status_url) . '">' . esc_html__('View order status', 'publishpress-cart') . '</a></p>';
    }
    echo '</body></html>';
    exit;
}

$log_return(
    'checkout.hosted_return.unresolved',
    'Hosted checkout return: could not resolve order; redirecting to clean checkout URL.',
    [
        'session_id' => $ppcart_session,
        'source'     => $context['source'],
    ],
    4
);

wp_safe_redirect($clean_url);
exit;
