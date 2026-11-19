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
        foreach ($posts as $post_id) {
            if ($synced >= 50) {
                break;
            }

            try {
                self::sync_subscription_by_local($post_id, false);
                $synced++;
            } catch (\PublishPress\Stripe\Exception\RateLimitException $e) {
                self::mark_sync_failure($post_id, $e, true);
            } catch (\PublishPress\Stripe\Exception\ApiErrorException $e) {
                self::mark_sync_failure($post_id, $e, false);
            } catch (Exception $e) {
                self::mark_sync_failure($post_id, $e, false);
            }
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
