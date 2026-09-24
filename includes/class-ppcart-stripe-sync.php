<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

if (! class_exists('PPCart_Stripe_Webhook_Logger')) {
    require_once dirname(__FILE__) . '/logging/class-ppcart-stripe-webhook-logger.php';
}

require_once dirname(__FILE__) . '/stripe-sync/class-ppcart-stripe-sync-context.php';
require_once dirname(__FILE__) . '/stripe-sync/traits/trait-ppcart-stripe-sync-webhook.php';
require_once dirname(__FILE__) . '/stripe-sync/traits/trait-ppcart-stripe-sync-subscriptions.php';
require_once dirname(__FILE__) . '/stripe-sync/traits/trait-ppcart-stripe-sync-invoices.php';
require_once dirname(__FILE__) . '/stripe-sync/traits/trait-ppcart-stripe-sync-hosted-checkout.php';
require_once dirname(__FILE__) . '/stripe-sync/traits/trait-ppcart-stripe-sync-refunds.php';
require_once dirname(__FILE__) . '/stripe-sync/traits/trait-ppcart-stripe-sync-reconciliation.php';
require_once dirname(__FILE__) . '/stripe-sync/traits/trait-ppcart-stripe-sync-owned-fields.php';
require_once dirname(__FILE__) . '/stripe-sync/traits/trait-ppcart-stripe-sync-utilities.php';

/**
 * Stripe as the authoritative source for PublishPress Cart payment records.
 *
 * This class is intentionally static because legacy code, webhook handlers, and
 * model guards call it directly. Keep new behavior grouped by concern so the
 * class remains navigable until larger services are extracted.
 */
class PPCart_Stripe_Sync
{
    use PPCart_Stripe_Sync_Webhook_Trait;
    use PPCart_Stripe_Sync_Subscriptions_Trait;
    use PPCart_Stripe_Sync_Invoices_Trait;
    use PPCart_Stripe_Sync_Hosted_Checkout_Trait;
    use PPCart_Stripe_Sync_Refunds_Trait;
    use PPCart_Stripe_Sync_Reconciliation_Trait;
    use PPCart_Stripe_Sync_Owned_Fields_Trait;
    use PPCart_Stripe_Sync_Utilities_Trait;

    /**
     * Webhook event dedupe TTL, in seconds.
     */
    public const EVENT_TTL = 604800;

    /**
     * WP-Cron hook for stale subscription reconciliation.
     */
    public const CRON_HOOK = 'ppcart_stripe_reconcile_stale_subscriptions';

    /*
     * Lifecycle and Stripe client helpers.
     */

    /**
     * Register hooks owned by the sync service.
     *
     * @return void
     */
    public static function init()
    {
        add_action('init', [ __CLASS__, 'schedule_reconciliation' ]);
        add_action(self::CRON_HOOK, [ __CLASS__, 'reconcile_stale_subscriptions' ]);
    }

    /**
     * Ensure stale subscription reconciliation is scheduled.
     *
     * @return void
     */
    public static function schedule_reconciliation()
    {
        if (! wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK);
        }
    }

    /**
     * Build a Stripe client for a gateway mode.
     *
     * @param string $mode Stripe mode. Usually live, test, or empty fallback.
     * @return \PublishPress\Stripe\StripeClient
     * @throws Exception When the Stripe secret key is missing.
     */
    public static function get_client_for_mode($mode = '')
    {
        $credentials = function_exists('ppcart_get_stripe_platform_credentials')
            ? ppcart_get_stripe_platform_credentials($mode)
            : [
                'mode' => $mode,
                'sk'   => ppcart_get_sensitive_option('_ppcart_stripe_' . sanitize_text_field($mode) . '_sk'),
            ];

        if (empty($credentials['sk'])) {
            throw new Exception(esc_html__('Stripe secret key missing.', 'publishpress-cart'));
        }

        return ppcart_stripe_client($credentials['sk']);
    }

    /*
     * Webhook routing, dedupe, and temporary audit logging.
     */

    /** Short TTL (seconds) for the hosted checkout completion claim; a crashed run self-heals after it. */
    public const HOSTED_CLAIM_TTL = 90;







    /*
     * Subscription sync.
     */






    /*
     * Invoice-to-order sync.
     */



    /*
     * Hosted Checkout Session sync.
     */






    /*
     * Charge and refund sync.
     */






    /*
     * Reconciliation and sync metadata.
     */





    /*
     * Stripe-owned field guard.
     */









    /*
     * Resource lookup, ownership, and mapping helpers.
     */












    /*
     * Shared object and logging helpers.
     */
}

/*
 * Initialize the static sync service and expose procedural compatibility helpers.
 */
PPCart_Stripe_Sync::init();

/**
 * Run a callback inside the Stripe sync context.
 *
 * @param callable $callback Callback to run.
 * @return mixed
 */
function ppcart_stripe_sync_context_run($callback)
{
    return PPCart_Stripe_Sync_Context::run($callback);
}

/**
 * Check whether a field is controlled by Stripe.
 *
 * @param string $field  Meta key or model property name.
 * @param mixed  $record Optional record context.
 * @return bool
 */
function ppcart_is_stripe_owned_field($field, $record = null)
{
    return PPCart_Stripe_Sync::is_stripe_owned_field($field, $record);
}

/**
 * Check whether a post is backed by Stripe.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function ppcart_is_stripe_backed_post($post_id)
{
    return PPCart_Stripe_Sync::is_stripe_backed_post($post_id);
}

/**
 * Preserve Stripe-owned fields before a local model store.
 *
 * @param mixed $record Local record.
 * @return mixed
 */
function ppcart_preserve_stripe_owned_fields($record)
{
    return PPCart_Stripe_Sync::preserve_owned_fields($record);
}
