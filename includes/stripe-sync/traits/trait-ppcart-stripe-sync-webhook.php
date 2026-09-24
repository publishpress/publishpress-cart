<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Sync_Webhook_Trait
{
    /**
     * Atomically claim the completion of a hosted Checkout Session.
     *
     * @param string $session_id Stripe Checkout Session id.
     * @return bool True when this caller owns the claim and may finalize.
     */
    public static function claim_checkout_session($session_id)
    {
        if ('' === $session_id) {
            return true;
        }

        $key = 'ppcart_hosted_claim_' . sanitize_key($session_id);
        $now = time();

        // Atomic INSERT on the unique option_name; false when the row already exists.
        if (false !== add_option($key, $now, '', 'no')) {
            return true;
        }

        // Reclaim an existing claim only when it is stale (crashed/abandoned run).
        $claimed_at = (int) get_option($key, 0);
        if ($claimed_at && ($now - $claimed_at) < self::HOSTED_CLAIM_TTL) {
            return false;
        }

        update_option($key, $now, false);
        return true;
    }

    /**
     * Release a hosted Checkout Session completion claim.
     *
     * @param string $session_id Stripe Checkout Session id.
     * @return void
     */
    public static function release_checkout_session($session_id)
    {
        if ('' === $session_id) {
            return;
        }

        delete_option('ppcart_hosted_claim_' . sanitize_key($session_id));
    }

    /**
     * Reserve a Stripe event ID so retries do not double-apply side effects.
     *
     * @param object|array $event Stripe event object.
     * @return bool True when the event can be processed.
     */
    public static function event_dedupe_gate($event)
    {
        $event_id = self::get($event, 'id', '');
        if ('' === $event_id) {
            return true;
        }

        $key = 'ppcart_stripe_evt_' . sanitize_key($event_id);
        if (get_transient($key)) {
            return false;
        }

        set_transient($key, 1, self::EVENT_TTL);
        return true;
    }

    /**
     * Release a dedupe reservation when the webhook did not apply.
     *
     * @param object|array $event Stripe event object.
     * @return void
     */
    public static function release_event_dedupe_gate($event)
    {
        $event_id = self::get($event, 'id', '');
        if ('' === $event_id) {
            return;
        }

        delete_transient('ppcart_stripe_evt_' . sanitize_key($event_id));
    }

    /**
     * Route a verified Stripe webhook event to the matching sync handler.
     *
     * @param object|array $event Stripe event object.
     * @return mixed False when unsupported or not matched, otherwise sync result.
     */
    public static function handle_webhook_event($event)
    {
        $__ppcart_template_result = include __DIR__ . '/templates/stripe-sync-webhook-handle-webhook-event.php';
        return 1 === $__ppcart_template_result ? null : $__ppcart_template_result;
    }

    /**
     * Store a compact webhook handling entry for support/debugging.
     *
     * @param object|array $event   Stripe event object.
     * @param string       $status  Handling status.
     * @param string       $message Human-readable summary.
     * @param array        $context Safe scalar diagnostic context.
     * @return void
     */
    public static function record_webhook_log($event, $status, $message = '', $context = [])
    {
        if (! $event || ! class_exists('PPCart_Stripe_Webhook_Logger')) {
            return;
        }

        PPCart_Stripe_Webhook_Logger::record_event($event, $status, $message, $context);
    }
}
