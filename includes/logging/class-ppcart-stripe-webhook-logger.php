<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

require_once dirname(__FILE__) . '/traits/trait-ppcart-stripe-webhook-logger-admin.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-stripe-webhook-logger-writer.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-stripe-webhook-logger-reader.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-stripe-webhook-logger-rules.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-stripe-webhook-logger-format.php';
require_once dirname(__FILE__) . '/traits/trait-ppcart-stripe-webhook-logger-utilities.php';

/**
 * File-backed operational audit log for Stripe webhook handling.
 */
class PPCart_Stripe_Webhook_Logger
{
    use PPCart_Stripe_Webhook_Logger_Admin_Trait;
    use PPCart_Stripe_Webhook_Logger_Writer_Trait;
    use PPCart_Stripe_Webhook_Logger_Reader_Trait;
    use PPCart_Stripe_Webhook_Logger_Rules_Trait;
    use PPCart_Stripe_Webhook_Logger_Format_Trait;
    use PPCart_Stripe_Webhook_Logger_Utilities_Trait;

    public const ENABLE_OPTION          = '_ppcart_enable_stripe_webhook_log';
    public const INCLUDE_IGNORED_OPTION = '_ppcart_stripe_webhook_log_include_ignored';
    public const FILE_OPTION            = '_ppcart_stripe_webhook_log_file';
    public const IP_HASH_SEED_OPTION    = '_ppcart_stripe_webhook_ip_hash_seed';
    public const MAX_ROTATED_FILES      = 3;
    public const DEFAULT_TAIL_BYTES     = 262144;
    public const DEFAULT_READ_LIMIT     = 100;
    public const REQUEST_SIZE_LIMIT     = 1048576;
    public const REJECTED_TTL           = 60;

    /**
     * Tracks events that already have a final row in this request.
     *
     * @var array
     */
    private static $event_final_statuses = [];

    /**
     * Register settings and admin actions.
     *
     * @return void
     */
    public static function init()
    {
        if (function_exists('add_action')) {
            add_action('init', [ __CLASS__, 'handle_admin_request' ]);
        }

        if (function_exists('add_filter')) {
            add_filter('_ppcart_option_list', [ __CLASS__, 'register_settings' ], 999);
        }
    }
}
