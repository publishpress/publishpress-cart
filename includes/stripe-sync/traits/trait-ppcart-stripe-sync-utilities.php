<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Sync_Utilities_Trait
{
    /**
     * Infer Stripe mode from a webhook event.
     *
     * @param object|array|null $event Stripe event object.
     * @return string
     */
    private static function guess_mode_from_event($event)
    {
        if ($event && self::get($event, 'livemode', false)) {
            return 'live';
        }

        if ($event) {
            return 'test';
        }

        return '';
    }

    /**
     * Read a key from an array or object.
     *
     * Stripe SDK resources and test fixtures can be object-like or array-like,
     * so sync code uses this helper instead of direct property access.
     *
     * @param object|array|null $object  Source value.
     * @param string            $key     Key/property name.
     * @param mixed             $default Default value.
     * @return mixed
     */
    public static function get($object, $key, $default = null)
    {
        if (is_array($object) && array_key_exists($key, $object)) {
            return $object[ $key ];
        }

        if (is_object($object) && isset($object->$key)) {
            return $object->$key;
        }

        return $default;
    }

    /**
     * Read a nested path from an array or object.
     *
     * @param object|array|null $object  Source value.
     * @param array             $path    Path of keys/properties.
     * @param mixed             $default Default value.
     * @return mixed
     */
    public static function path($object, $path, $default = null)
    {
        $value = $object;
        foreach ($path as $key) {
            $value = self::get($value, $key, null);
            if (null === $value) {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Read a canonical Stripe metadata key, with leftover fallback when Compat is on.
     *
     * @param object|array|null $object         Stripe resource that has a metadata bag.
     * @param string            $canonical_key Canonical metadata key.
     * @param mixed             $default        Default when empty.
     * @return mixed
     */
    public static function metadata($object, $canonical_key, $default = null)
    {
        if (function_exists('ppcart_stripe_metadata')) {
            return ppcart_stripe_metadata($object, $canonical_key, $default);
        }

        return $default;
    }

    /**
     * Write to the Cart debug logger ($ppcart_debug_logger) when available.
     *
     * @param string $message Debug message.
     * @param int    $level   Debug level.
     * @return void
     */
    private static function log_debug($message, $level = 0)
    {
        global $ppcart_debug_logger;

        if (is_object($ppcart_debug_logger) && is_callable([ $ppcart_debug_logger, 'log_debug' ])) {
            $ppcart_debug_logger->log_debug($message, $level);
        }
    }
}
