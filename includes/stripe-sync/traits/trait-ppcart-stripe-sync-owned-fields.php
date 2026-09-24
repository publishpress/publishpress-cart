<?php

if (! defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

trait PPCart_Stripe_Sync_Owned_Fields_Trait
{
    /**
     * Preserve Stripe-owned local fields outside the internal sync context.
     *
     * @param PPCart_Order|PPCart_Subscription $record Local record being stored.
     * @return PPCart_Order|PPCart_Subscription
     */
    public static function preserve_owned_fields($record)
    {
        if (PPCart_Stripe_Sync_Context::is_active() || empty($record->id) || ! self::is_stripe_backed_record($record)) {
            return $record;
        }

        if ($record instanceof PPCart_Order) {
            $current = new PPCart_Order($record->id);
        } elseif ($record instanceof PPCart_Subscription) {
            $current = new PPCart_Subscription($record->id);
        } else {
            return $record;
        }

        foreach (self::stripe_owned_fields_for_record($record) as $field) {
            if (property_exists($record, $field) && property_exists($current, $field)) {
                $record->$field = $current->$field;
            }
        }

        return $record;
    }

    /**
     * Check whether a field is controlled by Stripe.
     *
     * @param string                    $field  Meta key or model property name.
     * @param PPCart_Order|PPCart_Subscription|null $record Record context.
     * @return bool
     */
    public static function is_stripe_owned_field($field, $record = null)
    {
        $field = function_exists('ppcart_meta_key_suffix')
            ? ppcart_meta_key_suffix($field)
            : preg_replace('/^_?ppcart_/', '', sanitize_key($field));
        $fields = $record ? self::stripe_owned_fields_for_record($record) : array_merge(self::order_owned_fields(), self::subscription_owned_fields());

        return in_array($field, $fields, true);
    }

    /**
     * Check whether a loaded order/subscription is backed by Stripe.
     *
     * @param mixed $record Local record.
     * @return bool
     */
    public static function is_stripe_backed_record($record)
    {
        if (! $record) {
            return false;
        }

        if (isset($record->pay_method) && 'stripe' === $record->pay_method) {
            return true;
        }

        if ($record instanceof PPCart_Subscription && isset($record->subscription_id) && 0 === strpos((string) $record->subscription_id, 'sub_')) {
            return true;
        }

        return false;
    }

    /**
     * Check whether a post ID belongs to a Stripe-backed record.
     *
     * @param int $post_id Post ID.
     * @return bool
     */
    public static function is_stripe_backed_post($post_id)
    {
        $post_id = absint($post_id);
        if (! $post_id) {
            return false;
        }

        if ('stripe' === ppcart_get_post_meta($post_id, 'pay_method', true)) {
            return true;
        }

        $stripe_sub_id = ppcart_get_post_meta($post_id, 'stripe_subscription_id', true);
        if (is_string($stripe_sub_id) && 0 === strpos($stripe_sub_id, 'sub_')) {
            return true;
        }

        $stripe_sub_id = ppcart_get_post_meta($post_id, 'subscription_id', true);
        return is_string($stripe_sub_id) && 0 === strpos($stripe_sub_id, 'sub_');
    }

    /**
     * Get Stripe-owned fields for a record type.
     *
     * @param mixed $record Local record.
     * @return array
     */
    private static function stripe_owned_fields_for_record($record)
    {
        if ($record instanceof PPCart_Order) {
            return self::order_owned_fields();
        }

        if ($record instanceof PPCart_Subscription) {
            return self::subscription_owned_fields();
        }

        return [];
    }

    /**
     * Stripe-owned order model properties.
     *
     * @return array
     */
    private static function order_owned_fields()
    {
        return [ 'status', 'payment_status', 'transaction_id', 'refund_log' ];
    }

    /**
     * Stripe-owned subscription model properties.
     *
     * @return array
     */
    private static function subscription_owned_fields()
    {
        return [ 'status', 'sub_status', 'subscription_id', 'sub_next_bill_date', 'cancel_at', 'cancel_date' ];
    }
}
