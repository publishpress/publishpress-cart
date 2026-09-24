<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Webhook_Logger_Rules_Trait
{
    /**
     * Check whether webhook logging is enabled.
     *
     * @return bool
     */
    private static function is_enabled()
    {
        $enabled = get_option(self::ENABLE_OPTION, null);
        if (null === $enabled || false === $enabled) {
            return true;
        }

        return (bool) $enabled;
    }

    /**
     * Check whether ignored events should be written.
     *
     * @return bool
     */
    private static function include_ignored_events()
    {
        return (bool) get_option(self::INCLUDE_IGNORED_OPTION, false);
    }

    /**
     * Decide whether a rejected request is safe and useful to log.
     *
     * @param string $reason Rejection reason.
     * @return bool
     */
    private static function should_log_rejected_request($reason)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-webhook-logger-should-log-rejected-request.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Build a concise rejected request message.
     *
     * @param string $reason Rejection reason.
     * @return string
     */
    private static function format_rejected_message($reason)
    {
        $messages = [
            'missing_signature' => 'Missing Stripe-Signature header.',
            'missing_secret'    => 'Stripe webhook secret is not configured.',
            'invalid_payload'   => 'Invalid Stripe webhook payload.',
            'invalid_signature' => 'Invalid Stripe webhook signature.',
        ];

        $reason = self::sanitize_key($reason);
        return $messages[ $reason ] ?? ucwords(str_replace('_', ' ', $reason)) . '.';
    }

    /**
     * Normalize status names into the fixed operational set.
     *
     * @param string $status Raw status.
     * @return string
     */
    private static function normalize_status($status)
    {
        $status = self::sanitize_key($status);
        if ('received' === $status) {
            return '';
        }

        $map = [
            'handled'     => 'applied',
            'not_applied' => 'not_matched',
        ];

        if (isset($map[ $status ])) {
            return $map[ $status ];
        }

        return in_array($status, self::allowed_statuses(), true) ? $status : 'failed';
    }

    /**
     * Get allowed operational statuses.
     *
     * @return array
     */
    private static function allowed_statuses()
    {
        return [ 'applied', 'failed', 'not_matched', 'skipped', 'duplicate', 'rejected', 'ignored' ];
    }
}
