<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Webhook_Logger_Format_Trait
{
    /**
     * Format admin display time.
     *
     * @param array $row Log row.
     * @return string
     */
    private static function format_admin_time($row)
    {
        $timestamp = absint(self::get($row, 'timestamp', 0));
        if ($timestamp && function_exists('date_i18n')) {
            return date_i18n('Y-m-d H:i:s', $timestamp);
        }

        return self::sanitize_text((string) self::get($row, 'time_utc', ''));
    }

    /**
     * Format a local record context for the admin table.
     *
     * @param array $context Log context.
     * @return string
     */
    private static function format_record_context($context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-format-record-context.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Format a numeric ID with a hash prefix.
     *
     * @param mixed $id Record ID.
     * @return string
     */
    private static function format_hash_id($id)
    {
        if ('' === (string) $id) {
            return '-';
        }

        return is_numeric($id) ? '#' . absint($id) : (string) $id;
    }

    /**
     * Format a file size.
     *
     * @param int $bytes File size in bytes.
     * @return string
     */
    private static function format_file_size($bytes)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-format-file-size.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Format an amount context for the admin table.
     *
     * @param array $context Log context.
     * @return string
     */
    private static function format_amount_context($context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-format-amount-context.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Format the original payment amount from context.
     *
     * @param array $context Log context.
     * @return string
     */
    private static function format_original_amount_context($context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-format-original-amount-context.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Format the refunded amount from context.
     *
     * @param array $context Log context.
     * @return string
     */
    private static function format_refunded_amount_context($context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-format-refunded-amount-context.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Format amount context for the admin table.
     *
     * @param array $context Log context.
     * @return string
     */
    private static function format_amount_context_html($context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-format-amount-context-html.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Format a scalar amount with an optional currency code.
     *
     * @param mixed  $amount   Amount.
     * @param string $currency Currency code.
     * @return string
     */
    private static function format_amount_value($amount, $currency = '')
    {
        if ('' === (string) $amount) {
            return '-';
        }

        $amount = is_numeric($amount) ? number_format((float) $amount, 2, '.', '') : (string) $amount;
        return $currency ? $amount . ' ' . $currency : $amount;
    }

    /**
     * Format customer context for text previews.
     *
     * @param array $context Log context.
     * @return string
     */
    private static function format_customer_context($context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-format-customer-context.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Format customer context for the admin table.
     *
     * @param array $context Log context.
     * @return string
     */
    private static function format_customer_context_html($context)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-format-customer-context-html.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }
}
