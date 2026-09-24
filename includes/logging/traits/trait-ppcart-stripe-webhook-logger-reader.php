<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Webhook_Logger_Reader_Trait
{
    /**
     * Read recent log entries for admin display or tests.
     *
     * @param array $args Read and filter args.
     * @return array
     */
    public static function read_entries($args = [])
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-read-entries.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Get compact log data for the redesigned settings Debug screen.
     *
     * @param int $limit Number of rows to preview.
     * @return array
     */
    public static function get_admin_log_data($limit = self::DEFAULT_READ_LIMIT)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-get-admin-log-data.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Filter one entry.
     *
     * @param array $entry Log entry.
     * @param array $args  Filter args.
     * @return bool
     */
    private static function entry_matches($entry, $args)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-entry-matches.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Add display-only local record details to a log row.
     *
     * This enriches the admin view without writing customer PII into the JSONL
     * file itself.
     *
     * @param array $entry Log entry.
     * @return array
     */
    private static function hydrate_entry_context($entry)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-hydrate-entry-context.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Hydrate order display fields.
     *
     * @param int   $order_id Local order ID.
     * @param array $context  Existing context.
     * @return array
     */
    private static function hydrate_order_context($order_id, $context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-hydrate-order-context.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Hydrate subscription display fields.
     *
     * @param int   $subscription_id Local subscription ID.
     * @param array $context         Existing context.
     * @return array
     */
    private static function hydrate_subscription_context($subscription_id, $context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-hydrate-subscription-context.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Hydrate customer display fields from a local record.
     *
     * @param object|null $record  Local record object.
     * @param int         $post_id Local post ID.
     * @param array       $context Existing context.
     * @return array
     */
    private static function hydrate_customer_context($record, $post_id, $context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-hydrate-customer-context.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Read a value from a model object or post meta.
     *
     * @param object|null $record   Local record object.
     * @param int         $post_id  Local post ID.
     * @param string      $property Object property.
     * @param string      $meta_key Post meta key.
     * @return mixed
     */
    private static function get_record_value($record, $post_id, $property, $meta_key)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-get-record-value.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Old DB-backed logs briefly stored "received" lifecycle rows. Hide them in
     * the operational viewer so migrated history does not look like real work.
     *
     * @param array $entry Log entry.
     * @return bool
     */
    private static function is_noisy_received_entry($entry)
    {
        $message = strtolower(trim((string) self::get($entry, 'message', '')));
        $status  = self::sanitize_key(self::get($entry, 'status', ''));

        return in_array($status, [ '', 'received', 'skipped' ], true) && 'stripe webhook received.' === $message;
    }

    /**
     * Tail-read a log file.
     *
     * @param string $path      File path.
     * @param int    $max_bytes Maximum bytes.
     * @return array
     */
    private static function tail_lines($path, $max_bytes)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-tail-lines.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
