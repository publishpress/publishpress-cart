<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class PPCart_Status_Labels
{
    /**
     * Logical status => registered wp_posts.post_status slug.
     *
     * `pending-payment` maps to `ppcart_pending` because `ppcart_pending-payment`
     * exceeds the varchar(20) post_status column.
     *
     * @return array<string, string>
     */
    public static function registered_map()
    {
        return [
            'pending-payment' => 'ppcart_pending',
            'paid'            => 'ppcart_paid',
            'refunded'        => 'ppcart_refunded',
            'failed'          => 'ppcart_failed',
            'past_due'        => 'ppcart_past_due',
            'uncollectible'   => 'ppcart_uncollectible',
            'active'          => 'ppcart_active',
            'unpaid'          => 'ppcart_unpaid',
            'paused'          => 'ppcart_paused',
            'canceled'        => 'ppcart_canceled',
            'completed'       => 'ppcart_completed',
            'incomplete'      => 'ppcart_incomplete',
            'trialing'        => 'ppcart_trialing',
        ];
    }

    /**
     * @param string $status Logical, registered, or unknown slug.
     * @return string
     */
    public static function registered_slug($status)
    {
        $status = (string) $status;
        $map    = self::registered_map();

        if (isset($map[ $status ])) {
            return $map[ $status ];
        }

        if (in_array($status, $map, true)) {
            return $status;
        }

        return $status;
    }

    /**
     * @param string $status Logical, registered, or unknown slug.
     * @return string
     */
    public static function logical_slug($status)
    {
        $status = (string) $status;
        $map    = array_flip(self::registered_map());

        if (isset($map[ $status ])) {
            return $map[ $status ];
        }

        return $status;
    }

    /**
     * @param string $status Logical, registered, or unknown slug.
     * @return bool
     */
    public static function is_mapped($status)
    {
        $status = (string) $status;
        $map    = self::registered_map();

        return isset($map[ $status ]) || in_array($status, $map, true);
    }

    /**
     * @param string $status Logical or registered slug.
     * @return array<int, string>
     */
    public static function query_slugs($status)
    {
        $logical    = self::logical_slug($status);
        $registered = self::registered_slug($logical);

        if ($logical === $registered) {
            return [ $status ];
        }

        return array_values(array_unique([ $registered, $logical ]));
    }

    /**
     * @param int|\WP_Post $post Post ID or object.
     * @return string
     */
    public static function logical_from_post($post)
    {
        return self::logical_slug(get_post_status($post));
    }

    public static function get($status)
    {
        $status     = (string) $status;
        $candidates = array_unique(
            array_filter(
                [
                    self::registered_slug($status),
                    self::logical_slug($status),
                    $status,
                ]
            )
        );

        if (function_exists('get_post_status_object')) {
            foreach ($candidates as $candidate) {
                $status_object = get_post_status_object($candidate);

                if ($status_object && isset($status_object->label) && $status_object->label !== '') {
                    return $status_object->label;
                }
            }
        }

        return self::get_fallback_label(self::logical_slug($status));
    }

    /**
     * Map stored order status to the value used by the admin Order Status select.
     *
     * @param string $status Stored status (meta / post_status).
     * @return string Select option value.
     */
    public static function edit_select_value($status)
    {
        $status = self::logical_slug((string) $status);

        if (in_array($status, [ 'pending-payment', 'initiated' ], true)) {
            return 'pending';
        }

        return $status;
    }

    /**
     * Canonical order/subscription CPT slugs (never leftover StudioCart types).
     *
     * @return array<int, string>
     */
    public static function canonical_post_types()
    {
        if (function_exists('ppcart_canonical_live_post_types')) {
            $map   = ppcart_canonical_live_post_types();
            $types = [];
            foreach ([ 'order', 'subscription' ] as $family) {
                if (! empty($map[ $family ])) {
                    $types[] = $map[ $family ];
                }
            }

            return $types;
        }

        return [ 'ppcart_order', 'ppcart_subscription' ];
    }

    /**
     * CPT slugs used in queries, including leftover types during Compat mixed migration.
     *
     * @return array<int, string>
     */
    public static function query_post_types()
    {
        $types = self::canonical_post_types();

        if (function_exists('ppcart_query_post_types')) {
            $types = array_merge(
                $types,
                ppcart_query_post_types('order'),
                ppcart_query_post_types('subscription')
            );
        }

        return array_values(array_unique($types));
    }

    private static function get_fallback_label($status)
    {
        $labels = [
            'pending-payment' => 'Pending',
            'paid'            => 'Paid',
            'completed'       => 'Completed',
            'refunded'        => 'Refunded',
            'failed'          => 'Failed',
            'past_due'        => 'Past Due',
            'uncollectible'   => 'Uncollectible',
            'unpaid'          => 'Unpaid',
            'active'          => 'Active',
            'paused'          => 'Paused',
            'canceled'        => 'Canceled',
            'cancelled'       => 'Cancelled',
            'incomplete'      => 'Incomplete',
            'trialing'        => 'Trialing',
        ];

        if (isset($labels[ $status ])) {
            return $labels[ $status ];
        }

        return ucwords(str_replace([ '-', '_' ], ' ', $status));
    }
}
