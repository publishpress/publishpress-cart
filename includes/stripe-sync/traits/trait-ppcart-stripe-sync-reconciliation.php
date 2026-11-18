<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Sync_Reconciliation_Trait
{
    /**
     * Periodically refresh stale Stripe-backed subscriptions.
     *
     * @return void
     */
    public static function reconcile_stale_subscriptions()
    {
        if (get_option('_ppcart_stripe_recon_disabled')) {
            return;
        }

        $now = time();
        $posts = get_posts(
            [
                'post_type'      => ppcart_query_post_types('subscription'),
                'post_status'    => [ 'active', 'trialing', 'past_due', 'unpaid', 'paused' ],
                'posts_per_page' => 50,
                'fields'         => 'ids',
                'orderby'        => 'ID',
                'order'          => 'ASC',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Reconciliation intentionally selects stale Stripe-backed subscriptions by stored sync metadata.
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'key' => ppcart_meta_key('pay_method'),
                        'value' => 'stripe',
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key' => ppcart_meta_key('last_stripe_sync'),
                            'compare' => 'NOT EXISTS',
                        ],
                        [
                            'key' => ppcart_meta_key('last_stripe_sync'),
                            'value'   => $now - DAY_IN_SECONDS,
                            'compare' => '<=',
                            'type'    => 'NUMERIC',
                        ],
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key' => ppcart_meta_key('stripe_recon_backoff_until'),
                            'compare' => 'NOT EXISTS',
                        ],
                        [
                            'key' => ppcart_meta_key('stripe_recon_backoff_until'),
                            'value'   => $now,
                            'compare' => '<=',
                            'type'    => 'NUMERIC',
                        ],
                    ],
                ],
            ]
        );

        $synced = 0;
        $repairs_left = 10;
        foreach ($posts as $post_id) {
            if ($synced >= 50) {
                break;
            }

            try {
                $sub = self::sync_subscription_by_local($post_id, false);
                $synced++;
            } catch (\PublishPress\Stripe\Exception\RateLimitException $e) {
                self::mark_sync_failure($post_id, $e, true);
                continue;
            } catch (\PublishPress\Stripe\Exception\ApiErrorException $e) {
                self::mark_sync_failure($post_id, $e, false);
                continue;
            } catch (Exception $e) {
                self::mark_sync_failure($post_id, $e, false);
                continue;
            }

            if ($repairs_left > 0 && self::maybe_repair_missing_renewals($sub)) {
                $repairs_left--;
            }
        }
    }

    /**
     * Import renewal invoices that never became orders.
     *
     * A renewal is missing when the newest local order is older than the start
     * of the subscription's current Stripe billing period. This happens when a
     * webhook never reached the site, for example on sites migrated from
     * StudioCart. Each subscription is repaired at most once per billing period,
     * so an invoice the site refuses is not fetched again every hour.
     *
     * @param PPCart_Subscription|false $sub Local subscription, just synced from Stripe.
     * @return bool Whether a repair ran.
     */
    public static function maybe_repair_missing_renewals($sub)
    {
        if (! $sub || ! $sub->id || empty($sub->subscription_id)) {
            return false;
        }

        if (! in_array($sub->status, [ 'active', 'past_due', 'unpaid' ], true)) {
            return false;
        }

        try {
            $stripe = self::get_client_for_mode($sub->gateway_mode);
            $stripe_sub = $stripe->subscriptions->retrieve($sub->subscription_id);

            $period_start = absint(self::get($stripe_sub, 'current_period_start', 0));
            if (! $period_start) {
                $period_start = absint(self::path($stripe_sub, [ 'items', 'data', 0, 'current_period_start' ], 0));
            }

            // Give the webhook a day to deliver a new renewal before importing it here.
            if (! $period_start || $period_start > time() - DAY_IN_SECONDS) {
                return false;
            }

            if ((int) ppcart_get_post_meta($sub->id, 'stripe_renewal_repair_period', true) === $period_start) {
                return false;
            }

            $last_order = get_posts(
                [
                    'post_type'      => ppcart_query_post_types('order'),
                    'post_status'    => 'any',
                    'posts_per_page' => 1,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Newest order linked to this subscription.
                    'meta_query'     => [
                        [
                            'key'   => ppcart_meta_key('subscription_id'),
                            'value' => $sub->id,
                        ],
                    ],
                ]
            );
            $last_order_time = $last_order ? (int) get_post_time('U', true, $last_order[0]) : 0;

            // Orders can be dated a little before the period starts; a missing
            // renewal leaves a gap of at least one billing period.
            if ($last_order_time && $last_order_time >= $period_start - DAY_IN_SECONDS) {
                return false;
            }

            ppcart_update_post_meta($sub->id, 'stripe_renewal_repair_period', $period_start);
            self::sync_subscription_invoices($sub, $stripe);
            ppcart_log_entry($sub->id, __('Missing renewal orders imported from Stripe by the background sync', 'publishpress-cart'));

            return true;
        } catch (\Throwable $e) {
            self::log_debug('Renewal repair failed for subscription ' . $sub->id . ': ' . $e->getMessage(), 3);

            return false;
        }
    }

    /**
     * Store reconciliation failure metadata and backoff.
     *
     * @param int       $post_id      Local subscription post ID.
     * @param Exception $exception    Exception raised during sync.
     * @param bool      $rate_limited Whether this was a Stripe rate-limit error.
     * @return void
     */
    public static function mark_sync_failure($post_id, $exception, $rate_limited = false)
    {
        $failures = absint(ppcart_get_post_meta($post_id, 'stripe_recon_failures', true)) + 1;
        $durations = $rate_limited
            ? [ 6 * HOUR_IN_SECONDS, DAY_IN_SECONDS, DAY_IN_SECONDS ]
            : [ HOUR_IN_SECONDS, 6 * HOUR_IN_SECONDS, DAY_IN_SECONDS ];
        $duration = $durations[ $failures - 1 ] ?? DAY_IN_SECONDS;

        ppcart_update_post_meta($post_id, 'stripe_recon_failures', $failures);
        ppcart_update_post_meta($post_id, 'stripe_recon_backoff_until', time() + $duration);
        ppcart_update_post_meta($post_id, 'last_stripe_sync_error', sanitize_text_field($exception->getMessage()));
    }

    /**
     * Clear reconciliation failure state after a successful sync.
     *
     * @param int $post_id Local subscription post ID.
     * @return void
     */
    public static function mark_successful_sync($post_id)
    {
        ppcart_update_post_meta($post_id, 'last_stripe_sync', time());
        ppcart_delete_post_meta($post_id, 'stripe_recon_failures');
        ppcart_delete_post_meta($post_id, 'stripe_recon_backoff_until');
        ppcart_delete_post_meta($post_id, 'last_stripe_sync_error');
    }

    /**
     * Store the last Stripe event that touched a local resource.
     *
     * @param int          $post_id Local order or subscription post ID.
     * @param object|array $event   Stripe event object.
     * @return void
     */
    public static function mark_resource_event($post_id, $event)
    {
        if (! $post_id || ! $event) {
            return;
        }

        ppcart_update_post_meta($post_id, 'last_stripe_event_id', sanitize_text_field((string) self::get($event, 'id', '')));
        ppcart_update_post_meta($post_id, 'last_stripe_event_type', sanitize_text_field((string) self::get($event, 'type', '')));
        ppcart_update_post_meta($post_id, 'last_stripe_sync', time());
    }
}
