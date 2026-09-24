<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

/**
 * Tracks when writes are coming from the Stripe sync layer.
 *
 * Order and subscription models use this context to distinguish Stripe-owned
 * updates from local admin edits. Outside this context, Stripe-owned fields are
 * preserved from the current database record.
 */
class PPCart_Stripe_Sync_Context
{
    /**
     * Nesting depth for active sync callbacks.
     *
     * @var int
     */
    private static $depth = 0;

    /**
     * Run a callback while Stripe-owned field writes are allowed.
     *
     * @param callable $callback Callback to run.
     * @return mixed Callback return value.
     */
    public static function run($callback)
    {
        self::$depth++;

        try {
            return call_user_func($callback);
        } finally {
            self::$depth--;
        }
    }

    /**
     * Check whether code is currently running inside a Stripe sync callback.
     *
     * @return bool
     */
    public static function is_active()
    {
        return self::$depth > 0;
    }
}
