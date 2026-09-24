<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require_once __DIR__ . '/trait-ppcart-debug-log-viewer-group-formatting.php';

require_once __DIR__ . '/trait-ppcart-debug-log-viewer-group-matching.php';

trait PPCart_Debug_Log_Viewer_Grouping_Trait
{
    use PPCart_Debug_Log_Viewer_Group_Formatting_Trait;
    use PPCart_Debug_Log_Viewer_Group_Matching_Trait;

    /**
         * Group parsed entries into readable workflows.
         *
         * @param array $entries Parsed entries.
         * @return array
         */
    public static function group_entries($entries)
    {
        // Closures defined here (not in the included template): __CLASS__ is empty in includes.
        $compare_entries_oldest_first = static function ($a, $b) {
            return self::compare_entries_oldest_first($a, $b);
        };
        $compare_groups_newest_first = static function ($a, $b) {
            return self::compare_groups_newest_first($a, $b);
        };

        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-grouping-group-entries.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Create a blank group data structure.
     *
     * @param string $key Group key.
     * @return array
     */
    private static function create_group($key)
    {
        return [
            'key'             => $key,
            'title'           => '',
            'workflow'        => 'General',
            'summary'         => '',
            'level'           => 'UNKNOWN',
            'record'          => '-',
            'record_ids'      => [
                'order_id'        => 0,
                'subscription_id' => 0,
                'product_id'      => 0,
            ],
            'entries'         => [],
            'event_count'     => 0,
            'first_timestamp' => 0,
            'last_timestamp'  => 0,
            'first_sequence'  => 0,
            'last_sequence'   => 0,
        ];
    }

    /**
     * Update group summary fields from one entry.
     *
     * @param array $group Group data.
     * @param array $entry Parsed entry.
     * @return array
     */
    private static function update_group_summary($group, $entry)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-grouping-update-group-summary.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Get a stable grouping key for an entry.
     *
     * @param array $entry Parsed entry.
     * @return string
     */
    private static function get_group_key($entry)
    {
        $flow_id = self::get($entry, 'flow_id', '');
        if ('' !== (string) $flow_id) {
            return 'flow:' . strtolower((string) $flow_id);
        }

        if (! empty($entry['order_id'])) {
            return 'order:' . absint($entry['order_id']);
        }

        if (! empty($entry['subscription_id'])) {
            return 'subscription:' . absint($entry['subscription_id']);
        }

        if (! empty($entry['product_id']) && in_array(self::get($entry, 'workflow', ''), [ 'Checkout', 'Stripe' ], true)) {
            return 'product-checkout:' . absint($entry['product_id']) . ':' . self::timestamp_bucket(self::get($entry, 'timestamp', 0));
        }

        $workflow = self::get($entry, 'workflow', 'General');
        if (in_array($workflow, [ 'Checkout', 'Stripe', 'Order', 'Subscription', 'Integration', 'Security' ], true)) {
            return strtolower($workflow) . ':' . self::timestamp_bucket(self::get($entry, 'timestamp', 0));
        }

        return strtolower($workflow) . ':' . self::timestamp_bucket(self::get($entry, 'timestamp', 0)) . ':' . md5((string) self::get($entry, 'message', ''));
    }

    /**
     * Get a readable group title.
     *
     * @param array $group Group data.
     * @return string
     */
    private static function get_group_title($group)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-grouping-get-group-title.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
