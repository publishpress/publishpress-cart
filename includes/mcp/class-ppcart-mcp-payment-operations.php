<?php

/**
 * Inspect uncertain operations and release only after confirmed native reconciliation.
 * @package PPCart
 * @since 1.1.0
 */

defined('ABSPATH') || exit;
class PPCart_Mcp_Payment_Operations
{
    /** @since 1.1.0 @return bool */
    public function permitted()
    {
        return PPCart_Mcp_Payments::can_manage();
    }
    /** @since 1.1.0 @param bool $write Resolve. @return array */
    public function schema($write)
    {
        $fields = ['kind' => ['type' => 'string', 'enum' => ['order', 'subscription']], 'id' => ['type' => 'integer', 'minimum' => 1]];
        if ($write) {
            $fields['revision'] = ['type' => 'string', 'minLength' => 64, 'maxLength' => 64];
            $fields['dry_run'] = ['type' => 'boolean'];
        }
        return PPCart_Mcp_Input::schema($fields, $write ? ['kind', 'id', 'revision'] : ['kind', 'id']);
    }
    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function read($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(false));
        if (is_wp_error($input)) {
            return $input;
        }
        if (! $this->permitted()) {
            return new WP_Error('ppcart_mcp_forbidden', __('You cannot inspect payment operations.', 'publishpress-cart'));
        }
        $record = (new PPCart_Mcp_Records())->read(['kind' => $input['kind'], 'id' => $input['id']]);
        if (is_wp_error($record)) {
            return $record;
        }
        $held = get_option('_ppcart_mcp_payment_lock_' . $input['id']);
        $safe = is_array($held) ? array_intersect_key($held, array_flip(['kind', 'state', 'created', 'amount', 'transition', 'timing'])) : [];
        if (is_array($held) && ($held['kind'] ?? '') !== $input['kind']) {
            return PPCart_Mcp_Input::error();
        }
        $confirmed = false;
        if (is_array($held) && 'unconfirmed' === ($held['state'] ?? '')) {
            if ('order' === $input['kind']) {
                $confirmed = (float) $record['refund_total'] + 0.00000001 >= (float) $held['refund_total'] + (float) $held['amount'];
            } elseif (! empty($held['transition'])) {
                $confirmed = 'paused' === $held['transition'] ? 'paused' === $record['status'] : in_array($record['status'], ['active', 'trialing'], true);
            } else {
                $confirmed = 'period_end' === ($held['timing'] ?? null) ? ! empty($record['cancel_at']) : in_array($record['status'], ['canceled', 'cancelled'], true);
            }
        }
        return ['id' => $input['id'], 'pending' => false !== $held, 'operation' => (object) $safe, 'native_result_confirmed' => $confirmed,
            'revision' => hash_hmac('sha256', wp_json_encode([$held, $record['revision']]), wp_salt('nonce'))];
    }
    /** @since 1.1.0 @param mixed $input Input. @return array|WP_Error */
    public function resolve($input)
    {
        $input = PPCart_Mcp_Input::validate($input, $this->schema(true));
        if (is_wp_error($input)) {
            return $input;
        }
        $state = $this->read(['kind' => $input['kind'], 'id' => $input['id']]);
        if (is_wp_error($state)) {
            return $state;
        }
        if (! hash_equals($input['revision'], $state['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Operation changed. Read it again.', 'publishpress-cart'));
        }
        if (! $state['pending'] || ! $state['native_result_confirmed']) {
            return new WP_Error('ppcart_mcp_payment_unconfirmed', __('Reconcile with the provider using native synchronization first. This action cannot clear an unknown outcome.', 'publishpress-cart'));
        }
        if (! empty($input['dry_run'])) {
            return $state + ['dry_run' => true];
        }
        $key = '_ppcart_mcp_payment_lock_' . $input['id'];
        $expected = get_option($key);
        $fresh = $this->read(['kind' => $input['kind'], 'id' => $input['id']]);
        if (is_wp_error($fresh) || ! hash_equals($input['revision'], $fresh['revision'])) {
            return new WP_Error('ppcart_mcp_stale_revision', __('Operation changed before resolution.', 'publishpress-cart'));
        }
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Conditional removal prevents one resolver from deleting a newer operation's lock.
        $removed = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s", $key, maybe_serialize($expected)));
        wp_cache_delete($key, 'options');
        wp_cache_delete('alloptions', 'options');
        if (1 !== $removed) {
            return new WP_Error('ppcart_mcp_save_failed', __('Operation could not be resolved.', 'publishpress-cart'));
        }
        return ['resolved' => true, 'id' => $input['id']];
    }
}
