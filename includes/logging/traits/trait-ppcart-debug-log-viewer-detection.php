<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Debug_Log_Viewer_Detection_Trait
{
    /**
     * Detect local record IDs from message and context.
     *
     * @param string $message Debug message.
     * @param array  $context Parsed context.
     * @return array
     */
    private static function detect_record_ids($message, $context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-detection-detect-record-ids.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Detect Stripe IDs in text and structured context.
     *
     * @param string $raw_line Raw line.
     * @param array  $context  Parsed context.
     * @return array
     */
    private static function detect_stripe_ids($raw_line, $context)
    {
        $haystack = $raw_line . ' ' . self::json_encode($context);
        preg_match_all('/\b(?:(?:acct|ch|cs|cus|evt|in|inpay|pi|pm|re|seti|si|sub)_[A-Za-z0-9_]+|price_[A-Za-z0-9]{6,})\b/', $haystack, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }

    /**
     * Guess workflow from message and event context.
     *
     * @param string $message Debug message.
     * @param array  $context Parsed context.
     * @return string
     */
    private static function detect_workflow($message, $context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/debug-log-viewer-detection-detect-workflow.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Format detected record context.
     *
     * @param array $record_ids Record IDs.
     * @return string
     */
    private static function format_record_context($record_ids)
    {
        $parts = [];
        if (! empty($record_ids['order_id'])) {
            $parts[] = 'Order #' . absint($record_ids['order_id']);
        }
        if (! empty($record_ids['subscription_id'])) {
            $parts[] = 'Subscription #' . absint($record_ids['subscription_id']);
        }
        if (! empty($record_ids['product_id'])) {
            $parts[] = 'Product #' . absint($record_ids['product_id']);
        }

        return empty($parts) ? '-' : implode(' / ', $parts);
    }

    /**
     * Get a numeric context value by candidate keys.
     *
     * @param array $context Parsed context.
     * @param array $keys    Candidate keys.
     * @return int
     */
    private static function context_int($context, $keys)
    {
        foreach ($keys as $key) {
            if (isset($context[ $key ]) && is_numeric($context[ $key ])) {
                return absint($context[ $key ]);
            }
        }

        return 0;
    }

    /**
     * Detect request flow ID from structured context.
     *
     * @param array $context Parsed context.
     * @return string
     */
    private static function detect_flow_id($context)
    {
        foreach ([ 'flow_id', 'request_id' ] as $key) {
            if (isset($context[ $key ]) && is_scalar($context[ $key ])) {
                return preg_replace('/[^A-Za-z0-9_.:-]/', '', (string) $context[ $key ]);
            }
        }

        return '';
    }
}
