<?php

if (! defined('ABSPATH')) {
    exit;
}


$bulk_actions = [
    'bulk_ppcart_make_paid',
    'bulk_ppcart_make_failed',
    'bulk_ppcart_make_pending',
    'bulk_ppcart_make_active',
    'bulk_ppcart_make_canceled',
    'bulk_ppcart_make_completed',
];
$redirect = remove_query_arg(
    array_merge($bulk_actions, [ 'bulk_ppcart_sync_stripe', 'bulk_ppcart_sync_stripe_failed', 'bulk_ppcart_sync_stripe_skipped' ]),
    $redirect
);

if ('ppcart_sync_stripe' === $doaction) {
    $synced  = 0;
    $failed  = 0;
    $skipped = 0;

    // Each sync makes several Stripe calls, so the batch is capped to keep the
    // request inside the PHP time limit and away from Stripe rate limits.
    $sync_limit       = (int) apply_filters('ppcart_bulk_sync_subscription_limit', 20);
    $order_sync_limit = (int) apply_filters('ppcart_bulk_sync_order_limit', 20);

    foreach ($object_ids as $object_id) {
        if (! current_user_can('edit_post', $object_id)) {
            continue;
        }

        $object_type = get_post_type($object_id);
        $is_order    = ppcart_is_order_post_type($object_type);

        if (! $is_order && ! ppcart_is_subscription_post_type($object_type)) {
            continue;
        }

        if ($synced + $failed >= ($is_order ? $order_sync_limit : $sync_limit)) {
            break;
        }

        if (! class_exists('PPCart_Stripe_Sync')) {
            break;
        }

        if ($is_order) {
            $ppcart_order = new PPCart_Order($object_id);

            // Orders paid by another gateway, or without a Stripe charge or
            // payment intent ID, have nothing in Stripe to sync from.
            if (! PPCart_Stripe_Sync::get_order_sync_payment_id($ppcart_order)) {
                $skipped++;
                continue;
            }

            try {
                PPCart_Stripe_Sync::sync_order_by_local($ppcart_order);
                ppcart_log_entry($object_id, __('Order synced from Stripe by admin', 'publishpress-cart'));
                $synced++;
            } catch (Exception $e) {
                /* translators: %s: error message. */
                ppcart_log_entry($object_id, sprintf(__('Error syncing order from Stripe! Message: %s', 'publishpress-cart'), $e->getMessage()));
                $failed++;
            }

            continue;
        }

        try {
            PPCart_Stripe_Sync::sync_subscription_by_local(new PPCart_Subscription($object_id));
            ppcart_log_entry($object_id, __('Subscription synced from Stripe by admin', 'publishpress-cart'));
            $synced++;
        } catch (Exception $e) {
            /* translators: %s: error message. */
            ppcart_log_entry($object_id, sprintf(__('Error syncing subscription from Stripe! Message: %s', 'publishpress-cart'), $e->getMessage()));
            $failed++;
        }
    }

    $redirect = add_query_arg('bulk_ppcart_sync_stripe', $synced, $redirect);

    if ($failed) {
        $redirect = add_query_arg('bulk_ppcart_sync_stripe_failed', $failed, $redirect);
    }

    if ($skipped) {
        $redirect = add_query_arg('bulk_ppcart_sync_stripe_skipped', $skipped, $redirect);
    }

    return $redirect;
}
if (in_array('bulk_' . $doaction, $bulk_actions)) {
    $status_to = str_replace('ppcart_make_', '', $doaction);
    foreach ($object_ids as $object_id) {
        if (! current_user_can('edit_post', $object_id)) {
            continue;
        }

        $object_type = get_post_type($object_id);
        switch ($object_type) {
            case "ppcart_order":
                $ppcart_order = new PPCart_Order($object_id);
                if (class_exists('PPCart_Stripe_Sync') && PPCart_Stripe_Sync::is_stripe_backed_record($ppcart_order)) {
                    ppcart_log_entry($ppcart_order->id, __("Stripe-backed order status was not changed locally. Sync from Stripe or perform the action in Stripe.", 'publishpress-cart'));
                    break;
                }
                $ppcart_order->status = $status_to;
                PPCart_Order::update($ppcart_order);
                break;
            case "ppcart_subscription":
                $ppcart_subscription = new PPCart_Subscription($object_id);
                if ($doaction === 'ppcart_make_active') {
                    if (class_exists('PPCart_Stripe_Sync') && PPCart_Stripe_Sync::is_stripe_backed_record($ppcart_subscription)) {
                        try {
                            PPCart_Stripe_Sync::resume_subscription($ppcart_subscription);
                            ppcart_log_entry($ppcart_subscription->id, __("Subscription resumed from Stripe by admin", 'publishpress-cart'));
                        } catch (\Exception $e) {
                            /* translators: %s: error message. */
                            ppcart_log_entry($ppcart_subscription->id, sprintf(__("Error resuming Stripe subscription! Message: %s", 'publishpress-cart'), $e->getMessage()));
                        }
                    } else {
                        $ppcart_subscription->status = $status_to;
                        PPCart_Subscription::update($ppcart_subscription);
                    }
                } else {
                    if ($doaction === 'ppcart_make_completed') {
                        if (class_exists('PPCart_Stripe_Sync') && PPCart_Stripe_Sync::is_stripe_backed_record($ppcart_subscription)) {
                            ppcart_log_entry($ppcart_subscription->id, __("Stripe-backed subscription was not marked completed locally. Stripe state remains authoritative.", 'publishpress-cart'));
                        } else {
                            $ppcart_subscription->status = 'completed';
                            $ppcart_subscription->sub_status = 'completed';
                            PPCart_Subscription::update($ppcart_subscription);
                        }
                    } else {
                        $out = ppcart_do_cancel_subscription(
                            $ppcart_subscription,
                            $ppcart_subscription->subscription_id,
                            true,
                            false,
                            [ 'allow_local_cancel' => true ]
                        );
                        if ($out == 'OK') {
                            ppcart_log_entry($ppcart_subscription->id, __("Subscription canceled by admin", 'publishpress-cart'));
                        } else {
                            /* translators: %s: error message. */
                            ppcart_log_entry($ppcart_subscription->id, sprintf(__("Error canceling subscription! Message: %s", 'publishpress-cart'), $out));
                        }
                    }
                }
                break;
            default:
                break;
        }
    }
    $redirect = add_query_arg(
        'bulk_' . $doaction, // just a parameter for URL
        count($object_ids), // how many posts have been selected
        $redirect
    );
}
return $redirect;
